<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */


use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

if ($property->allow_multiple_values == 1) : ?>
    <?php
    $values = (array) $model->{$property->key};
    if (count($values) === 0) {
        $values = [''];
    } else {
        array_walk(
            $values,
            function (&$item) {
                if (is_array($item)) {
                    $item = implode(',', $item);
                }
            }
        );
    }
    echo Html::ul($values);
    ?>
<?php else : ?>
    <?= Html::tag('span', $model->{$property->key}) ?>
<?php endif;