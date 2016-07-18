<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\propertyHandler\RelatedEntity;
use DevGroup\DataStructure\widgets\MaskedInput;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$params = ArrayHelper::getValue($property, 'params.' . Property::PACKED_HANDLER_PARAMS, []);
$maskedSettings = [];
if (empty($params['mask']) === false) {
    $maskedSettings['mask'] = $params['mask'];
} elseif (empty($params['alias']) === false) {
    $maskedSettings = ['clientOptions' => ['alias' => RelatedEntity::$aliases[$params['alias']],],];
} else {
    $maskedSettings['mask'] = '*{0,*}';
}
if ($property->allow_multiple_values == 1) :
    $inputName = Html::getInputName($model, $property->key) . '[]';
    $inputId = Html::getInputId($model, $property->key);
    $values = isset($model->{$property->key}) ? (array) $model->{$property->key} : [];
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
                <?= MaskedInput::widget(
                    ArrayHelper::merge(
                        [
                            'name' => $inputName,
                            'value' => $value,
                            'id' => $inputId . '-' . $index,
                            'class' => 'form-control',
                        ],
                        $maskedSettings
                    )
                ); ?>
                <div class="input-group-addon">
                    <button class="btn btn-xs btn-danger" data-action="delete-eav-input"><i class="fa fa-close"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="help-block"><?= implode('<br />', $model->getErrors($property->key)) ?></div>
    </div>
<?php else :
    echo $form->field($model, $property->key)->widget(\yii\widgets\MaskedInput::className(), $maskedSettings)->label(
        $property->name
    );
endif;