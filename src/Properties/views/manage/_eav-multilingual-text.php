<?php

/**
 * @var yii\web\View $this
 * @var DevGroup\DataStructure\models\StaticValue $model
 * @var yii\widgets\ActiveForm $form
 * @var integer $language_id
 * @var \DevGroup\Multilingual\models\Language $language
 * @var string $attributePrefix
 */

use yii\helpers\Url;

?>
<?= $form->field($model, $attributePrefix . 'text')->textarea(['rows' => 7]) ?>

<!-- end -->