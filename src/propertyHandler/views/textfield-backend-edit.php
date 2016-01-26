<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler $this
 */

use yii\widgets\ActiveForm;

echo (new ActiveForm())->field($model, $property->key);
