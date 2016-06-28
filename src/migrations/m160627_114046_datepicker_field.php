<?php

use yii\db\Migration;

class m160627_114046_datepicker_field extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'DatePicker Field',
                'class_name' => \DevGroup\DataStructure\propertyHandler\DatePickerField::class,
                'sort_order' => 6,
                'packed_json_default_config' => '[]',
            ]
        );
    }

    public function down()
    {
        $this->delete(
            '{{%property_handlers}}',
            [
                'class_name' => \DevGroup\DataStructure\propertyHandler\DatePickerField::class,
            ]
        );
    }

}
