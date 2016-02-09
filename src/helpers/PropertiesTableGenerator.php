<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\PropertyGroup;
use Yii;
use yii\db\Migration;

/**
 * Class PropertiesTableGenerator is a special helper for manipulating(creating/dropping) properties related tables
 * for exact models.
 *
 * @package DevGroup\DataStructure\helpers
 */
class PropertiesTableGenerator extends Migration
{
    /**
     * @var PropertiesTableGenerator
     */
    public static $instance = null;

    /**
     * @codeCoverageIgnore
     * @return PropertiesTableGenerator
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param \yii\db\Connection|string|null  $db
     * @codeCoverageIgnore
     */
    private function setDb($db)
    {
        if ($db === null) {
            $db = Yii::$app->db;
        } elseif (is_string($db)) {
            $db = Yii::$app->get($db);
        }
        $this->db = $db;
    }

    /**
     * Generates all properties tables for specified $className model
     * @param string                          $className
     * @param \yii\db\Connection|string|null  $db
     */
    public function generate($className, $db = null)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $className */
        $this->setDb($db);

        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB'
            : null;

        // Static values
        $staticValuesTable = $className::staticValuesBindingsTable();

        $this->createTable(
            $staticValuesTable,
            [
                'model_id' => $this->integer()->notNull(),
                'static_value_id' => $this->integer()->notNull(),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
            ],
            $tableOptions
        );

        $this->addPrimaryKey('uniquePair', $staticValuesTable, ['model_id', 'static_value_id']);
        $this->addForeignKey(
            'fk'.crc32($className).'SV',
            $staticValuesTable,
            ['model_id'],
            $className::tableName(),
            ['id'],
            'CASCADE'
        );


        // eav!
        $eavTable = $className::eavTable();
        $this->createTable(
            $eavTable,
            [
                'id' => $this->primaryKey(),
                'model_id' => $this->integer()->notNull(),
                'property_id' => $this->integer()->notNull(),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
                'value_integer' => $this->integer()->notNull()->defaultValue(0),
                'value_float' => $this->float()->notNull()->defaultValue(0),
                'value_string' => $this->string(),
                'value_text' => $this->text(),
            ],
            $tableOptions
        );
        $this->createIndex(
            'model_properties',
            $eavTable,
            [
                'model_id',
                'sort_order',
            ]
        );
        $this->addForeignKey(
            'fk'.crc32($className).'Eav',
            $eavTable,
            ['model_id'],
            $className::tableName(),
            ['id'],
            'CASCADE'
        );

        // table inheritance
        $tableInheritanceTable = $className::tableInheritanceTable();
        $this->createTable(
            $tableInheritanceTable,
            [
                'model_id' => $this->primaryKey(),
            ],
            $tableOptions
        );
        $this->addForeignKey(
            'fk'.crc32($className).'TI',
            $tableInheritanceTable,
            ['model_id'],
            $className::tableName(),
            ['id'],
            'CASCADE'
        );

        // binded property groups
        $bindedGroupsTable = $className::bindedPropertyGroupsTable();
        $this->createTable(
            $bindedGroupsTable,
            [
                'model_id' => $this->integer()->notNull(),
                'property_group_id' => $this->integer()->notNull(),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
            ],
            $tableOptions
        );
        $this->createIndex(
            'uniquePair',
            $bindedGroupsTable,
            [
                'model_id',
                'property_group_id',
            ],
            true
        );
        $this->addForeignKey(
            'fk'.crc32($className).'BPG',
            $bindedGroupsTable,
            ['model_id'],
            $className::tableName(),
            ['id'],
            'CASCADE'
        );
        $this->addForeignKey(
            'fk'.crc32($className).'BPG1',
            $bindedGroupsTable,
            ['property_group_id'],
            PropertyGroup::tableName(),
            ['id'],
            'CASCADE'
        );
        $this->insert(
            ApplicablePropertyModels::tableName(),
            [
                'class_name' => $className,
                'name' => substr($className, strrpos($className, '\\') + 1),
            ]
        );
    }

    /**
     * @param string                          $className
     * @param \yii\db\Connection|string|null  $db
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function drop($className, $db = null)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $className */
        $this->setDb($db);

        $tables = [
            $className::staticValuesBindingsTable(),
            $className::eavTable(),
            $className::tableInheritanceTable(),
            $className::bindedPropertyGroupsTable(),
        ];

        foreach ($tables as $table) {
            $this->dropTable($table);
        }
        $this->delete(
            ApplicablePropertyModels::tableName(),
            [
                'class_name' => $className,
            ]
        );
    }

    /**
     * @return \yii\db\Connection the database connection to be used for schema building.
     */
    protected function getDb()
    {
        return $this->db;
    }

}
