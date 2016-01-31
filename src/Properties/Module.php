<?php

namespace DevGroup\DataStructure\Properties;

use arogachev\sortable\controllers\SortController;
use DevGroup\AdminUtils\events\ModelEditAction;
use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\propertyHandler\StaticValues;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
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
class Module extends BaseModule implements BootstrapInterface
{
    public function init()
    {
        parent::init();

        if (Yii::$app instanceof \yii\web\Application) {
            $this->controllerMap = [
                'sort' => [
                    'class' => SortController::class,
                ]
            ];
        }

        ModelEditForm::on(
            View::className(),
            EditProperty::EVENT_AFTER_FORM,
            [StaticValues::className(), 'onPropertyEditForm']
        );
    }

    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        $app->on(Application::EVENT_BEFORE_REQUEST, function () {
            Yii::$app->setAliases([
                '@dataStructure' => '@vendor/devgroup/yii2-data-structure-tools/src/',
            ]);
        });
    }


}
