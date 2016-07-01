<?php

namespace DevGroup\DataStructure\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\Json;

class PackedJsonAttributes extends Behavior
{
    /** @var string[] name of packed attributes without prefix */
    private $_packedAttributes = null;
    public $prefix = 'packed_json_';
    private $_unpackedValues = [];

    /**
     * @var bool Set real value to empty array on null
     */
    public $forceEmptyArray = true;
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'unpackAttributes',
            ActiveRecord::EVENT_BEFORE_INSERT => 'packAttributes',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'packAttributes',
        ];
    }

    /**
     * @return string[]
     */
    private function getPackedAttributes()
    {
        if ($this->_packedAttributes === null) {
            /** @var ActiveRecord $owner */
            $owner = $this->owner;
            $allAttributes = array_keys($owner->getAttributes());
            $this->_packedAttributes = array_reduce(
                $allAttributes,
                function ($carry, $item) {
                    if (strpos($item, $this->prefix) === 0) {
                        $carry[] = substr($item, strlen($this->prefix));
                    }
                    return $carry;
                },
                []
            );
        }
        return $this->_packedAttributes;
    }

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $this->getPackedAttributes();
    }

    /**
     * Returns attribute name with prefix
     *
     * @param $attribute
     *
     * @return string
     */
    public function addPrefix($attribute)
    {
        return $this->prefix . $attribute;
    }

    public function unpackAttributes()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        $this->_unpackedValues = [];

        foreach ($this->_packedAttributes as $attribute) {
            $json = $owner->getAttribute($this->addPrefix($attribute));
            if (empty($json)) {
                $json = null;
            } else {
                $json = Json::decode($json);
            }
            $this->_unpackedValues[$attribute] = $json;
        }
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->_packedAttributes) ?: parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->_packedAttributes) ?: parent::canSetProperty($name, $checkVars);
    }

    public function __get($name)
    {
        if (in_array($name, $this->_packedAttributes)) {
            if (array_key_exists($name, $this->_unpackedValues) === false) {
                $this->unpackAttributes();
            }
            return $this->_unpackedValues[$name];
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->_packedAttributes)) {
            $this->_unpackedValues[$name] = $value;
            return;
        }
        parent::__set($name, $value);
    }

    public function packAttributes()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;


        if ($this->forceEmptyArray === true) {
            // set unset attributes to empty array
            foreach ($this->_packedAttributes as $attribute) {
                if (!isset($this->_unpackedValues[$attribute])) {
                    $this->_unpackedValues[$attribute] = [];
                }
            }
        }

        // pack all attributes to JSON string
        foreach ($this->_unpackedValues as $attribute => $value) {
            if (empty($value) && $this->forceEmptyArray === true) {
                $value = [];
            }
            $owner->setAttribute($this->addPrefix($attribute), Json::encode($value));
        }
    }
}
