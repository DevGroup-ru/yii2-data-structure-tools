<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\traits\PropertiesTrait;
use yii\base\Widget;
use yii\db\ActiveRecord;

abstract class AbstractPropertyHandler extends Widget
{
    const BACKEND_RENDER = 'backend-render';
    const BACKEND_EDIT = 'backend-edit';
    const FRONTEND_RENDER = 'frontend-render';
    const FRONTEND_EDIT = 'frontend-edit';

    public function __construct()
    {

    }

    public function afterFind($model, $attribute)
    {

    }

    public function afterSave($model, $attribute)
    {

    }

    public function beforeSave($model, $attribute)
    {
        return true;
    }

    /**
     * Convert a view name.
     * @param string $view
     * @return string
     */
    public function convertView($view)
    {
        if (strpos($view, '@') !== false || strpos($view, '/') !== false || strpos($view, '\\') !== false) {
            return $view;
        }
        return strtolower(substr(static::className(), strrpos(static::className(), '\\') + 1)) . '-' . $view;
    }

    /**
     * @param \DevGroup\DataStructure\models\Property $property
     * @param bool                                    $insert
     *
     * @return bool
     */
    public function beforePropertyModelSave(Property &$property, $insert)
    {
        return true;
    }

    public function afterPropertyModelSave(Property &$property)
    {

    }

    /**
     * Get validation rules for a property.
     * @param Property $property
     * @return array of ActiveRecord validation rules
     */
    abstract public function getValidationRules(Property $property);

    /**
     * Render a property.
     * @param ActiveRecord | HasProperties | PropertiesTrait $model
     * @param Property $property
     * @param string $view
     * @return string
     */
    public function renderProperty($model, $property, $view)
    {
        return $this->render(
            $this->convertView($view),
            [
                'model' => $model,
                'property' => $property
            ]
        );
    }

    /**
     * @return string class name with namespace
     */
    public static function className()
    {
        return get_called_class();
    }
}
