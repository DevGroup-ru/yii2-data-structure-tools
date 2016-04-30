<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */

use yii\widgets\ActiveForm;

?>
<?= $form->field($model, $property->key)->textarea(['rows' => 7]) ?>
