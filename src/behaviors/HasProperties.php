<?php

namespace DevGroup\DataStructure\behaviors;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

class HasProperties extends Behavior
{
    /** @var bool Should properties be automatically fetched after find */
    public $autoFetchProperties = false;

    /** @var bool Should properties be automatically saved when model saves  */
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
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    public function afterFind()
    {
        if ($this->autoFetchProperties === true) {
            //! @todo fetch here
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
            $owner = $this->owner;
            $owner->ensurePropertyGroupIds();
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

    private function hasPropertyKey($key)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;
        $owner->ensurePropertiesAttributes();
        return in_array($key, $owner->propertiesAttributes);
    }

    public function canGetProperty($name, $checkVars = true)
    {

        if ($this->hasPropertyKey($name)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->hasPropertyKey($name)) {
            return true;
        }

        return parent::canSetProperty($name, $checkVars);
    }

    public function __set($name, $value)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;
        $owner->ensurePropertiesAttributes();

        $id = array_search($name, $owner->propertiesAttributes);
        if ($id === false) {
            throw new Exception("Property id for key $name not found");
        }
        if (is_array($value) === false) {
            /** @var Property $property */
            $property = Property::loadModel(
                $id,
                false,
                true,
                86400,
                new Exception("Property for id $id not found"),
                true
            );
            if ($property->allow_multiple_values === true) {
                // convert value to array for multiple property!
                $value = [$value];
            }
        }

        $changed = true;
        if (isset($owner->propertiesValues[$id])) {
            if (is_array($value) === true) {
                $diffCount = count(array_diff_assoc($owner->propertiesValues[$id], $value)) > 0;
                $changed = $diffCount || count($owner->propertiesValues[$id]) != count($value);
            } else {
                $changed = $value !== $owner->propertiesValues[$id];
            }
        }
        if ($changed === true) {
            $owner->propertiesValuesChanged = true;
            $owner->changedProperties[] = $id;
        }

        $owner->propertiesValues[$id] = $value;
    }

    public function afterSave()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;
        $owner->changedProperties = [];
        $owner->propertiesValuesChanged = false;
    }

    public function __get($name)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
        $owner = $this->owner;
        $owner->ensurePropertiesAttributes();

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
