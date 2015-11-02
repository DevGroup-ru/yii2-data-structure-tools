<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use yii\db\ActiveRecord;

class TableInheritance extends AbstractPropertyStorage
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
        // TODO: Implement storeValues() method.
    }

    /**
     * Action that should be done by property storage before property adding.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @return bool Success status, true if all's ok
     */
    public function beforePropertyAdd(Property &$property)
    {

        return true;
    }
}
