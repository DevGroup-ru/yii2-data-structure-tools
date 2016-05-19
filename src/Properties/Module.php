<?php

namespace DevGroup\DataStructure\Properties;

use arogachev\sortable\controllers\SortController;
use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\commands\ElasticIndexController;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\propertyHandler\StaticValues;
use DevGroup\DataStructure\search\base\AbstractSearch;
use DevGroup\DataStructure\search\common\Search;
use DevGroup\DataStructure\search\elastic\Search as Elastic;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
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

    /** @var string */
    public $searchClass;

    public $searchConfig = [];

    /** @var  null | AbstractSearch */
    private $search = null;

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

        if ($app instanceof \yii\web\Application) {
            $started = false;
            $search = null;
            if (false === empty($this->searchClass)) {
                if (true === class_exists($this->searchClass)) {
                    $config = array_merge(['class' => $this->searchClass], $this->searchConfig);
                    $search = Yii::createObject($config);
                    $started = $search->getStarted();
                }
                if (false === $started) {
                    $search = new Search;
                }
                $this->search = $search;
            }
            $this->controllerMap['sort'] = [
                'class' => SortController::className(),
            ];
        }
        if ($app instanceof \yii\console\Application) {
            if ($this->searchClass === Elastic::class) {
                $this->controllerMap['elastic'] = [
                    'class' => ElasticIndexController::class,
                ];
            }
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
     * @return AbstractSearch
     * @throws InvalidConfigException
     */
    public function getSearch()
    {
        if (null === $this->search || false === $this->search instanceof AbstractSearch) {
            throw new InvalidConfigException("Before using 'Search' component you have to define it in the app config!");
        }
        return $this->search;
    }

    /**
     * Add custom translations method
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('@vendor/devgroup/yii2-data-structure-tools/' . $category, $message, $params, $language);
    }
}
