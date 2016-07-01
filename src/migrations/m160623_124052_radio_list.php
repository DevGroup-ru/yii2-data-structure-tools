<?php

use DevGroup\DataStructure\propertyHandler\RadioList;
use yii\db\Migration;

class m160623_124052_radio_list extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Radio list',
                'class_name' => RadioList::class,
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
                'class_name' => RadioList::class,
            ]
        );
    }
}
