<?php

namespace DevGroup\DataStructure\Properties;

use DevGroup\AdminUtils\events\ModelEditAction;
use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\propertyHandler\StaticValues;
use Yii;
use yii\base\Module as BaseModule;
use yii\web\View;

/**
 * This is a module for you application backend part.
 * Features:
 * - property groups crud
 * - binding groups to models
 * - properties crud
 * - widget for editing model's properties
 *
 * @package DevGroup\DataStructure\ManageProperties
 */
class Module extends BaseModule
{
    public function init()
    {
        parent::init();

        ModelEditForm::on(
            View::className(),
            EditProperty::EVENT_FORM_AFTER_SUBMIT,
            [StaticValues::className(), 'onPropertyEditForm']
        );
    }
}
