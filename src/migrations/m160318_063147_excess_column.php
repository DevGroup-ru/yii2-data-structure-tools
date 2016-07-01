<?php

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyPropertyGroup;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\propertyStorage\TableInheritance;
use yii\db\Migration;
use yii\helpers\ArrayHelper;

class m160318_063147_excess_column extends Migration
{
    public function up()
    {
        /** @var Property[] $properties */
        $properties = Property::find()
            ->where(
                [
                    'storage_id' => PropertyStorage::find()
                        ->select('id')
                        ->where(['class_name' => TableInheritance::class])
                        ->scalar()
                ]
            )
            ->all();
        foreach ($properties as $property) {
            foreach ($property->propertyGroups as $propertyGroup) {
                TableInheritance::addColumn($property, $propertyGroup);
            }
        }
        $this->dropColumn(
            Property::tableName(),
            'applicable_property_model_id'
        );
    }

    public function down()
    {
        $this->addColumn(
            Property::tableName(),
            'applicable_property_model_id',
            $this->integer()->notNull()
        );
        /** @var Property[] $properties */
        $properties = Property::find()->all();
        foreach ($properties as $property) {
            if (isset($property->propertyGroups[0])) {
                $property->applicable_property_model_id = $property->propertyGroups[0]->applicable_property_model_id;
                $property->save(true, ['applicable_property_model_id']);
            }
        }
    }
}
