<?php

namespace DevGroup\DataStructure\traits;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use yii\web\ServerErrorHttpException;

trait PropertiesTrait
{
    /**
     * @var null|integer[] Array of property_group_ids
     */
    public $property_group_ids = null;

    /** @var null|string[] Array of properties attributes names  */
    public $propertiesAttributes = null;

    /** @var null|integer[] Array of properties ids */
    public $propertiesIds = null;

    /**
     * Ensures that $property_group_ids is retrieved
     */
    public function ensurePropertyGroupIds()
    {
        if ($this->property_group_ids === null) {
            $array = [&$this];
            PropertiesHelper::fillPropertyGroups($array);
        }
    }

    /**
     * Ensures that $propertiesIds is filled. Also fills $property_group_ids if needed.
     */
    public function ensurePropertiesIds()
    {
        if ($this->propertiesIds === null) {
            $this->ensurePropertyGroupIds();
            $this->propertiesIds = [];

            foreach ($this->property_group_ids as $property_group_id) {
                $property_ids = PropertyGroup::propertyIdsForGroup($property_group_id);
                foreach ($property_ids as $property_id) {
                    $this->propertiesIds[] = $property_id;
                }
            }
            $this->propertiesIds = array_unique($this->propertiesIds);
        }
    }

    /**
     * Ensures that $propertiesAttributes is filled. Also fills $propertiesIds and $property_group_ids if needed.
     */
    public function ensurePropertiesAttributes()
    {
        if ($this->propertiesAttributes === null) {
            $this->ensurePropertiesIds();
            $this->propertiesAttributes = [];

            foreach ($this->propertiesIds as $property_id) {
                $this->propertiesAttributes[] = Property::propertyKeyForId($property_id);
            }
        }
    }

    public function iteratePropertyGroups()
    {
        foreach ($this->property_group_ids as $id) {
            yield PropertyGroup::loadModel(
                $id,
                false,
                true,
                86400,
                new ServerErrorHttpException("Property Group with id $id not found"),
                true
            );
        }
    }

    public function iterateGroupProperties($property_group_id)
    {
        $property_ids = PropertyGroup::propertyIdsForGroup($property_group_id);
        foreach ($property_ids as $id) {
            $property = Property::loadModel(
                $id,
                false,
                true,
                86400,
                new ServerErrorHttpException("Property with id $id not found"),
                true
            );
            $key = $property->key;
            yield $key => $this->$key;
        }
    }

    /**
     * @return string Static values bindings table name. Override this in your model class if needed.
     */
    public static function static_values_bindings_table()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_static_values';
    }

    /**
     * @return string EAV table name. Override this in your model class if needed.
     */
    public static function eav_table()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_eav';
    }

    /**
     * @return string Table inheritance table name. Override this in your model class if needed.
     */
    public static function table_inheritance_table()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_properties';
    }

    /**
     * @return string Binded property groups ids. Override this in your model class if needed.
     */
    public static function binded_property_groups_table()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_property_groups';
    }



}