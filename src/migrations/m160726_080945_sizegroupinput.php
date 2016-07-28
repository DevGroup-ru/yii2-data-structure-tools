<?php

use DevGroup\DataStructure\propertyHandler\SizeGroupInput;
use yii\db\Migration;

class m160726_080945_sizegroupinput extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Size Group Input',
                'class_name' => SizeGroupInput::class,
                'sort_order' => 8,
                'packed_json_default_config' => '[]',
            ]
        );
    }

    public function down()
    {
        $this->delete(
            '{{%property_handlers}}',
            [
                'class_name' => SizeGroupInput::class,
            ]
        );
    }
}
