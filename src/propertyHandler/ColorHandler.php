<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\models\StaticValue;

class ColorHandler extends StaticValues
{
    /**
     * @param ModelEditForm $event
     */
    public static function onStaticValueEditForm(ModelEditForm $event)
    {
        /**
         * @var StaticValue
         */
        $model = $event->model;
        if (empty($model->property) === false && $model->property->handler()->className() == self::class) {
            $view = $event->getView();
            $form = $event->form;
            echo $view->render('_color-handler-settings', ['model' => $model, 'form' => $form]);
        }
    }
}