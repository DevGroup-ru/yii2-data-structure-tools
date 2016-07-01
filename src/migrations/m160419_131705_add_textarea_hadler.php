<?php

use DevGroup\DataStructure\propertyHandler\TextArea;
use yii\db\Migration;

class m160419_131705_add_textarea_hadler extends Migration
{
    public function up()
    {
        $this->insert('{{%property_handlers}}', [
            'name' => 'Text area',
            'class_name' => TextArea::class,
            'sort_order' => 5,
            'packed_json_default_config' => '[]',
        ]);
    }

    public function down()
    {
        $this->delete('{{%property_handlers}}', [
            'class_name' => TextArea::class
        ]);
    }
}
