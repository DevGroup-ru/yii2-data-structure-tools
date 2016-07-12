<?php


namespace DevGroup\DataStructure\propertyHandler;


use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;

class RadioList extends AbstractPropertyHandler
{
    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_ALL;

    /** @inheritdoc */
    public static $allowedStorage = [
        StaticValues::class,
    ];

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_STRING,
        Property::DATA_TYPE_INTEGER,
        Property::DATA_TYPE_FLOAT,
        Property::DATA_TYPE_TEXT,
        Property::DATA_TYPE_PACKED_JSON,
        Property::DATA_TYPE_BOOLEAN,
        Property::DATA_TYPE_INVARIANT_STRING,
    ];

    /** @inheritdoc */
    public static $allowInSearch = true;

    /**
     * Get validation rules for a property.
     *
     * @param Property $property
     *
     * @return array of ActiveRecord validation rules
     */
    public function getValidationRules(Property $property)
    {
        $key = $property->key;
        if ($property->allow_multiple_values) {
            return [
                [$key, 'each', 'skipOnEmpty' => true, 'rule' => ['filter', 'filter' => 'intval']],
                $this->existenceValidation($property),
            ];
        } else {
            return [
                [$key, 'filter', 'skipOnEmpty' => true, 'filter' => 'intval'],
                $this->existenceValidation($property),
            ];
        }
    }

    /**
     * @param Property $property
     *
     * @return array Validation rule for checking existence of static_value row with specified ID
     *
     * @warning If we are updating multiple models properties at once - we get lots of queries to db(1 for each model
     *     in array)
     */
    private function existenceValidation(Property $property)
    {
        $key = $property->key;

        return [
            $key,
            'exist',
            'targetClass' => StaticValue::className(),
            'targetAttribute' => 'id',
            'allowArray' => $property->allow_multiple_values,
            'filter' => ['property_id' => $property->id],
            'skipOnEmpty' => true,
        ];

    }
}