<?php

use yii\db\Migration;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyPropertyGroup;

class m160316_090100_new_keys extends Migration
{
    public function up()
    {
        $wrongClassNames = ApplicablePropertyModels::find()
            ->select('class_name')
            ->groupBy('class_name')
            ->having('COUNT(*) > 1')
            ->column();
        foreach ($wrongClassNames as $className) {
            $ids = ApplicablePropertyModels::find()
                ->select('id')
                ->where(['class_name' => $className])
                ->column();
            $firstId = null;
            foreach ($ids as $idx => $id) {
                if ($firstId === null) {
                    $firstId = $id;
                    continue;
                }
                PropertyGroup::updateAll(
                    ['applicable_property_model_id' => $firstId],
                    ['applicable_property_model_id' => $id]
                );
                ApplicablePropertyModels::deleteAll(['id' => $id]);
            }
        }
        $this->createIndex(
            'uq-applicable_property_model-class_name',
            ApplicablePropertyModels::tableName(),
            'class_name',
            true
        );
        $ids = PropertyPropertyGroup::find()
            ->select('property_id')
            ->where(['not in', 'property_id', Property::find()->select('id')])
            ->column();
        PropertyPropertyGroup::deleteAll(['property_id' => $ids]);
        $ids = PropertyPropertyGroup::find()
            ->select('property_group_id')
            ->where(['not in', 'property_group_id', PropertyGroup::find()->select('id')])
            ->column();
        PropertyPropertyGroup::deleteAll(['property_group_id' => $ids]);
        $this->addForeignKey(
            'fk-property_property_group-property-id',
            PropertyPropertyGroup::tableName(),
            'property_id',
            Property::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-property_property_group-property_group-id',
            PropertyPropertyGroup::tableName(),
            'property_group_id',
            PropertyGroup::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey('fk-property_property_group-property_group-id', PropertyPropertyGroup::tableName());
        $this->dropForeignKey('fk-property_property_group-property-id', PropertyPropertyGroup::tableName());
        $this->dropIndex('uq-applicable_property_model-class_name', ApplicablePropertyModels::tableName());
    }
}
