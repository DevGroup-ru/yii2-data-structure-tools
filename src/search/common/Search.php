<?php

namespace DevGroup\DataStructure\search\common;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\search\base\AbstractSearch;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Search proceeds search against persistent database e.g.: mysql
 *
 * @package DevGroup\DataStructure\search\common
 */
class Search extends AbstractSearch
{
    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function findInContent($modelClass = '')
    {
        // TODO: Implement findInContent() method.
    }

    /**
     * @inheritdoc
     */
    public function findInProperties($modelClass = '', $config = [], $params = [])
    {
        if (false === is_string($modelClass) || false === class_exists($modelClass)) {
            return [];
        }
        /** @var ActiveRecord | HasProperties | PropertiesTrait $model */
        $model = new $modelClass;
        if (false === method_exists($model, 'ensurePropertyGroupIds')) {
            return [];
        }
        $storage = self::prepareStorage($config);
        $data = false;
        ksort($params);
        /** @var AbstractPropertyStorage $one */
        foreach ($storage as $one) {
            $modelIds = $one::getModelIdsByValues($modelClass, $params);
            if ($modelIds === false) {
                continue;
            } elseif (empty($modelIds)) {
                return [];
            }
            $data = $data === false
                ? $modelIds
                : array_intersect($data, $modelIds);
        }
        // fallback. return all ids
        if ($data === false) {
            return $modelClass::find()->select($model->primaryKey())->column();
        }
        return $data;
    }

    /**
     * Prepares list of applicable storage
     *
     * @param array $config
     * @return array
     */
    private static function prepareStorage($config)
    {
        $list = isset($config['storage']) ? $config['storage'] : [];
        $list = is_array($list) ? $list : [$list];
        $query = PropertyStorage::find()->select('id')->where('class_name=:className');
        if (count($list) == 0) {
            return [$query->params([':className' => StaticValues::class])->scalar() => StaticValues::class];
        }
        $storage = [];
        foreach ($list as $storageClass) {
            if (false === $storageId = $query->params([':className' => $storageClass])->scalar()) {
                continue;
            }
            $storage[$storageId] = $storageClass;
        }
        return $storage;
    }

    /**
     * @param array $config you can define it like this:
     * [
     *  'modelClass' => Page::class, // models to search for
     *  'storage' => StaticValues::class, //property storage or array of storages handler to search with
     * ]
     * For now and by default this works only with StaticValues property handler
     *
     * @return array
     */
    public function filterFormData($config = [])
    {
        if (false === isset($config['modelClass']) || false === class_exists($config['modelClass'])) {
            return [];
        }
        $class = $config['modelClass'];
        /** @var ActiveRecord | HasProperties | PropertiesTrait $model */
        $model = new $class;
        if (false === method_exists($model, 'ensurePropertyGroupIds')) {
            return [];
        }
        $storage = self::prepareStorage($config);
        $props = Property::find()
            ->select(['id', 'name'])
            ->where([
                'in_search' => 1,
                'storage_id' => array_keys($storage)
            ])
            ->asArray(true)
            ->all();
        $props = ArrayHelper::map($props, 'id', 'name');
        $data = [];
        /** @var AbstractPropertyStorage $one */
        foreach ($storage as $one) {
            $data = ArrayHelper::merge($data, $one::filterFormSet($class, array_keys($props)));
        }
        return [
            'data' => $data,
            'props' => $props,
            'selected' => Yii::$app->request->get('filter', []),
        ];
    }
}
