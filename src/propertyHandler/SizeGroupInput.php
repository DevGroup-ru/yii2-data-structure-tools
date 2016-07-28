<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\AdminUtils\events\ModelEditAction;
use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\events\SizeGroupEvent;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\Measure\models\Measure;
use yii\helpers\ArrayHelper;
use yii\jui\JuiAsset;
use Yii;

/**
 * Class SizeGroupInput
 * @package DevGroup\DataStructure\propertyHandler
 */
class SizeGroupInput extends AbstractPropertyHandler
{

    const EVENT_BEFORE_RENDER_PROPERTY = 'event_before_render_property';

    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_SINGLE;

    /** @inheritdoc */
    public static $allowInSearch = true;

    /** @inheritdoc */
    public static $allowedStorage = [
        EAV::class
    ];

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_INVARIANT_STRING
    ];

    /**
     * @inheritdoc
     */
    public function getValidationRules(Property $property)
    {
        $key = $property->key;

        $pattern = ArrayHelper::getValue(
            $property->params,
            Property::PACKED_HANDLER_PARAMS . '.template'
        );

        $pattern = self::convertTemplate2Reg($pattern);

        $rule = Property::dataTypeValidator($property->data_type) ?: 'safe';
        return [
            [$key, $rule],
            [$key, 'match', 'pattern' => $pattern]
        ];
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

        $event = new SizeGroupEvent([
            'property' => $property,
            'model' => $model,
            'view' => $view,
            'measureId' => ArrayHelper::getValue(
                $property->params,
                Property::PACKED_HANDLER_PARAMS . '.measure_id'
            ),
            'measureFrontendId' => ArrayHelper::getValue(
                $property->params,
                Property::PACKED_HANDLER_PARAMS . '.measure_frontend_id'
            ),
            'values' => self::getValuesFromString($model->{$property->key}, $property)
        ]);

        $this->trigger(self::EVENT_BEFORE_RENDER_PROPERTY, $event);


        $template = ArrayHelper::getValue($property->params, Property::PACKED_HANDLER_PARAMS . '.template', '');

        return $this->render(
            $this->convertView($event->view),
            [
                'model' => $event->model,
                'property' => $event->property,
                'form' => $form,
                'measure' => Measure::getById($event->measureId),
                'measureFrontend' => Measure::getById($event->measureFrontendId),
                'template' => $template,
                'mask' => self::convertTemplate2Mask($template),
                'values' => $event->values
            ]
        );
    }


    /**
     * @param ModelEditAction $event
     */
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

            $measureId = ArrayHelper::getValue(
                $event->model->params,
                Property::PACKED_HANDLER_PARAMS . '.measure_id'
            );

            $measureFrontendId = ArrayHelper::getValue(
                $event->model->params,
                Property::PACKED_HANDLER_PARAMS . '.measure_frontend_id'
            );

            if (empty(Measure::getById($measureId, true)) === true ||
                $measureId == $measureFrontendId ||
                (
                    empty($measureFrontendId) === false &&
                    empty(Measure::getById($measureFrontendId, true)) === true
                )
            ) {
                $event->model->addError('params', Module::t('app', 'Measure not valid'));
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
            $measures = Measure::getMeasures('Length');

            echo $view->render('_sizegroup-settings', ['model' => $model, 'form' => $form, 'measures' => $measures]);

        }
    }


    /**
     * @param $template
     * @return string
     */
    public static function convertTemplate2Reg($template)
    {
        $result = strtr(
            $template,
            [
                '/' => '\/',
                '-' => '\-',
                '?' => '\?',
                '*' => '\*',
                '+' => '\+'
            ]
        );
        preg_match_all('/{([a-z0-9]+)}/i', $result, $matches);

        if (empty($matches[0]) === false) {
            foreach ($matches[0] as $key => $math) {
                $result = strtr($result, [$math => '(?P<' . $matches[1][$key] . '>\d+.?\d{0,})']);
            }
        }
        return '/' . $result . '/ie';
    }

    /**
     * @param $template
     * @return string
     */
    public static function convertTemplate2Mask($template)
    {
        $result = $template;
        preg_match_all('/{([a-z0-9]+)}/i', $result, $matches);

        if (empty($matches[0]) === false) {
            foreach ($matches[0] as $key => $math) {
                $result = strtr($result, [$math => '9{+}.{0,1}9{*}']);
            }
        }
        return $result;
    }

    /**
     * @param $value
     * @param Property $property
     * @return array
     */
    public static function getValuesFromString($value, Property $property)
    {
        $template = ArrayHelper::getValue($property->params, Property::PACKED_HANDLER_PARAMS . '.template', '');
        $result = [];
        if (empty($value) === false && empty($template) === false && $reg = self::convertTemplate2Reg($template)) {
            preg_match($reg, $value, $match);
            foreach ($match as $key => $res) {
                if (is_numeric($key) === false) {
                    $result[$key] = doubleval($res);
                }
            }
        }
        return $result;
    }
}
