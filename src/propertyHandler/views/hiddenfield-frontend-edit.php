<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;


if ($property->allow_multiple_values == 1) : ?>
    <?php
    $inputName = Html::getInputName($model, $property->key) . '[]';
    $inputId = Html::getInputId($model, $property->key);
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
    ?>
    <?php foreach ($values as $index => $value) : ?>

        <?= Html::hiddenInput(
            $inputName,
            $value,
            [
                'class' => 'form-control',
                'id' => $inputId . '-' . $index,
                'name' => $inputName,
            ]
        ) ?>

    <?php endforeach; ?>

<?php else : ?>
    <?= $form->field($model, $property->key)->hiddenInput()->label(false); ?>
<?php endif;