<?php

namespace DevGroup\DataStructure\behaviors;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

class HasProperties extends Behavior
{
    /** @var bool Should properties be automatically fetched after find */
    public $autoFetch = false;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);
        /** @var \yii\db\ActiveRecord $owner */
        $owner = $this->owner;
        if ($owner->hasMethod('deleteTableInheritanceRow', false) === false) {
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
        ];
    }

    public function afterFind()
    {
        if ($this->autoFetch === true) {
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


}