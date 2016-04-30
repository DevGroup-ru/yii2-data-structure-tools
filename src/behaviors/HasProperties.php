<?php

namespace DevGroup\DataStructure\behaviors;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertyStorageHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\ServerErrorHttpException;

class HasProperties extends Behavior
{
    /** @var bool Should properties be automatically fetched after find */
    public $autoFetchProperties = false;

    /** @var bool Should properties be automatically saved when model saves */
    public $autoSaveProperties = false;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);
        /** @var \yii\db\ActiveRecord $owner */
        $owner = $this->owner;
        if ($owner->hasMethod('ensurePropertyGroupIds', false) === false) {
            throw new InvalidConfigException('Model class must has PropertiesTrait.');
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    /**
     * Performs auto fetching properties if it is turned on
     * @return bool
     */
    public function afterFind()
    {
        if ($this->autoFetchProperties === true) {
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
            $owner = $this->owner;
            $owner->ensurePropertyGroupIds();

            $models = [&$owner];
            PropertiesHelper::fillProperties($models);
        }
        return true;
    }

    /**
     * Deletes related properties from database
     * @return bool
     */
    public function beforeDelete()
    {

        // properties assigned to this record
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;
        //! @todo add check if this object doesn't has related properties that we wish to delete(lower db queries)
        $array = [&$owner];
        PropertiesHelper::deleteAllProperties($array);
        unset($array);

        return true;
    }

    /**
     * Returns if property is binded to model
     * @param string $key
     * @return bool
     */
    public function hasPropertyKey($key)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;
        $owner->ensurePropertiesAttributes();
        return in_array($key, $owner->propertiesAttributes);
    }

    /**
     * Checks if property can be read(binded to model)
     * @param string $name
     * @param bool|true $checkVars
     * @return bool
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if ($this->hasPropertyKey($name)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * Checks if property can be written(binded to model)
     * @param string $name
     * @param bool|true $checkVars
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->hasPropertyKey($name)) {
            return true;
        }

        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * Sets property value
     *
     * @param string $name
     * @param mixed $value
     * @throws Exception
     * @throws \Exception
     * @throws bool
     */
    public function __set($name, $value)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;

        $id = array_search($name, $owner->propertiesAttributes);
        if ($id === false) {
            throw new Exception("Property id for key $name not found");
        }

        $changed = true;
        if (isset($owner->propertiesValues[$id])) {
            if (is_array($value) === true) {
                if (is_array($owner->propertiesValues[$id]) === false) {
                    $owner->propertiesValues[$id] = (array) $owner->propertiesValues[$id];
                }
                $first = reset($owner->propertiesValues[$id]);
                $firstValue = reset($value);
                if (true === is_array($first) && true === is_array($firstValue)) {
                    $key = key($owner->propertiesValues[$id]);
                    $valueKey = key($value);
                    $diffCount = count(array_diff_assoc($first, $firstValue)) > 0;
                    $changed = $diffCount || count($first) != count($firstValue) || $key != $valueKey;
                } else {
                    $diffCount = count(array_diff_assoc($owner->propertiesValues[$id], $value)) > 0;
                    $changed = $diffCount || count($owner->propertiesValues[$id]) != count($value);
                }
            } else {
                $changed = $value != $owner->propertiesValues[$id];
            }
        }
        if ($changed === true) {
            $owner->propertiesValuesChanged = true;
            if (in_array($id, $owner->changedProperties) === false) {
                $owner->changedProperties[] = $id;
            }
        }
        $owner->propertiesValues[$id] = $value;
    }

    /**
     * Performs after insert stuff
     */
    public function afterInsert()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;
        $groups = $owner->propertyGroupIds;
        $owner->propertyGroupIds = null;

        if (count($groups) > 0) {
            foreach ($groups as $group_id) {
                /** @var PropertyGroup $group */
                $group = PropertyGroup::findOne(['id' => $group_id]);
                if ($group) {
                    $owner->addPropertyGroup($group);
                }
            }
        }

        $handlers = PropertyStorageHelper::getHandlersForModel($owner);
        $models = [&$owner];
        foreach ($handlers as $handler) {
            $handler->modelsInserted($models);
        }

        $this->afterSave();
    }

    /**
     * Performs after save stuff:
     * - saves dirty properties
     * - clears states of dirty properties
     */
    public function afterSave()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;

        if ($this->autoSaveProperties === true) {
            $models = [&$owner];
            PropertiesHelper::storeValues($models);
        }
        $owner->changedProperties = [];
        $owner->propertiesValuesChanged = false;

        // check for auto added property groups
        $owner->ensurePropertyGroupIds();
        $autoAddedGroups = PropertyGroup::getAutoAddedGroupsIds(
            PropertiesHelper::applicablePropertyModelId($owner->className())
        );

        foreach ($autoAddedGroups as $id) {
            if (!in_array($id, $owner->propertyGroupIds)) {
                /** @var PropertyGroup $propertyGroup */
                $propertyGroup = PropertyGroup::loadModel(
                    $id,
                    false,
                    true,
                    86400,
                    new ServerErrorHttpException("Property group with id $id not found"),
                    true
                );
                $owner->addPropertyGroup($propertyGroup);
            }
        }
    }

    /**
     * Returns property value
     * @param string $name
     * @return null
     * @throws Exception
     */
    public function __get($name)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;

        $id = array_search($name, $owner->propertiesAttributes);
        if ($id === false) {
            throw new Exception("Property id for key $name not found");
        }

        if (array_key_exists($id, $owner->propertiesValues)) {
            return $owner->propertiesValues[$id];
        } else {
            return null;
        }
    }
}
