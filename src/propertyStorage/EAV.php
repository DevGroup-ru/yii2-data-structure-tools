<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyHandlers;
use DevGroup\DataStructure\propertyHandler\TextArea;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\VarDumper;

class EAV extends AbstractPropertyStorage
{

    /**
     * @inheritdoc
     */
    public function fillProperties(&$models, $languageId = null)
    {
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        /** @var \yii\db\Command $command */
        $query = new Query();
        $query->select('*')
            ->from($firstModel->eavTable())
            ->where(PropertiesHelper::getInCondition($models))
            ->orderBy([
                'model_id' => SORT_ASC,
                'sort_order' => SORT_ASC,
            ]);
        if (null !== $languageId) {
            $query->andWhere(['language_id' => [(int)$languageId, 0]]);
        }

        $values = $query->createCommand($firstModel->getDb())
            ->queryAll();
        $values = ArrayHelper::map(
            $values,
            'id',
            function ($item) {
                return $item;
            },
            'model_id'
        );
        $textAreaHandlerId = PropertyHandlers::find()->select('id')->where(['class_name' => TextArea::class])->scalar();
        foreach ($models as &$model) {
            if (isset($values[$model->id])) {
                $groupedByProperty = ArrayHelper::map(
                    $values[$model->id],
                    'id',
                    function ($item) {
                        return $item;
                    },
                    'property_id'
                );
                foreach ($groupedByProperty as $propertyId => $propertyRows) {
                    /** @var Property $property */
                    $property = Property::findById($propertyId);
                    $key = $property->key;
                    if (null === $languageId) {
                        $model->$key = self::fillForBackend($propertyRows, $property, $textAreaHandlerId);
                    } else {
                        $model->$key = self::fillForFrontend($propertyRows, $property);
                    }
                }
            }
        }
    }

    /**
     * @param array $propertyRows
     * @param Property $property
     * @param int | false $textAreaHandlerId
     * @return array | string
     */
    private static function fillForBackend($propertyRows, $property, $textAreaHandlerId)
    {
        $carry = [];
        $column = static::dataTypeToEavColumn($property->data_type);
        foreach ($propertyRows as $row) {
            $value = Property::castValueToDataType($row[$column], $property->data_type);
            if ($property->allow_multiple_values === false) {
                if (true === $property->canTranslate()) {
                    if (false === empty($carry[$row['language_id']])) {
                        continue;
                    }
                } else {
                    if (true === empty($carry)) {
                        $carry = $value;
                    }
                    continue;
                }
            }
            if (true === $property->canTranslate()) {
                $carry[$row['language_id']][$row['sort_order']] = $value;
            } else {
                if ($property->property_handler_id == $textAreaHandlerId) {
                    //in this case there must not be an array, because TextArea can not be of multiple values type
                    $carry = $value;
                } else {
                    $carry[] = $value;
                }
            }
        }
        return $carry;
    }

    /**
     * @param array $propertyRows
     * @param Property $property
     * @return array|string
     */
    private static function fillForFrontend($propertyRows, $property)
    {
        $carry = '';
        $column = static::dataTypeToEavColumn($property->data_type);
        foreach ($propertyRows as $row) {
            $value = Property::castValueToDataType($row[$column], $property->data_type);
            if ($property->allow_multiple_values === false) {
                if (true === empty($carry)) {
                    $carry = $value;
                }
                continue;
            }
            $carry[] = $value;
        }
        return $carry;
    }

    /**
     * @inheritdoc
     */
    public function deleteAllProperties(&$models)
    {
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        /** @var \yii\db\Command $command */
        $command = $firstModel->getDb()->createCommand()
            ->delete($firstModel->eavTable(), PropertiesHelper::getInCondition($models));

        $command->execute();
    }

