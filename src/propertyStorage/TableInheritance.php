<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Schema;
use yii\helpers\ArrayHelper;

class TableInheritance extends AbstractPropertyStorage
{
    /**
     * @inheritdoc
     */
    public function fillProperties(&$models)
    {
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);

        $tableInheritanceRows = Yii::$app->cache->lazy(function () use ($firstModel, $models) {
            $rows =
                $firstModel->getDb()->createCommand(
                    'SELECT * FROM ' . $firstModel->tableInheritanceTable() . ' WHERE ' .
                    PropertiesHelper::getInCondition($models)
                )->queryAll();

            return ArrayHelper::map($rows, 'model_id', function ($item) {
                return $item;
            });
        }, PropertiesHelper::generateCacheKey($models, 'ti_rows'), 86400, $firstModel->commonTag());

        // fill models with properties
        foreach ($models as &$model) {
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
            $modelId = $model->id;

            if (isset($tableInheritanceRows[$modelId])) {
                $properties = $tableInheritanceRows[$modelId];

                foreach ($properties as $key => $value) {
                    // skip model_id column
                    if ($key === 'model_id') {
                        continue;
                    }

                    /** @var Property $property */
                    $property = PropertiesHelper::getPropertyModel($model, $key);
                    if ($property === null) {
                        // skip unbinded property
                        continue;
                    }
                    $value = Property::castValueToDataType($value, $property->data_type);
                    $model->$key = $value;
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteAllProperties(&$models)
    {
        /** @var \yii\db\Command $command */
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        $command = $firstModel->getDb()->createCommand()
            ->delete($firstModel->tableInheritanceTable(), PropertiesHelper::getInCondition($models));

        $command->execute();
    }

    /**
     * @param ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[]|\DevGroup\DataStructure\behaviors\HasProperties[] $models
     *
     * @return boolean
     */
    public function storeValues(&$models)
    {
        if (count($models) === 0) {
            return true;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);

        $db = $firstModel->getDb();

        /** @var \yii\db\Command[] $queries */
        $queries = [];

        foreach ($models as $model) {
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\DataStructure\traits\PropertiesTrait $model */
            $model->ensurePropertiesAttributes();

            $modelTableInheritancePairs = [];

            foreach ($model->propertiesAttributes as $propertyId => $key) {
                $property = Property::findById($propertyId);
                if ($property->storage_id === $this->storageId) {
                    // check if this property changed
                    if (in_array($propertyId, $model->changedProperties)) {
                        $modelTableInheritancePairs[$key] = $model->$key;
                    }
                }
            }
            if (count($modelTableInheritancePairs) > 0) {
                $queries[] = $db->createCommand()->update(
                    $firstModel->tableInheritanceTable(),
                    $modelTableInheritancePairs,
                    [
                        'model_id' => $model->id
                    ]
                );
            }
        }
        if (count($queries) > 0) {
            $db->transaction(function ($db) use ($queries) {
                foreach ($queries as $query) {
                    $query->execute();
                }
            });
        }
        return true;
    }

    /**
     * Creates table inheritance rows
     *
     * @param \DevGroup\DataStructure\behaviors\HasProperties[]|\DevGroup\DataStructure\traits\PropertiesTrait[]|\yii\db\ActiveRecord[] $models
     */
    public function modelsInserted(&$models)
    {
        /** @var \yii\db\Command $command */

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        $ids = [];
        foreach ($models as $model) {
            $ids [] = [$model->id];
        }
        $command = $firstModel->getDb()->createCommand()
            ->batchInsert(
                $firstModel->tableInheritanceTable(),
                ['model_id'],
                $ids
            );

        $command->execute();
    }

    /**
     * @inheritdoc
     */
    public function beforePropertyValidate(Property &$property)
    {
        if ($property->allow_multiple_values) {
            $property->addError(
                'allow_multiple_values',
                Yii::t('app', 'Property can\'t has multiple values if storage type is Table Inherited Row.')
            );
            return false;
        }
        return true;
    }

    /**
     * Action that should be done by property storage before property adding.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @return bool Success status, true if all's ok
     */
    public function beforePropertyAdd(Property &$property)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\DataStructure\behaviors\HasProperties $applicableModel */
        $applicableModel = PropertiesHelper::classNameForApplicablePropertyModelId($property->applicable_property_model_id);
        $tableName = $applicableModel::tableInheritanceTable();
        $db = $applicableModel::getDb();
        /** @var \yii\db\Command $command */
        $command = $db->createCommand()->addColumn(
            $tableName,
            $property->key,
            $this->columnTypeForDataType($db, $property->data_type)
        );
        // no exception here - is good result
        // we don't catch exception here so user will se it as it is(ie. "Duplicate column name 'foo'")
        $command->execute();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function beforePropertyDelete(Property &$property)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\DataStructure\behaviors\HasProperties $applicableModel */
        $applicableModel = PropertiesHelper::classNameForApplicablePropertyModelId($property->applicable_property_model_id);
        $tableName = $applicableModel::tableInheritanceTable();
        $db = $applicableModel::getDb();
        /** @var \yii\db\Command $command */
        $command = $db->createCommand()->dropColumn(
            $tableName,
            $property->key
        );
        // no exception here - is good result
        // we don't catch exception here so user will se it as it is(ie. "Column 'foo' does not exist")
        $command->execute();

        return true;
    }

    /**
     * @param \yii\db\Connection $db
     * @param integer $type
     * @return string
     */
    protected function columnTypeForDataType($db, $type)
    {
        $schema = $db->getSchema();
        switch ($type) {
            case Property::DATA_TYPE_FLOAT:
                return $schema->createColumnSchemaBuilder(Schema::TYPE_FLOAT);
                break;
            case Property::DATA_TYPE_INTEGER:
                return $schema->createColumnSchemaBuilder(Schema::TYPE_INTEGER);
                break;

            case Property::DATA_TYPE_STRING:
                /** @var \yii\db\ColumnSchemaBuilder $builder */
                return $schema->createColumnSchemaBuilder(Schema::TYPE_STRING);
                break;

            case Property::DATA_TYPE_BOOLEAN:
                return $schema->createColumnSchemaBuilder(Schema::TYPE_BOOLEAN);
                break;

            case Property::DATA_TYPE_TEXT:
            case Property::DATA_TYPE_PACKED_JSON:
            default:
                return $schema->createColumnSchemaBuilder(Schema::TYPE_TEXT);
                break;
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteProperties($models, $propertyIds)
    {
        $columns = Property::find()
            ->select(new Expression('""'))
            ->where(
                [
                    'id' => $propertyIds,
                    'storage_id' => $this->storageId,
                ]
            )
            ->indexBy('key')
            ->column();
        if (count($columns) > 0) {
            foreach ($models as $model) {
                $model->getDb()->createCommand()->update(
                    $model->tableInheritanceTable(),
                    $columns,
                    [
                        'model_id' => $model->id,
                    ]
                )->execute();
            }
        }
    }
}
