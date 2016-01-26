<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use kartik\select2\Select2;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class StaticValues extends AbstractPropertyHandler
{
    /**
     * Forces property to be of integer data type
     * @param \DevGroup\DataStructure\models\Property $property
     * @param                                         $insert
     *
     * @return bool
     */
    public function beforePropertyModelSave(Property &$property, $insert)
    {
        // static values are forced to be of integer data type
        $property->data_type = Property::DATA_TYPE_INTEGER;
        return parent::beforePropertyModelSave($property, $insert);
    }

    /**
     * @inheritdoc
     */
    public function getValidationRules(Property $property)
    {
        $key = $property->key;
        if ($property->allow_multiple_values) {
            return [
                [$key, 'each', 'rule' => ['filter', 'filter' => 'intval']],
                $this->existenceValidation($property),
            ];
        } else {
            return [
                [$key, 'filter', 'filter' => 'intval'],
                $this->existenceValidation($property),
            ];
        }
    }

    /**
     * @param Property $property
     * @return array Validation rule for checking existence of static_value row with specified ID
     *
     * @warning If we are updating multiple models properties at once - we get lots of queries to db(1 for each model in array)
     */
    private function existenceValidation(Property $property)
    {
        $key = $property->key;

        return [
            $key,
            'exist',
            'targetClass' => StaticValue::className(),
            'targetAttribute' => 'id',
            'allowArray' => true,
            'filter' => ['property_id' => $property->id],
        ];

    }

    /**
     * @param \DevGroup\AdminUtils\events\ModelEditForm $event
     */
    public static function onPropertyEditForm(ModelEditForm $event)
    {
        $view = $event->getView();
        $model = $event->model;
        $staticValue = new StaticValue($model);
        $staticValue->setScenario('search');
        $dataProvider = $staticValue->search($model->id, \Yii::$app->request->get());

        echo $view->render(
            '_static-values-grid',
            [
                'property' => $model,
                'staticValue' => $staticValue,
                'dataProvider' => $dataProvider,
            ]
        );
    }
}
