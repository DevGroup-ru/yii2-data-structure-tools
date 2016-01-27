<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler $widget
 * @var \DevGroup\DataStructure\models\Property $property
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */

use yii\widgets\ActiveForm;
use yii\helpers\Html;

?>
<?php if ($property->allow_multiple_values == 1): ?>
    <?php
    $inputName = Html::getInputName($model, $property->key) . '[]';
    $inputId = Html::getInputId($model, $property->key);
    $values = (array) $model->{$property->key};
    if (count($values) === 0) {
        $values = [''];
    }
    ?>
    <div class="m-form__col multi-eav">
        <label for="<?= $inputId ?>-0">
            <?= $model->getAttributeLabel($property->key) ?>
            <button class="btn btn-info btn-xs" data-action="add-new-eav-input"><i class="fa fa-plus"></i></button>
        </label>
        <?php foreach ($values as $index => $value): ?>
            <div class="input-group">
                <div class="input-group-addon arrows"><i class="fa fa-arrows"></i></div>
                <?=
                Html::input(
                    'text',
                    $inputName,
                    $value,
                    [
                        'class' => 'form-control',
                        'id' => $inputId . '-' . $index,
                        'name' => $inputName,
                    ]
                )
                ?>
                <div class="input-group-addon">
                    <button class="btn btn-xs btn-danger" data-action="delete-eav-input"><i class="fa fa-close"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php echo (new ActiveForm())->field($model, $property->key); ?>
<?php endif; ?>
