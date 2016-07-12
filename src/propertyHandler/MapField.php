<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\TableInheritance;

class MapField extends AbstractPropertyHandler
{
    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_SINGLE;

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_INVARIANT_STRING,
    ];

    /** @inheritdoc */
    public static $allowedStorage = [
        EAV::class,
        TableInheritance::class,
    ];

    /** @inheritdoc */
    public static $allowInSearch = false;

    public $key;

    public $latitude = 0;

    public $longitude = 0;

    public $zoom = 5;

    public $description = "";

    public $width = '95%';

    public $height = '300px';

    public $pattern = '{"latitude":"%latitude%","longitude":"%longitude%","zoom":"%zoom%", "description":"%description%"}';

    public $mapType = 'roadmap';

    public $animateMarker = false;

    public $alignMapCenter = true;

    public $enableSearchBar = true;



    public function getValidationRules(Property $property)
    {
        $key = $property->key;

        $rule = Property::dataTypeValidator($property->data_type) ?: 'safe';

        if ($property->allow_multiple_values) {
            return [
                [$key, 'each', 'rule' => [$rule]],
            ];
        } else {
            return [
                [$key, $rule],
            ];
        }
    }
}
