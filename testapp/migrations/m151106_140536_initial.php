<?php

use yii\db\Migration;

class m151106_140536_initial extends Migration
{
    public function up()
    {
        mb_internal_encoding("UTF-8");
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable(
            '{{%product}}',
            [
                'id' => $this->primaryKey(),
                'price' => $this->decimal(10, 2),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
                'packed_json_data' => $this->text(),
            ],
            $tableOptions
        );

        $this->createTable(
            '{{%product_translation}}',
            [
                'model_id' => $this->integer()->notNull(),
                'language_id' => $this->integer()->notNull(),
                'name' => $this->string(),
            ],
            $tableOptions
        );
        $this->createIndex('uniqPair', '{{%product_translation}}', ['model_id', 'language_id'], true);
        $this->addForeignKey(
            'prodTransl',
            '{{%product_translation}}',
            ['model_id'],
            '{{%product}}',
            ['id'],
            'CASCADE'
        );

        $this->insert(
            '{{%product}}',
            [
                'price' => 99.90,
            ]
        );
        $this->insert(
            '{{%product_translation}}',
            [
                'model_id' => 1,
                'language_id' => 1,
                'name' => 'iPhone 5',
            ]
        );
        $this->insert(
            '{{%product_translation}}',
            [
                'model_id' => 1,
                'language_id' => 2,
                'name' => 'айФон 5',
            ]
        );

        \DevGroup\DataStructure\helpers\PropertiesTableGenerator::getInstance()->generate(app\models\Product::className());

    }

    public function down()
    {
        $this->dropTable('{{%product_translation}}');
        $this->dropTable('{{%product}}');
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
