<?php

use yii\db\Migration;

class m160624_130451_masked_input extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Masked input',
                'class_name' => \DevGroup\DataStructure\propertyHandler\MaskedInput::class,
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
                'class_name' => \DevGroup\DataStructure\propertyHandler\MaskedInput::class,
            ]
        );
    }
}
