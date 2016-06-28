<?php

use DevGroup\DataStructure\propertyHandler\StaticValues;
use yii\db\Migration;

class m160628_130235_select_handler extends Migration
{
    public function up()
    {
        $this->update('{{%property_handlers}}', ['name' => 'Select2'], ['class_name' => StaticValues::class]);
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Select',
                'class_name' => \DevGroup\DataStructure\propertyHandler\Select::class,
                'sort_order' => 2,
                'packed_json_default_config' => '[]',
            ]
        );
    }

    public function down()
    {
        $this->update('{{%property_handlers}}', ['name' => 'Static values'], ['class_name' => StaticValues::class]);
        $this->delete(
            '{{%property_handlers}}',
            ['class_name' => \DevGroup\DataStructure\propertyHandler\Select::class,]
        );
    }

}
