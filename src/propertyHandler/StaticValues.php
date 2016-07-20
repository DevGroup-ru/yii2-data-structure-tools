<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class StaticValues extends AbstractPropertyHandler
{
    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_ALL;

    /** @inheritdoc */
    public static $allowInSearch = true;

    /** @inheritdoc */
    public static $allowedStorage = [
        \DevGroup\DataStructure\propertyStorage\StaticValues::class,
    ];

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_STRING,
        Property::DATA_TYPE_INTEGER,
        Property::DATA_TYPE_FLOAT,
        Property::DATA_TYPE_TEXT,
        Property::DATA_TYPE_PACKED_JSON,
        Property::DATA_TYPE_BOOLEAN,
        Property::DATA_TYPE_INVARIANT_STRING,
    ];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'PackedJsonAttributes' => [
                'class' => PackedJsonAttributes::className(),
            ],
        ];
    }
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
            'allowArray' => $property->allow_multiple_values,
            'filter' => ['property_id' => $property->id],
            'skipOnEmpty' => true,
        ];

    }

    /**
     * @param \DevGroup\AdminUtils\events\ModelEditForm $event
     */
    public static function onPropertyEditForm(ModelEditForm $event)
    {

        if (!$event->model->isNewRecord && $event->model->storage->class_name == \DevGroup\DataStructure\propertyStorage\StaticValues::className()) {
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
}
