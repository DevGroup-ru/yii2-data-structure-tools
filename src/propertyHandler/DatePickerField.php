<?php


namespace DevGroup\DataStructure\propertyHandler;


use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\TableInheritance;
use Yii;
use yii\jui\JuiAsset;

class DatePickerField extends AbstractPropertyHandler
{

    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_NOTHING;

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_STRING,
        Property::DATA_TYPE_INVARIANT_STRING,
        Property::DATA_TYPE_INTEGER,
    ];

    /** @inheritdoc */
    public static $allowInSearch = true;

    /** @inheritdoc */
    public static $allowedStorage = [
        EAV::class,
        TableInheritance::class,
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
                [$key, 'each', 'rule' => ['date']],
                [$key, 'each', 'rule' => ['default', 'value' => null]],
            ];
        } else {
            return [
                [$key, 'date'],
                [$key, 'default', 'value' => null],
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
        return parent::beforePropertyModelSave($property, $insert);
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