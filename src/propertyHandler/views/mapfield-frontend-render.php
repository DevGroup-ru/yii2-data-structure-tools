<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var DevGroup\DataStructure\propertyHandler\MapField $this ->context
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use hector68\yii2\assets\MapInputAsset;
use yii\helpers\Html;
use yii\helpers\Json;

MapInputAsset::register($this);

echo Html::tag(
    'div',
    '',
    [
        'id' => $this->context->getId(),
        'style' => [
            'width' => $this->context->width,
            'height' => $this->context->height
        ]
    ]
);

try {
    $data = Json::decode($model->{$property->key});
} catch (Exception $e) {
    $data = null;
}

if ($data !== null) {
    $this->registerJs(<<<JAVASCRIPT
var myLatlng = new google.maps.LatLng({$data['latitude']},{$data['longitude']});
var mapOptions = {
  zoom: {$data['zoom']},
  center: myLatlng
}
var map = new google.maps.Map(document.getElementById("{$this->context->getId()}"), mapOptions);

var marker = new google.maps.Marker({
    position: myLatlng,
});
// To add the marker to the map, call setMap();
marker.setMap(map);
JAVASCRIPT
    );
}