    /**
     * @param ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[] $models
     *
     * @return bool
     * @throws \Exception
     * @throws bool
     */
    public function storeValues(&$models)
    {
        if (count($models) === 0) {
            return true;
        }
        $insertRows = [];
        $deleteRows = [];
        /** @var ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);
        foreach ($models as $model) {
            foreach ($model->changedProperties as $propertyId) {
                /** @var Property $propertyModel */
                $propertyModel = Property::findById($propertyId);
                if ($propertyModel === null) {
                    continue;
                }
                if ($propertyModel->storage_id === $this->storageId) {
                    if (isset($deleteRows[$model->id])) {
                        $deleteRows[$model->id][] = $propertyId;
                    } else {
                        $deleteRows[$model->id] = [$propertyId];
                    }
                    $newRows = self::saveModelPropertyRow($model, $propertyModel);
                    foreach ($newRows as $row) {
                        $insertRows[] = $row;
                    }
                }
            }
        }
        if (count($deleteRows) > 0) {
            if (count($deleteRows) > 1) {
                $condition = ['OR'];
                foreach ($deleteRows as $modelId => $propertyIds) {
                    $condition = array_merge($condition, [['model_id' => $modelId, 'property_id' => $propertyIds]]);
                }
            } else {
                $condition = [];
                foreach ($deleteRows as $modelId => $propertyIds) {
                    $condition = ['model_id' => $modelId, 'property_id' => $propertyIds];
                }
            }
            $firstModel->getDb()->createCommand()->delete($firstModel->eavTable(), $condition)->execute();
        }
        if (count($insertRows) === 0) {
            return true;
        }
        $cmd = $firstModel->getDb()->createCommand();
        return $cmd
            ->batchInsert(
                $firstModel->eavTable(),
                [
                    'model_id',
                    'property_id',
                    'sort_order',
                    'value_integer',
                    'value_float',
                    'value_string',
                    'value_text',
                    'language_id',
                ],
                $insertRows
            )->execute() > 0;
    }

    /**
     * @param ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $model
     * @param Property $propertyModel
     *
     * @return array
     */
    private static function saveModelPropertyRow(ActiveRecord $model, Property $propertyModel)
    {
        $modelId = $model->id;
        $propertyId = $propertyModel->id;
        $key = $propertyModel->key;
        $values = (array)$model->$key;
        if (count($values) === 0) {
            return [];
        }
        $valueField = self::dataTypeToEavColumn($propertyModel->data_type);
        $rows = [];
        if (true === $propertyModel->canTranslate()) {
            foreach ($values as $langId => $data) {
                $rows = array_merge($rows, self::prepareRows($data, $modelId, $propertyId, $valueField, $langId));
            }
        } else {
            $rows = array_merge($rows, self::prepareRows($values, $modelId, $propertyId, $valueField));
        }
        return $rows;
    }

    /**
     * @param $values
     * @param $modelId
     * @param $propertyId
     * @param $valueField
     * @param int $langId
     * @return array
     */
    private static function prepareRows($values, $modelId, $propertyId, $valueField, $langId = 0)
    {
        if (true === empty($values)) {
            return [];
        }
        $rows = [];
        foreach ($values as $index => $value) {
            if ($value !== 0 && true === empty($value)) {
                continue;
            }
            $rows[] = [
                $modelId,
                $propertyId,
                $index,
                $valueField === 'value_integer' ? (int)$value : 0,
                $valueField === 'value_float' ? (float)$value : 0,
                $valueField === 'value_string' ? $value : '',
                $valueField === 'value_text' ? $value : '',
                $langId
            ];
        }
        return $rows;
    }

    /**
     * Returns EAV column by property data type.
     *
     * @param integer $type
     *
     * @return string
     */
    public static function dataTypeToEavColumn($type)
    {
        switch ($type) {
            case Property::DATA_TYPE_FLOAT:
                return 'value_float';
                break;

            case Property::DATA_TYPE_BOOLEAN:
            case Property::DATA_TYPE_INTEGER:
                return 'value_integer';
                break;

            case Property::DATA_TYPE_TEXT:
            case Property::DATA_TYPE_PACKED_JSON:
            case Property::DATA_TYPE_INVARIANT_STRING:
                return 'value_text';
                break;

            case Property::DATA_TYPE_STRING:
            default:
                return 'value_string';
                break;
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteProperties($models, $propertyIds)
    {
        foreach ($models as $model) {
            $model->getDb()
                ->createCommand()
                ->delete($model->eavTable(), ['model_id' => $model->id, 'property_id' => (array)$propertyIds])
                ->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public function afterPropertyDelete(Property &$property)
    {
        $classNames = static::getApplicablePropertyModelClassNames($property->id);
        foreach ($classNames as $className) {
            $className::getDb()
                ->createCommand()
                ->delete(
                    $className::eavTable(),
                    ['property_id' => $property->id]
                )
                ->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public function afterPropertyChange(Property &$property, $changedAttributes)
    {
        $classNames = self::getApplicablePropertyModelClassNames($property->id);
        foreach ($classNames as $className) {
            if (true === isset($changedAttributes['data_type'])
                && ($property->data_type != $changedAttributes['data_type'])
            ) {
                self::updateDataType($property, $changedAttributes['data_type'], $className);
            }
        }
    }

    /**
     * Moves EAV values from one column to another with theirs auto converting.
     * Will add duplicate values for all defined languages if property values can be translated
     * Note if you move TEXT to STRING you will get only first 255 symbols due to field type limitations
     *
     * @param Property $property
     * @param int $old
     * @param ActiveRecord | HasProperties | PropertiesTrait $className
     */
    private static function updateDataType($property, $old, $className)
    {
        $oldColumn = self::dataTypeToEavColumn($old);
        $newColumn = self::dataTypeToEavColumn($property->data_type);
        $valueToOldColumn = Property::castValueToDataType('', $old);
        $canTranslate = $property->canTranslate();
        $wasTranslatable = in_array($old, [Property::DATA_TYPE_STRING, Property::DATA_TYPE_TEXT]);
        $rowConfig = [
            'model_id' => 0,
            'property_id' => 0,
            'sort_order' => 0,
            'value_integer' => 0,
            'value_float' => 0,
            'value_string' => '',
            'value_text' => '',
            'language_id' => 0,
        ];
        $whereCondition = ['property_id' => $property->id];
        $oldValues = (new Query())
            ->select(array_keys($rowConfig))
            ->from($className::eavTable())
            ->where($whereCondition)
            ->all();
        $newValues = [];
        //lang based rows we can search for both [model_id, property_id]
        //language based value for lang independent row we should get for Yii::$app->multilingual->default_language_id
        if ((true === $canTranslate) && (true === $wasTranslatable)) {
            //leave all rows just move values
            foreach ($oldValues as $row) {
                $newRow = array_replace($rowConfig, $row);
                $newRow[$oldColumn] = $valueToOldColumn;
                $newRow[$newColumn] = Property::castValueToDataType($row[$oldColumn], $property->data_type);
                $newValues[] = $newRow;
            }
        } else if ((false === $canTranslate) && (true === $wasTranslatable)) {
            //remove duplicated language based values and set language_id to 0
            $checks = [];
            $defaultLang = Yii::$app->multilingual->default_language_id;
            foreach ($oldValues as $row) {
                $checkVar = $row['model_id'] . '-' . $row['property_id'];
                if (false === in_array($checkVar, $checks)
                    && ($row['language_id'] == $defaultLang || $row['language_id'] == 0)
                ) {
                    $newRow = array_replace($rowConfig, $row);
                    $newRow[$oldColumn] = $valueToOldColumn;
                    $newRow[$newColumn] = Property::castValueToDataType($row[$oldColumn], $property->data_type);
                    $newRow['language_id'] = 0;
                    $newValues[] = $newRow;
                    $checks[] = $checkVar;
                }
            }
        } else if ((true === $canTranslate) && (false === $wasTranslatable)) {
            //duplicate rows for all lang ids
            $langs = Yii::$app->multilingual->getAllLanguages();
            $langs = ArrayHelper::map($langs, 'id', 'id');
            foreach ($oldValues as $row) {
                $newRow = array_replace($rowConfig, $row);
                $newRow[$oldColumn] = $valueToOldColumn;
                $newRow[$newColumn] = Property::castValueToDataType($row[$oldColumn], $property->data_type);
                foreach ($langs as $langId) {
                    $newRow['language_id'] = $langId;
                    $newValues[] = $newRow;
                }
            }
        } else {
            //leave all rows just move values with no duplicated for other languages
            $checks = [];
            foreach ($oldValues as $row) {
                $checkVar = $row['model_id'] . '-' . $row['property_id'];
                if (false === in_array($checkVar, $checks)) {
                    $newRow = array_replace($rowConfig, $row);
                    $newRow[$oldColumn] = $valueToOldColumn;
                    $newRow[$newColumn] = Property::castValueToDataType($row[$oldColumn], $property->data_type);
                    $newRow['language_id'] = 0;
                    $newValues[] = $newRow;
                    $checks[] = $checkVar;
                }
            }
        }
        Yii::$app->getDb()->createCommand()->delete(
            $className::eavTable(),
            $whereCondition
        )->execute();
        if (false === empty($newValues)) {
            Yii::$app->getDb()->createCommand()->batchInsert(
                $className::eavTable(),
                array_keys($rowConfig),
                $newValues
            )->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public static function getPropertyValuesByParams(
        $propertyId,
        $params = '',
        $customDependency = null,
        $customKey = '',
        $cacheLifetime = 86400
    )
    {
        $property = Property::findById($propertyId);
        $column = static::dataTypeToEavColumn($property->data_type);
        $params = static::prepareParams($params, $column);
        $classNames = static::getApplicablePropertyModelClassNames($propertyId);
        $queries = [];
        $keys = [$customKey, 'PropertyValues', 'Property', $propertyId, Json::encode($params)];
        $tags = [$property->objectTag()];
        foreach ($classNames as $className) {
            $query = new Query();
            $query->select($column)->from($className::eavTable())->where($params);
            $queries[] = $query;
            $keys[] = $className;
            $tags[] = $className::commonTag();
        }
        $dependency = self::dependencyHelper($customDependency, $tags);
        $query = self::unionQueriesToOne($queries);
        sort($keys);
        return Yii::$app->cache->lazy(
            function () use ($query) {
                return $query->column();
            },
            'EAVPV_' . md5(Json::encode($keys)),
            $cacheLifetime,
            $dependency
        );

    }

    /**
     * @inheritdoc
     */
    public static function getModelsByPropertyValues(
        $propertyId,
        $values = [],
        $returnType = self::RETURN_ALL,
        $customDependency = null,
        $cacheLifetime = 86400
    )
    {
        $result = $returnType === self::RETURN_COUNT ? 0 : [];
        $property = Property::findById($propertyId);
        $tags = [$property->objectTag()];
        $classNames = static::getApplicablePropertyModelClassNames($propertyId);
        $column = static::dataTypeToEavColumn($property->data_type);
        foreach ($classNames as $className) {
            $eavTable = $className::eavTable();
            $tmpQuery = $className::find()->innerJoin(
                "$eavTable  EAV",
                $className::tableName() . '.id= EAV.model_id'
            )->andWhere(
                [
                    'EAV.property_id' => $propertyId,
                    'EAV.' . $column => $values,
                ]
            )->addGroupBy($className::primaryKey());
            $dependency = static::dependencyHelper(
                $customDependency,
                ArrayHelper::merge($tags, (array)$className::commonTag())
            );
            $result = static::valueByReturnType(
                $returnType,
                $tmpQuery,
                $result,
                $className,
                $dependency,
                $cacheLifetime
            );
        }
        return $result;
    }
}
