<?php

namespace DevGroup\DataStructure\traits;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

/**
 * Class PropertiesTrait adds properties-related functions to model
 *
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

    /** @var null|string[] Array of properties attributes names indexed by property ids */
    public $propertiesAttributes = null;

    /** @var array Array of properties values indexed by property id */
    public $propertiesValues = [];

    /** @var array Changed properties ids */
    public $changedProperties = [];

    /** @var bool If properties values were changed */
    public $propertiesValuesChanged = false;

    /**
     * Build a valid table name with suffix
     * @param string $suffix
     * @return mixed|string
     */
    protected static function buildTableName($suffix = '')
    {
        if (true === empty(static::$tablePrefix)) {
            if (strpos(static::tableName(), '}}') !== false) {
                $name = str_replace('}}', $suffix . '}}', static::tableName());
            } else {
                $name = static::tableName() . $suffix;
            }
        } else {
            $name = static::$tablePrefix . $suffix;
        }
        return $name;
    }

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
     *
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
     *
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
            $property = Property::findById($id);
            $key = $property->key;
            yield $key => $this->$key;
        }
    }

    /**
     * Static values bindings table name. Override this in your model class if needed.
     *
     * @return string
     */
    public static function staticValuesBindingsTable()
    {
        return static::buildTableName('_static_values');
    }

    /**
     * EAV table name. Override this in your model class if needed.
     *
     * @return string
     */
    public static function eavTable()
    {
        return static::buildTableName('_eav');
    }


    /**
     * Table inheritance table name. Override this in your model class if needed.
     *
     * @return string
     */
    public static function tableInheritanceTable()
    {
        return static::buildTableName('_properties');
    }

    /**
     * Binded property groups ids. Override this in your model class if needed.
     *
     * @return string
     */
    public static function bindedPropertyGroupsTable()
    {
        return static::buildTableName('_property_groups');
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
        $array = [&$this];
        return PropertiesHelper::bindGroupToModels($array, $propertyGroup);
    }

    /**
     * @param PropertyGroup $propertyGroup
     * @return bool
     */
    public function deletePropertyGroup(PropertyGroup $propertyGroup)
    {
        $array = [$this];
        return PropertiesHelper::unbindGroupFromModels($array, $propertyGroup);
    }

    /**
     * Array of validation rules for properties
     *
     * @return array
     */
    public function propertiesRules()
    {
        $rules = [];
        $this->ensurePropertiesAttributes();

        if (empty($this->propertiesIds) === false) {
            foreach ($this->propertiesIds as $propertyId) {
                /** @var Property $property */
                $property = Property::findById($propertyId);
                $handler = $property->handler();

                $rules = ArrayHelper::merge($rules, $handler->getValidationRules($property));
                if ($property->isRequired()) {
                    $rules = ArrayHelper::merge($rules, [[$property->key, 'required']]);
                }
            }
        }
        return $rules;
    }
}
