<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;

class MapField  extends AbstractPropertyHandler
{

    public $key;

    public $latitude = 0;

    public $longitude = 0;

    public $zoom = 5;

    public $width = '95%';

    public $height = '300px';

    public $pattern = '{%latitude%,%longitude%,%zoom%}';

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