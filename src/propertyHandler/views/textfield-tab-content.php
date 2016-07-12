<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 * @var integer $langId
 */
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$inputName = Html::getInputName($model, $property->key) . '[' . $langId . '][]';
$inputId = Html::getInputId($model, $property->key);
$values = isset($model->{$property->key}[$langId]) ? (array)$model->{$property->key}[$langId] : [];
if (count($values) === 0) {
    $values = [''];
}
if ($property->allow_multiple_values == 1) : ?>
    <div class="m-form__col multi-eav <?= $model->hasErrors($property->key) ? 'has-error' : '' ?>">
        <label for="<?= $inputId?>">
            <?= $property->name ?>
            <button class="btn btn-info btn-xs" data-action="add-new-eav-input">
                <i class="fa fa-plus"></i>
            </button>
        </label>
        <?php foreach ($values as $index => $value) : ?>
            <div class="input-group">
                <div class="input-group-addon arrows"><i class="fa fa-arrows"></i></div>
                <?=
                Html::textInput(
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
                    <button class="btn btn-xs btn-danger" data-action="delete-eav-input"><i class="fa fa-close"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="help-block"><?= implode('<br />', $model->getErrors($property->key)) ?></div>
    </div>
<?php else :
    $value = array_pop($values);
    ?>
    <label for="<?= $inputId . '-' . $langId ?>">
        <?= $property->name ?>
    </label>
    <?= Html::textInput($inputName, $value, ['class' => 'form-control', 'id' => $inputId . '-' . $langId]) ?>
    <div class="help-block"><?= implode('<br />', $model->getErrors($property->key)) ?></div>
<?php endif; ?>