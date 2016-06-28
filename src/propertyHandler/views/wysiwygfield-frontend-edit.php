<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */


use yii\widgets\ActiveForm;
use yii\bootstrap\Html;
use yii\bootstrap\Tabs;

\kartik\icons\FlagIconAsset::register($this);

if (true === $property->canTranslate()) {
    $items = [];
    $languages = Yii::$app->multilingual->getAllLanguages();
    $inputId = Html::getInputId($model, $property->key);
    foreach ($languages as $index => $language) {
        $langInputId = $inputId . '-' . $index;
        $inputName = Html::getInputName($model, $property->key) . '[' . $language->id . '][]';
        $values = isset($model->{$property->key}[$language->id]) ? (array) $model->{$property->key}[$language->id] : [];
        if (count($values) === 0) {
            $values = [''];
        }
        $value = array_pop($values);
        $flag = $language->iso_639_1 === 'en' ? 'gb' : $language->iso_639_1;
        $items[] = [
            'label' => '<span class="flag-icon flag-icon-' . $flag . '"></span> ' . $language->name,
            'active' => $index === 0,
            'content' => Html::label($property->name, $langInputId) . \vova07\imperavi\Widget::widget(
                    [
                        'name' => $inputName,
                        'value' => $value,
                        'settings' => [
                            'lang' => 'ru',
                            'minHeight' => 200,
                            'plugins' => [
                                'clips',
                                'fullscreen',
                            ],
                        ],
                        'options' => ['class' => 'form-control', 'rows' => 7, 'id' => $langInputId],
                    ]
                ) . Html::tag('div', implode('<br />', $model->getErrors($property->key))),
        ];
    }
    echo "<div class=\"nav-tabs-custom\">" . Tabs::widget(
            [
                'items' => $items,
                'encodeLabels' => false,
            ]
        ) . '</div>';
} else {
    echo $form->field($model, $property->key)->widget(
        \vova07\imperavi\Widget::className(),
        [
            'settings' => [
                'lang' => 'ru',
                'minHeight' => 200,
                'plugins' => [
                    'clips',
                    'fullscreen',
                ],
            ],
        ]
    )->label($property->name);
}

