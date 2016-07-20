<?php

namespace DevGroup\DataStructure\Properties;

use arogachev\sortable\controllers\SortController;
use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\commands\ElasticIndexController;
use DevGroup\DataStructure\commands\TranslateEavController;
use DevGroup\DataStructure\models\PropertyHandlers;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\Properties\actions\EditStaticValue;
use DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler;
use DevGroup\DataStructure\propertyHandler\ColorHandler;
use DevGroup\DataStructure\propertyHandler\MaskedInput;
use DevGroup\DataStructure\propertyHandler\RelatedEntity;
use DevGroup\DataStructure\propertyHandler\StaticValues;
use DevGroup\DataStructure\search\base\AbstractSearch;
use DevGroup\DataStructure\search\common\Search;
use DevGroup\DataStructure\search\elastic\Search as Elastic;
use DevGroup\TagDependencyHelper\NamingHelper;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\base\Module as BaseModule;
use yii\caching\TagDependency;
use yii\db\Query;
use yii\helpers\Json;
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

    /**
     * Configuration array of applicable property handlers list
     * by default - empty
     * If you want to use only few - list them in this array just like:
     * [
     *    \DevGroup\DataStructure\propertyHandler\MapField::class,
     *    \DevGroup\DataStructure\propertyHandler\TextArea::class,
     *    \DevGroup\DataStructure\propertyHandler\RadioList::class
     * ]
     * @var array
     */
    public $handlersList = [];

    /**
     * Prepares json string to use in js property creation wizard
     *
     * @return array
     */
    public function prepareWizardData()
    {
        $cacheKey = 'property-wizard' . md5(implode(':', $this->handlersList));
        $data = Yii::$app->cache->get($cacheKey);
        if (false === $data) {
            $query = (new Query())->from(PropertyHandlers::tableName())->indexBy('id')->select('class_name');
            if ((count($this->handlersList) != 0)) {
                $query->where(['class_name' => $this->handlersList]);
            }
            $handlers = $query->column();
            $storageToId = (new Query())->from(PropertyStorage::tableName())->indexBy('id')->select('class_name')->column();
            /**
             * @var AbstractPropertyHandler $className
             */
            foreach ($handlers as $id => $className) {
                $data[$id] = [
                    'id' => $id,
                    'allowedTypes' => $className::$allowedTypes,
                    'allowedStorage' => array_keys(array_intersect($storageToId, $className::$allowedStorage)),
                    'allowInSearch' => (bool)$className::$allowInSearch,
                    'multipleMode' => $className::$multipleMode
                ];
            }
            Yii::$app->cache->set(
                $cacheKey,
                $data,
                86400,
                new TagDependency(['tags' => [
                    NamingHelper::getCommonTag(PropertyHandlers::class),
                    NamingHelper::getCommonTag(PropertyStorage::class)
                ]])
            );
        }
        return $data;
    }

    /** @var  null | AbstractSearch */
    private $search = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        ModelEditForm::on(
            View::class,
            EditProperty::EVENT_AFTER_FORM,
            [StaticValues::class, 'onPropertyEditForm']
        );
        ModelEditForm::on(
            View::class,
            EditProperty::EVENT_FORM_BEFORE_SUBMIT,
            [MaskedInput::class, 'onPropertyEditForm']
        );
        ModelEditForm::on(
            View::class,
            EditProperty::EVENT_FORM_BEFORE_SUBMIT,
            [RelatedEntity::class, 'onPropertyEditForm']
        );

        ModelEditForm::on(
            View::class,
            EditStaticValue::EVENT_FORM_BEFORE_SUBMIT,
            [ColorHandler::class, 'onStaticValueEditForm']
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
            $this->controllerMap['translate-eav'] = [
                'class' => TranslateEavController::class,
            ];
        }

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
