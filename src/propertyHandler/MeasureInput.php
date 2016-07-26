<?php

namespace DevGroup\DataStructure\propertyHandler;


use DevGroup\AdminUtils\events\ModelEditAction;
use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\validators\ValuesValidator;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\Measure\models\Measure;
use yii\helpers\ArrayHelper;
use yii\jui\JuiAsset;
use Yii;
use yii\validators\ExistValidator;

class MeasureInput extends AbstractPropertyHandler
{
    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_ALL;

    /** @inheritdoc */
    public static $allowInSearch = true;

    /** @inheritdoc */
    public static $allowedStorage = [
        EAV::class
    ];

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_INTEGER,
        Property::DATA_TYPE_FLOAT,
    ];

    /**
     * @inheritdoc
     */
    public function getValidationRules(Property $property)
    {
        $key = $property->key;

        switch ($property->data_type) {
            case Property::DATA_TYPE_INTEGER:
                $numberValidate = 'integer';
                break;
            case Property::DATA_TYPE_FLOAT:
                $numberValidate = 'double';
                break;
            default:
                $numberValidate = 'integer';
                break;
        }

        if (true === $property->canTranslate()) {
            return [
                [$key, ValuesValidator::class, 'skipOnEmpty' => true],
            ];
        } else {
            $rule = Property::dataTypeValidator($property->data_type) ?: 'safe';
            if ($property->allow_multiple_values) {
                return [
                    [$key, 'each', 'rule' => [$rule]],
                    [$key, 'each', 'rule' => [$numberValidate]],

                ];
            } else {
                return [
                    [$key, $rule],
                    [$key, $numberValidate]
                ];
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function renderProperty($model, $property, $view, $form = null)
    {
        if ($property->allow_multiple_values === true) {
            JuiAsset::register($this->getView());
            TextFieldAsset::register($this->getView());
        }

        $measure = null;

        if (empty($property->params[Property::PACKED_HANDLER_PARAMS]['measure_id']) === false) {
            $measure = Measure::findOne(['id' => $property->params[Property::PACKED_HANDLER_PARAMS]['measure_id']]);
        }

        return $this->render(
            $this->convertView($view),
            [
                'model' => $model,
                'property' => $property,
                'form' => $form,
                'measure' => $measure
            ]
        );
    }


    public static function onPropertyEditAction(ModelEditAction $event)
    {

        if ($event->isValid === true
            && $event->model->isNewRecord === false
            && $event->model->handler()->className() == self::class
        ) {
            $params = $event->model->params;
            $handlerParams = ArrayHelper::getValue(
                Yii::$app->request->post($event->model->formName()),
                'params.' . Property::PACKED_HANDLER_PARAMS,
                []
            );
            $params[Property::PACKED_HANDLER_PARAMS] = $handlerParams;
            $event->model->params = $params;

            if (empty($event->model->params[Property::PACKED_HANDLER_PARAMS]['measure_id']) === true ||
                (new ExistValidator(['targetClass' => Measure::class, 'targetAttribute' => 'id']))->validate(
                    $event->model->params[Property::PACKED_HANDLER_PARAMS]['measure_id'],
                    $errors
                ) === false
            ) {
                $event->model->addError('params', $errors);
                $event->isValid = false;
            }

        }
    }


    /**
     * @param ModelEditForm $event
     */
    public static function onPropertyEditForm(ModelEditForm $event)
    {
        if (!$event->model->isNewRecord && $event->model->handler()->className() == self::class) {
            $view = $event->getView();
            $model = $event->model;
            $form = $event->form;
            $measures = Measure::getMeasures(false);

            echo $view->render('_measureinput-settings', ['model' => $model, 'form' => $form, 'measures' => $measures]);

        }
    }


}