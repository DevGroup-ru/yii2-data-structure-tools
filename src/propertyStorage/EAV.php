<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use yii\db\ActiveRecord;

class EAV extends AbstractPropertyStorage
{

    /**
     * @inheritdoc
     */
    public function fillProperties(&$models)
    {
        // TODO: Implement fillProperties() method.
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
        $storageId = array_search($this->className(), PropertiesHelper::storageHandlers());
        if ($storageId === false) {
            throw new \Exception("Storage not found in handlers");
        }

        $deleteModelIds = [];
        $deletePropertyIds = [];
        $insertRows = [];

        /** @var ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($model);

        foreach ($models as $model) {
            foreach ($model->changedProperties as $propertyId) {
                /** @var Property $propertyModel */
                $propertyModel = Property::loadModel(
                    $propertyId,
                    false,
                    true,
                    86400,
                    false,
                    true
                );
                if ($propertyModel === null) {
                    continue;
                }
                if ($propertyModel->storage_id === $storageId) {
                    $newRows = $this->saveModelProperty($model, $propertyModel);
                    foreach ($newRows as $row) {
                        $insertRows[] = $row;
                    }
                }
            }
        }

        /** @var \yii\db\Command $cmd */
        $cmd = $firstModel->getDb()->createCommand();
        $cmd = $cmd->delete($model->eavTable(), ['model_id'=>$deleteModelIds, 'property_id' => $deletePropertyIds], []);
        $cmd->execute();

        if (count($insertRows) === 0) {
            return true;
        }

        $cmd = $firstModel->getDb()->createCommand();
        return $cmd
            ->batchInsert(
                $model->eavTable(),
                [
                    'model_id', 'property_id', 'sort_order', 'value_int', 'value_float', 'value_string', 'value_text'
                ],
                $insertRows
            )->execute() > 0;

    }

    /**
     * @param ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $model
     * @param Property                                                    $propertyModel
     *
     * @return array
     */
    private function saveModelProperty($model, $propertyModel)
    {

        $modelId = $model->id;
        $propertyId = $propertyModel->id;



        $key = $propertyModel->key;
        $values = (array) $model->$key;

        if (count($values) === 0) {
            return true;
        }

        $valueField = static::dataTypeToEavColumn($propertyModel->data_type);

        $rows = [];

        foreach ($values as $index => $value) {
            $rows[] = [
                $modelId,
                $propertyId,
                $index,
                $valueField === 'value_int' ? $value : 0,
                $valueField === 'value_float' ? $value : 0,
                $valueField === 'value_string' ? $value : '',
                $valueField === 'value_text' ? $value : '',
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
                return 'value_int';
                break;

            case Property::DATA_TYPE_TEXT:
            case Property::DATA_TYPE_PACKED_JSON:
                return 'value_text';
                break;

            case Property::DATA_TYPE_STRING:
            default:
                return 'value_string';
                break;
        }
    }
}
