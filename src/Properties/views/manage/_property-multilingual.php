<?php

/**
 * @var yii\web\View                                $this
 * @var DevGroup\DataStructure\models\Property      $model
 * @var yii\widgets\ActiveForm                      $form
 * @var integer                                     $language_id
 * @var \DevGroup\Multilingual\models\Language      $language
 * @var string                                      $attributePrefix
 */

use yii\helpers\Url;

?>
<?= $form->field($model, $attributePrefix.'name') ?>
<?= $form->field($model, $attributePrefix.'description')->textarea() ?>

<!-- end -->