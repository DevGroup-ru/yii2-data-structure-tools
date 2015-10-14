<?php

namespace DevGroup\DataStructure\traits;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use yii\web\ServerErrorHttpException;

/**
 * Class PropertiesTrait
 * @package DevGroup\DataStructure\traits
 * @mixin \yii\db\ActiveRecord
 * @property integer $id
 */
trait PropertiesTrait
{
    /**
     * @var null|integer[] Array of property_group_ids
     */
    public $propertyGroupIds = null;

    /** @var null|integer[] Array of properties ids */
    public $propertiesIds = null;

    /** @var null|string[] Array of properties attributes names indexed by property ids  */
    public $propertiesAttributes = null;

    /** @var array Array of properties values indexed by property id */
    public $propertiesValues = [];

    /** @var array Changed properties ids */
    public $changedProperties = [];

    /** @var bool If properties values were changed */
    public $propertiesValuesChanged = false;

    /**
     * Ensures that $property_group_ids is retrieved
     */
    public function ensurePropertyGroupIds()
    {
        if ($this->propertyGroupIds === null) {
            $array = [&$this];
            PropertiesHelper::fillPropertyGroups($array);
        }
    }

    /**
     * Ensures that $propertiesIds is filled. Also fills $property_group_ids if needed.
     * @param boolean $force True to force refreshing properties ids even if they are filled.
     */
    public function ensurePropertiesIds($force = false)
    {
        if ($this->propertiesIds === null || $force === true) {
            $this->ensurePropertyGroupIds();
            $this->propertiesIds = [];

            foreach ($this->propertyGroupIds as $propertyGroupId) {
                $propertyIds = PropertyGroup::propertyIdsForGroup($propertyGroupId);
                foreach ($propertyIds as $propertyId) {
                    $this->propertiesIds[] = $propertyId;
                }
            }
            $this->propertiesIds = array_unique($this->propertiesIds);
        }
    }

    /**
     * Ensures that $propertiesAttributes is filled. Also fills $propertiesIds and $property_group_ids if needed.
     * @param boolean $force True to force refreshing propertiesAttributes even if they are filled.
     */
    public function ensurePropertiesAttributes($force = false)
    {
        if ($this->propertiesAttributes === null || $force === true) {
            $this->ensurePropertiesIds($force);
            $this->propertiesAttributes = [];

            foreach ($this->propertiesIds as $propertyId) {
                $this->propertiesAttributes[$propertyId] = Property::propertyKeyForId($propertyId);
            }
        }
    }

    /**
     * @return \Generator
     * @throws \Exception
     */
    public function iteratePropertyGroups()
    {
        $this->ensurePropertyGroupIds();
        foreach ($this->propertyGroupIds as $id) {
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

    /**
     * @param int $propertyGroupId
     *
     * @return \Generator
     * @throws \Exception
     */
    public function iterateGroupProperties($propertyGroupId)
    {
        $propertyIds = PropertyGroup::propertyIdsForGroup($propertyGroupId);
        foreach ($propertyIds as $id) {
            /** @var Property $property */
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
    public static function staticValuesBindingsTable()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_static_values';
    }

    /**
     * @return string EAV table name. Override this in your model class if needed.
     */
    public static function eavTable()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_eav';
    }

    /**
     * @return string Table inheritance table name. Override this in your model class if needed.
     */
    public static function tableInheritanceTable()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_properties';
    }

    /**
     * @return string Binded property groups ids. Override this in your model class if needed.
     */
    public static function bindedPropertyGroupsTable()
    {
        $baseTableName = trim(static::tableName(), '{}%');
        return $baseTableName . '_property_groups';
    }

    /**
     * Adds property group to current model instance.
     *
     * @param PropertyGroup $propertyGroup
     *
     * @return bool Result of adding
     * @throws \yii\db\Exception
     */
    public function addPropertyGroup(PropertyGroup $propertyGroup)
    {
        $array = [ &$this ];
        return PropertiesHelper::bindGroupToModels($array, $propertyGroup);
    }
}
