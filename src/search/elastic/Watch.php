<?php

namespace DevGroup\DataStructure\search\elastic;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValueTranslation;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\search\base\AbstractWatch;
use DevGroup\DataStructure\search\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\search\helpers\LanguageHelper;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Elasticsearch\Client;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use Yii;

/**
 * Class Watch Ensures the integrity and relevance of the elasticsearch indices
 * Elastic index structure:
 * - index name: according to site entity name from ApplicablePropertyModels::$name in lowercase i.e.: `realty`
 * - index type: according to property type i.e.: `static_values`
 * - index id: auto generated
 * - index structure: @see ElasticIndexController::$staticIndexConfig
 *
 *
 * @package DevGroup\DataStructure\search\elastic
 */
class Watch extends AbstractWatch
{
    /** @var null | Client */
    private $client = null;

    /**
     * @inheritdoc
     */
    public function beforeInit()
    {
        if (true === parent::beforeInit()) {
            if (false === $this->client instanceof Client) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @param $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function onDelete($event)
    {
        $index = IndexHelper::classToIndex(get_class($event->model));
        $this->flushForModel($index, $event->model->id);
    }

    /**
     * @inheritdoc
     */
    public function onSave($event)
    {
        $index = IndexHelper::classToIndex(get_class($event->model));
        $this->fillForModel($index, $event->model->id);
    }

    /**
     * @inheritdoc
     */
    public function onUpdate($event)
    {
        $index = IndexHelper::classToIndex(get_class($event->model));
        $this->flushForModel($index, $event->model->id);
        $this->fillForModel($index, $event->model);
    }

    /**
     * Flushes all elasticsearch indices for given model id
     *
     * @param string $index
     * @param integer $modelId
     */
    protected function flushForModel($index, $modelId)
    {
        $query = [
            'index' => $index,
            'body' => [
                'query' => [
                    'bool' => [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    'term' => ['model_id' => $modelId]
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ];
        $pks = IndexHelper::primaryKeysByCondition($this->client, $query);
        if (count($pks) > 0) {
            $params = ['body' => []];
            foreach ($pks as $id => $type) {
                $params['body'][] = [
                    'delete' => [
                        '_index' => $index,
                        '_type' => $type,
                        '_id' => $id
                    ]
                ];
            }
            $this->client->bulk($params);
        }
    }

    /**
     * Performs model index filling
     *
     * @param string $index
     * @param ActiveRecord | HasProperties | PropertiesTrait $model
     */
    protected function fillForModel($index, $model)
    {
        if (false === empty($model->propertiesIds) && false === empty($model->propertiesValues)) {
            //leave only not empty properties
            $workingProps = array_filter($model->propertiesValues, function ($e) {
                return false === empty($e);
            });
            //TODO refactor. Work with all storages & possibly not yet created
            //collecting applicable storages
            $storage = (new Query())->from(PropertyStorage::tableName())->select(['class_name', 'id'])->where([
                'class_name' => [
                    StaticValues::class,
                    EAV::class
                ]
            ])->all();
            $storageClassToId = ArrayHelper::map($storage, 'class_name', 'id');
            $storageIdToIndexType = ArrayHelper::map($storage, 'id', function ($e) {
                return IndexHelper::storageClassToType($e['class_name']);
            });
            //selecting all applicable properties to work with
            $props = (new Query())->from(Property::tableName())->select(['id', 'key', 'storage_id'])->where(
                [
                    'id' => array_keys($workingProps),
                    'storage_id' => array_keys($storageIdToIndexType),
                    'in_search' => 1
                ]
            )->all();
            //grouping them by storage id
            $props = ArrayHelper::map($props, 'id', 'key', 'storage_id');
            if (false === empty($props[$storageClassToId[StaticValues::class]])) {
                $staticBulk = self::prepareStatic(
                    $props[$storageClassToId[StaticValues::class]],
                    $model->id,
                    $workingProps,
                    $index,
                    $storageIdToIndexType[$storageClassToId[StaticValues::class]]
                );
                $this->client->bulk($staticBulk);
            }
            //TODO implement EAV values filling
            /*
            if (false === empty($props[$storageClassToId[EAV::class]])) {
                $eavBulk = self::prepareEav(
                    $props[$storageClassToId[EAV::class]],
                    $model->id,
                    $workingProps,
                    $index,
                    $storageIdToIndexType[$storageClassToId[EAV::class]]
                );
            }
            */
        }
    }

    /**
     * Prepares bulk data to store in elasticsearch index for model properties static values
     *
     * @param array $props
     * @param integer $modelId
     * @param array $workingProps
     * @param string $index index name i.e.: page
     * @param string $type index type i.e.: static_values
     * @return array
     */
    private static function prepareStatic($props, $modelId, $workingProps, $index, $type)
    {
        $languages = LanguageHelper::getAll();
        $res = $valIds = [];
        //leave only applicable properties with according values
        $workingProps = array_intersect_key($workingProps, $props);
        $workingProps = array_flip($workingProps);
        $values = (new Query())->from(StaticValueTranslation::tableName())
            ->select(['model_id', 'language_id', 'name', 'slug'])
            ->where(['model_id' => array_keys($workingProps)])
            ->all();
        $propertyValues = [];
        foreach ($values as $value) {
            if (false === isset($propertyValues[$value['model_id']])) {
                $propId = isset($workingProps[$value['model_id']]) ? $workingProps[$value['model_id']] : null;
                $propKey = isset($props[$propId]) ? $props[$propId] : null;
                $propertyValues[$value['model_id']] = [
                    'static_value_id' => $value['model_id'],
                    'prop_id' => $propId,
                    'prop_key' => $propKey,
                    'value_' . $languages[$value['language_id']] => $value['name'],
                    'slug_' . $languages[$value['language_id']] => $value['slug'],
                ];
            } else {
                $propertyValues[$value['model_id']]['value_' . $languages[$value['language_id']]] = $value['name'];
                $propertyValues[$value['model_id']]['slug_' . $languages[$value['language_id']]] = $value['slug'];
            }
        }
        $res['body'][] = ['index' => [
            '_id' => $modelId,
            '_index' => $index,
            '_type' => $type,
        ]];
        $res['body'][] = [
            'model_id' => $modelId,
            'propertyValues' => array_values($propertyValues),
        ];
        return $res;
    }

    /**
     * Prepares bulk data to store in elasticsearch index for model properties eav values
     *
     * @param array $props
     * @param integer $modelId
     * @param array $workingProps
     * @param string $index index name i.e.: page
     * @param string $type index type i.e.: static_values
     * @return array
     */
    /*
    private static function prepareEav($props, $modelId, $workingProps, $index, $type)
    {
        $bulk = [];
        return $bulk;
    }
    */
}