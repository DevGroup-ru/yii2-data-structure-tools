<?php

namespace DevGroup\DataStructure\search\widgets;

use DevGroup\DataStructure\search\base\AbstractSearch;
use yii\base\InvalidParamException;
use yii\base\Widget;
use Yii;

/**
 * Class FilterFormWidget
 * @codeCoverageIgnore
 * @package DevGroup\DataStructure\search\elastic\widgets
 */
class FilterFormWidget extends Widget
{
    /** @var string required model class name to search for */
    public $modelClass = '';
    /** @var string */
    public $viewFile = 'default';
    /**
     * @var array additional array config. Special key `storage` will be used for definition against what property
     * storage search will be proceed. If you omit it search will be work only against `StaticValues` storage by default
     */
    public $config = [];
    /**
     * @var array|string required param, `action` attribute for rendered filter form
     */
    public $filterRoute;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (true === empty($this->filterRoute)) {
            throw new InvalidParamException("'filterRoute' must be set!");
        }
        if (false === class_exists($this->modelClass)) {
            throw new InvalidParamException("'modelClass' must be the correct class name!");
        }
        $class = $this->modelClass;
        $model = new $class;
        if (false === method_exists($model, 'ensurePropertyGroupIds')) {
            throw new InvalidParamException('Model class must has PropertiesTrait.');
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        /** @var AbstractSearch $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $config = array_merge($this->config, ['modelClass' => $this->modelClass]);
        $data = $search->filterFormData($config);
        return $this->render(
            $this->viewFile,
            [
                'data' => $data,
                'filterRoute' => $this->filterRoute,
            ]
        );
    }
}