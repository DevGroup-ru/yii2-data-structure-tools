<?php

use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyHandlers;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValue;
use yii\db\Migration;

class m160322_075751_foreign_keys extends Migration
{
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $schema = $this->db->schema->getTableSchema($table);
        $table = trim($this->db->quoteSql($table), '`');
        $refTable = trim($this->db->quoteSql($refTable), '`');
        foreach ($schema->foreignKeys as $foreignKey) {
            foreach ($foreignKey as $col => $refCol) {
                if ($col === 0) {
                    if ($refCol !== $refTable) {
                        continue 2;
                    } elseif ($refCol !== $table) {
                        continue;
                    }
                }
                if ($col === $columns && $refCol === $refColumns) {
                    return;
                }
            }
        }
        parent::addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
    }

    public function up()
    {
        $propertyIds = Property::find()->select('id')->column();
        $staticValueIds = StaticValue::find()->select('id')->column();
        foreach (ApplicablePropertyModels::find()->select('class_name')->column() as $class) {
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $class */
            $this->delete(
                $class::eavTable(),
                ['not in', 'property_id', $propertyIds]
            );
            $this->delete(
                $class::staticValuesBindingsTable(),
                ['not in', 'static_value_id', $staticValueIds]
            );
            $this->addForeignKey(
                PropertiesTableGenerator::getForeignKeyName($class::eavTable(), Property::tableName(), $this->db),
                $class::eavTable(),
                'property_id',
                Property::tableName(),
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                PropertiesTableGenerator::getForeignKeyName($class::staticValuesBindingsTable(), StaticValue::tableName(), $this->db),
                $class::staticValuesBindingsTable(),
                'static_value_id',
                StaticValue::tableName(),
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
        $this->delete(
            StaticValue::tableName(),
            ['not in', 'property_id', $propertyIds]
        );
        $this->addForeignKey(
            PropertiesTableGenerator::getForeignKeyName(StaticValue::tableName(), Property::tableName(), $this->db),
            StaticValue::tableName(),
            'property_id',
            Property::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->delete(
            PropertyGroup::tableName(),
            ['not in', 'applicable_property_model_id', ApplicablePropertyModels::find()->select('id')->column()]
        );
        $this->addForeignKey(
            PropertiesTableGenerator::getForeignKeyName(PropertyGroup::tableName(), ApplicablePropertyModels::tableName(), $this->db),
            PropertyGroup::tableName(),
            'applicable_property_model_id',
            ApplicablePropertyModels::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->delete(
            Property::tableName(),
            ['not in', 'storage_id', PropertyStorage::find()->select('id')->column()]
        );
        $this->addForeignKey(
            PropertiesTableGenerator::getForeignKeyName(Property::tableName(), PropertyStorage::tableName(), $this->db),
            Property::tableName(),
            'storage_id',
            PropertyStorage::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->delete(
            Property::tableName(),
            ['not in', 'property_handler_id', PropertyHandlers::find()->select('id')->column()]
        );
        $this->addForeignKey(
            PropertiesTableGenerator::getForeignKeyName(Property::tableName(), PropertyHandlers::tableName(), $this->db),
            Property::tableName(),
            'property_handler_id',
            PropertyHandlers::tableName(),
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey(
            PropertiesTableGenerator::getForeignKeyName(Property::tableName(), PropertyHandlers::tableName(), $this->db),
            Property::tableName()
        );
        $this->dropForeignKey(
            PropertiesTableGenerator::getForeignKeyName(Property::tableName(), PropertyStorage::tableName(), $this->db),
            Property::tableName()
        );
        $this->dropForeignKey(
            PropertiesTableGenerator::getForeignKeyName(PropertyGroup::tableName(), ApplicablePropertyModels::tableName(), $this->db),
            PropertyGroup::tableName()
        );
        $this->dropForeignKey(
            PropertiesTableGenerator::getForeignKeyName(StaticValue::tableName(), Property::tableName(), $this->db),
            StaticValue::tableName()
        );
        foreach (ApplicablePropertyModels::find()->select('class_name')->column() as $class) {
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $class */
            try {
                $this->dropForeignKey(
                    PropertiesTableGenerator::getForeignKeyName($class::eavTable(), Property::tableName(), $this->db),
                    $class::eavTable()
                );
            } catch (Exception $e) {}
            try {
                $this->dropForeignKey(
                    PropertiesTableGenerator::getForeignKeyName($class::staticValuesBindingsTable(), StaticValue::tableName(), $this->db),
                    $class::staticValuesBindingsTable()
                );
            } catch (Exception $e) {}
        }
    }
}
