<?php


namespace DevGroup\DataStructure\propertyHandler;


use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\propertyStorage\EAV;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\jui\JuiAsset;

class RelatedEntity extends AbstractPropertyHandler
{
    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_ALL;

    /** @inheritdoc */
    public static $allowedStorage = [
        EAV::class,
    ];

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_INTEGER,
    ];

    /** @inheritdoc */
    public static $allowInSearch = true;

    public static $aliases = [
        1 => 'date',
        2 => 'ip',
        3 => 'url',
        4 => 'email',
        5 => 'decimal',
    ];

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
                [$key, 'each', 'skipOnEmpty' => true, 'rule' => ['filter', 'filter' => 'intval']],
                $this->existenceValidation($property),
            ];
        } else {
            return [
                [$key, 'filter', 'skipOnEmpty' => true, 'filter' => 'intval'],
                $this->existenceValidation($property),
            ];
        }
    }

    /**
     * @param Property $property
     *
     * @return array Validation rule for checking existence of static_value row with specified ID
     *
     * @warning If we are updating multiple models properties at once - we get lots of queries to db(1 for each model
     *     in array)
     */
    private function existenceValidation(Property $property)
    {
        $key = $property->key;

        return [
            $key,
            'exist',
            'targetClass' => $this->getRelatedClass($property),
            'targetAttribute' => 'id',
            'allowArray' => $property->allow_multiple_values,
            'skipOnEmpty' => true,
        ];

    }

    public function getRelatedClass(Property $property)
    {
        $params = $property->params;
        return ArrayHelper::getValue($params, Property::PACKED_HANDLER_PARAMS . '.className');
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
        if (empty($data) === false) {
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
            RelatedEntityAsset::register($view);
            $model = $event->model;
            $form = $event->form;
            echo $view->render('_related-entity-settings', ['property' => $model, 'form' => $form]);
        }
    }

}