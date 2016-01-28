<?php

use DevGroup\DataStructure\propertyHandler\MapField;
use yii\db\Migration;

class m160128_122745_mapField extends Migration
{
    public function up()
    {
        // insert default data
        $this->insert('{{%property_handlers}}', [
            'name' => 'Map Field',
            'class_name' => MapField::className(),
            'sort_order' => 3,
            'packed_json_default_config' => '[]',
        ]);

    }

    public function down()
    {
        $this->delete(
            '{{%property_handlers}}',
            [
                'class_name' => MapField::className()
            ]
        );
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
