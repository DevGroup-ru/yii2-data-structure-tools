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

        if (is_a($app, \yii\web\Application::className())) {
            $this->controllerMap['sort'] = [
                'class' => SortController::className(),
            ];
        }

        $app->on(Application::EVENT_BEFORE_REQUEST, function () {
            Yii::$app->setAliases([
                '@dataStructure' => '@vendor/devgroup/yii2-data-structure-tools/src/',
            ]);
        });

        $this->registerTranslations();
    }

    /**
     * Add custom translations source
     */
    public function registerTranslations()
    {
        Yii::$app->i18n->translations['@vendor/devgroup/yii2-data-structure-tools/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@vendor/devgroup/yii2-data-structure-tools/src/translations',
            'fileMap' => [
                '@vendor/devgroup/yii2-data-structure-tools/app' => 'app.php',
                '@vendor/devgroup/yii2-data-structure-tools/widget' => 'widget.php',
            ],

        ];
    }

    /**
     * Add custom translations method
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('@vendor/devgroup/yii2-data-structure-tools/' . $category, $message, $params, $language);
    }
}
