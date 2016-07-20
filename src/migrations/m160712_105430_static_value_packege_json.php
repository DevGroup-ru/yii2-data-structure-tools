<?php

use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\propertyHandler\ColorHandler;
use yii\db\Migration;

class m160712_105430_static_value_packege_json extends Migration
{
    public function up()
    {
        $this->addColumn(
            StaticValue::tableName(),
            'packed_json_params',
            $this->text()->notNull()
        );
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Color',
                'class_name' => ColorHandler::class,
                'sort_order' => 8,
                'packed_json_default_config' => '[]',
            ]
        );

    }

    public function down()
    {
        $this->dropColumn(
            StaticValue::tableName(),
            'packed_json_params'
        );

        $this->delete(
            '{{%property_handlers}}',
            [
                'class_name' => ColorHandler::class,
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
