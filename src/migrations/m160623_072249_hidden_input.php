<?php

use DevGroup\DataStructure\propertyHandler\HiddenField;
use yii\db\Migration;

class m160623_072249_hidden_input extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Hidden field',
                'class_name' => HiddenField::class,
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
                'class_name' => HiddenField::class,
            ]
        );
    }

}
