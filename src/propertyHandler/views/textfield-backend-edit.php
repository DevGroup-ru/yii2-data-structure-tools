<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var \yii\widgets\ActiveForm $form
 * @var \yii\web\View $this
 */
use yii\helpers\Html;

if (true === $property->canTranslate()) {
    \kartik\icons\FlagIconAsset::register($this);
    $items = [];
    $languages = Yii::$app->multilingual->getAllLanguages();
    foreach ($languages as $index => $language) {
        $flag = $language->iso_639_1 === 'en' ? 'gb' : $language->iso_639_1;
        $items[] = [
            'label' => '<span class="flag-icon flag-icon-' . $flag . '"></span> ' . $language->name,
            'active' => $index === 0,
            'content' => $this->render(
                'textfield-tab-content',
                [
                    'model' => $model,
                    'form' => $form,
                    'property' => $property,
                    'langId' => $language->id,
                ]
            )
        ];
    }
    echo "<div class=\"nav-tabs-custom\">" . \yii\bootstrap\Tabs::widget([
            'items' => $items,
            'encodeLabels' => false,
        ]) . '</div>';
} else {
    if ($property->allow_multiple_values == 1) :
        $inputName = Html::getInputName($model, $property->key) . '[]';
        $inputId = Html::getInputId($model, $property->key);
        $values = isset($model->{$property->key}) ? (array)$model->{$property->key} : [];
        if (count($values) === 0) {
            $values = [''];
        }
        ?>
        <div class="m-form__col multi-eav <?= $model->hasErrors($property->key) ? 'has-error' : '' ?>">
            <?php foreach ($values as $index => $value) : ?>
                <label for="<?= $inputId . '-' . $index ?>">
                    <?= $property->name ?>
                    <button class="btn btn-info btn-xs" data-action="add-new-eav-input">
                        <i class="fa fa-plus"></i>
                    </button>
                </label>
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
        echo $form->field($model, $property->key)->label($property->name);
    endif;
}


