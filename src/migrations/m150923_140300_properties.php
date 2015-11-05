<?php

use yii\db\Migration;

/**
 * Base migration for properies
 */
class m150923_140300_properties extends Migration
{
    /**
     * Applies migrations
     */
    public function up()
    {
        mb_internal_encoding("UTF-8");
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%property}}', [
            'id' => $this->primaryKey(),
            'applicable_property_model_id' => $this->integer()->notNull(),
            'key' => $this->string(80)->notNull(),
            'data_type' => $this->boolean()->defaultValue(0),
            'is_internal' => $this->boolean()->defaultValue(0),
            'allow_multiple_values' => $this->boolean()->defaultValue(0),
            'storage_id' => $this->integer()->notNull(),
            'packed_json_default_value' => $this->text()->notNull(),
            'property_handler_id' => $this->integer()->notNull(),
//
//
//          This config can not be applied directly as handlers are singleton!
//          Therefore we need to split configs into several cases???
//
//               Or maybe it is useless
//
//            'packed_json_handler_config' => $this->text()->notNull(),
//
//
//
        ], $tableOptions);
        $this->createIndex('iKey', '{{%property}}', ['key'], true);

        $this->createTable('{{%static_value}}', [
            'id' => $this->primaryKey(),
            'property_id' => $this->integer()->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->createTable('{{%property_group}}', [
            'id' => $this->primaryKey(),
            'internal_name' => $this->string()->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'applicable_property_model_id' => $this->integer()->notNull(),
            'is_auto_added' => $this->boolean()->defaultValue(0),
        ], $tableOptions);

        $this->createIndex('by_models', '{{%property_group}}', ['applicable_property_model_id']);

        $this->createTable('{{%property_property_group}}', [
            'property_id' => $this->integer()->notNull(),
            'property_group_id' => $this->integer()->notNull(),
            'sort_order_property_groups' => $this->integer()->notNull()->defaultValue(0),
            'sort_order_group_properties' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->addPrimaryKey('pk', '{{%property_property_group}}', ['property_id', 'property_group_id']);

        $this->createTable('{{%property_storage}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'class_name' => $this->string()->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->createTable('{{%applicable_property_models}}', [
            'id' => $this->primaryKey(),
            'class_name' => $this->string()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%property_handlers}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'class_name' => $this->string()->notNull(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'packed_json_default_config' => $this->text()->notNull(),
        ]);

        // translations
        $this->createTable('{{%property_translation}}', [
            'model_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'description' => $this->string()->notNull()->defaultValue(''),
        ], $tableOptions);

        $this->createIndex('pk', '{{%property_translation}}', ['model_id', 'language_id'], true);
        $this->addForeignKey(
            'fkPrT',
            '{{%property_translation}}',
            ['model_id'],
            '{{%property}}',
            ['id'],
            'CASCADE'
        );

        $this->createTable('{{%static_value_translation}}', [
            'model_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'description' => $this->string()->notNull()->defaultValue(''),
            'slug' => $this->string(80)->notNull(),
        ], $tableOptions);
        $this->createIndex('pk', '{{%static_value_translation}}', ['model_id', 'language_id'], true);
        $this->createIndex('slug', '{{%static_value_translation}}', ['slug']);
        $this->addForeignKey(
            'fkSvT',
            '{{%static_value_translation}}',
            ['model_id'],
            '{{%static_value}}',
            ['id'],
            'CASCADE'
        );

        $this->createTable('{{%property_group_translation}}', [
            'model_id' => $this->integer()->notNull(),
            'language_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
        ], $tableOptions);

        $this->createIndex('pk', '{{%property_group_translation}}', ['model_id', 'language_id'], true);
        $this->addForeignKey(
            'fkPgT',
            '{{%property_group_translation}}',
            ['model_id'],
            '{{%property_group}}',
            ['id'],
            'CASCADE'
        );

        // insert default data
        $this->insert('{{%property_handlers}}', [
            'name' => 'Static values',
            'class_name' => 'DevGroup\DataStructure\propertyHandler\StaticValues',
            'sort_order' => 2,
            'packed_json_default_config' => '[]',
        ]);
        $this->insert('{{%property_handlers}}', [
            'name' => 'Text field',
            'class_name' => 'DevGroup\DataStructure\propertyHandler\TextField',
            'sort_order' => 1,
            'packed_json_default_config' => '[]',
        ]);

        $this->insert('{{%property_storage}}', [
            'name' => 'Static values',
            'class_name' => 'DevGroup\DataStructure\propertyStorage\StaticValues',
            'sort_order' => 2,
        ]);
        $this->insert('{{%property_storage}}', [
            'name' => 'EAV',
            'class_name' => 'DevGroup\DataStructure\propertyStorage\EAV',
            'sort_order' => 1,
        ]);
        $this->insert('{{%property_storage}}', [
            'name' => 'Table Inheritance',
            'class_name' => 'DevGroup\DataStructure\propertyStorage\TableInheritance',
            'sort_order' => 3,
        ]);
    }

    /**
     * Removes all properties related tables
     */
    public function down()
    {
        $this->dropTable('{{%property_property_group}}');

        $this->dropTable('{{%property_translation}}');
        $this->dropTable('{{%property}}');
        $this->dropTable('{{%property_group_translation}}');
        $this->dropTable('{{%property_group}}');

        $this->dropTable('{{%property_storage}}');

        $this->dropTable('{{%static_value_translation}}');
        $this->dropTable('{{%static_value}}');

        $this->dropTable('{{%applicable_property_models}}');
        $this->dropTable('{{%property_handlers}}');
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
