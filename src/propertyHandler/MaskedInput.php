<?php


namespace DevGroup\DataStructure\propertyHandler;


use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\models\Property;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\jui\JuiAsset;

class MaskedInput extends AbstractPropertyHandler
{
    public static $multipleMode = Property::MODE_ALLOW_ALL;

    /**
     * Get validation rules for a property.
     *
     * @param Property $property
     *
     * @return array of ActiveRecord validation rules
     */
    public function getValidationRules(Property $property)
    {
        $key = $property->key;
        if ($property->allow_multiple_values) {
            return [
                [$key, 'each', 'rule' => ['safe']],
            ];
        } else {
            return [
                [$key, 'safe'],
            ];
        }
    }

    /**
     * @param Property $property
     * @param bool $insert
     *
     * @return bool
     */
    public function beforePropertyModelSave(Property &$property, $insert)
    {
        if ($property->canTranslate()) {
            $property->data_type = Property::DATA_TYPE_INVARIANT_STRING;
        }
        $data = Yii::$app->request->post(Property::PACKED_HANDLER_PARAMS);
        if (empty($data) === true) {
            $data = ArrayHelper::getValue(
                Yii::$app->request->post($property->formName()),
                'params.' . Property::PACKED_HANDLER_PARAMS,
                []
            );
        }
        $param = $property->params;
        if (!empty($data)) {
            $params[Property::PACKED_HANDLER_PARAMS] = $data;
            $property->params = $params;
            $property->packAttributes();
        }
        return parent::beforePropertyModelSave($property, $insert);
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
            echo $view->render('_masked-input-settings', ['property' => $model, 'form' => $form]);
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
        return parent::renderProperty($model, $property, $view, $form);
    }
}