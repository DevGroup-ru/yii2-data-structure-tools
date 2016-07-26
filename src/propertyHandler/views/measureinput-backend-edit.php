<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var \yii\widgets\ActiveForm $form
 * @var Measure $measure
 * @var \yii\web\View $this
 */
use DevGroup\Measure\helpers\MeasureHelper;
use DevGroup\Measure\models\Measure;
use yii\helpers\Html;


$unit = '';
if (empty($measure) === false) {
    $unit = MeasureHelper::t($measure->unit);
}


if ($property->allow_multiple_values == 1) :
    $inputName = Html::getInputName($model, $property->key) . '[]';
    $inputId = Html::getInputId($model, $property->key);
    $values = isset($model->{$property->key}) ? (array)$model->{$property->key} : [];
    if (count($values) === 0) {
        $values = [''];
    }
    ?>
    <div class="m-form__col multi-eav <?= $model->hasErrors($property->key) ? 'has-error' : '' ?>">
        <label for="<?= $inputId ?>">
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
                <div class="input-group-addon"><?= $unit ?></div
                <div class="input-group-addon">
                    <button class="btn btn-xs btn-danger" data-action="delete-eav-input"><i class="fa fa-close"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="help-block"><?= implode('<br />', $model->getErrors($property->key)) ?></div>
    </div>
<?php else :
    echo $form->field($model, $property->key,
        [
            'template' => "{label}\n <div class=\"input-group\">{input}<div class=\"input-group-addon\">" . $unit . "</div></div>\n{hint}\n{error}"
        ])->input('text')->label($property->name);
endif;
