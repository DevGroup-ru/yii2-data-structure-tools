<?php

use yii\db\Migration;

class m160628_070816_wysiwyg extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Wysiwyg Field',
                'class_name' => \DevGroup\DataStructure\propertyHandler\WysiwygField::class,
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
                'class_name' => \DevGroup\DataStructure\propertyHandler\WysiwygField::class,
            ]
        );
    }
}
