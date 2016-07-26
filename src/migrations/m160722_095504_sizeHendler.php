<?php

use DevGroup\DataStructure\propertyHandler\MeasureInput;
use yii\db\Migration;

class m160722_095504_sizeHendler extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Measure Input',
                'class_name' => MeasureInput::class,
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
                'class_name' => MeasureInput::class,
            ]
        );
    }

}
