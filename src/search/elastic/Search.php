<?php

namespace DevGroup\DataStructure\search\elastic;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\PropertyTranslation;
use DevGroup\DataStructure\search\base\AbstractSearch;
use DevGroup\DataStructure\search\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\search\helpers\LanguageHelper;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Search
 * @package DevGroup\DataStructure\search\elastic
 * @property Watch $watcher
 */
class Search extends AbstractSearch
{
    /** @var  string */
    protected $defaultWatcherClass = Watch::class;

    /** @var  string */
    public $watcherClass = null;

    /** @var Client | null */
    private $client = null;

    /**
     * @var array of hosts to be configured with elasticsearch
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html
     */
    public $hosts = [];

    /**
     * Initiates self::$watcher and sets the flag self::$started if all started ok or not
     */
    public function init()
    {
        $client = ClientBuilder::create();
        if (true === is_array($this->hosts) && count($this->hosts) > 0) {
            $client->setHosts($this->hosts);
        }
        $this->client = $client->build();
        try {
            $e = $this->client->search(['index' => '_all', '_source' => false, 'body' => []]);
            /** @var null | Watch $watcher */
            $watcher = null;
            if (null !== $this->watcherClass) {
                if (true === class_exists($this->watcherClass)) {
                    $class = $this->watcherClass;
                    $watcher = new $class;
                }
            } else {
                $watcher = new $this->defaultWatcherClass;
            }
            if (null !== $watcher) {
                $watcher->setClient($this->client);
                $watcher->init();
                $this->watcher = $watcher;
                $this->started = true;
            } else {
                Yii::warning('There is no correct class defined for elasticsearch Watch. Search rolls back to common.');
            }
        } catch (NoNodesAvailableException $e) {
            Yii::warning('Elasticsearch said:' . $e->getMessage());
        }
    }

    /**
     * @return Client|null
     */
    public function getClient()
    {
        return $this->client;
    }

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
        $index = IndexHelper::classToIndex($modelClass);
        if (true === empty($index)) {
            return [];
        }
        $query = self::buildQuery($params, $index);
        $res = IndexHelper::primaryKeysByCondition($this->client, $query);
        return array_keys($res);
    }

    /**
     * @inheritdoc
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
        $index = IndexHelper::classToIndex($config['modelClass']);
        if (false === $this->client->indices()->exists(['index' => $index])) {
            return [];
        }
        $condition = [
            'index' => $index,
            "size" => 0,
            '_source' => false,
            'body' => [
                'aggs' => [
                    'props' => [
                        'nested' => [
                            'path' => 'propertyValues',
                        ],
                        'aggs' => [
                            'prop_id' => [
                                'terms' => [
                                    'field' => 'propertyValues.prop_id',
                                ],
                                'aggs' => [
                                    'values' => [
                                        'terms' => [
                                            'field' => 'propertyValues.value_' . LanguageHelper::getCurrent() . '.raw'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $res = $this->client->search($condition);
        $data = [];
        if (false === empty($res['aggregations']['props']['prop_id']['buckets'])) {
            foreach ($res['aggregations']['props']['prop_id']['buckets'] as $bucket) {
                if (true === empty($bucket['key'])) {
                    continue;
                }
                if (true === empty($bucket['values']['buckets'])) {
                    continue;
                }
                foreach ($bucket['values']['buckets'] as $value) {
                    if (true === empty($value['key'])) {
                        continue;
                    }
                    if (false === isset($data[$bucket['key']])) {
                        $data[$bucket['key']] = [$value['key'] => $value['key']];
                    } else {
                        $data[$bucket['key']][$value['key']] = $value['key'];
                    }
                }
            }
        }
        $props = PropertyTranslation::find()->select(['model_id', 'name'])
            ->where(['model_id' => array_keys($data), 'language_id' => Yii::$app->multilingual->language_id])
            ->asArray(true)
            ->all();
        $props = ArrayHelper::map($props, 'model_id', 'name');
        return [
            'data' => $data,
            'props' => $props,
            'selected' => Yii::$app->request->get('filter', []),
        ];
    }

    /**
     * Prepares filter query
     *
     * @param $params
     * @param $index
     * @return array
     */
    public static function buildQuery($params, $index)
    {
        $query = ['bool' => ['must' => []]];
        foreach ($params as $propId => $values) {
            $q = ['bool' => ['should' => []]];
            foreach ($values as $val) {
                $q['bool']['should'][] = [
                    'bool' => ['must' => [['term' => ['propertyValues.prop_id' => $propId]],
                        ['term' => ['propertyValues.value_' . LanguageHelper::getCurrent() . '.raw' => $val]]]]
                ];
            }
            $query['bool']['must'][] = $q;
        }
        //will search against all types in given index by default
        return $a = [
            'index' => $index,
            'body' => [
                'query' => [
                    'constant_score' => [
                        'filter' => $query
                    ]
                ]
            ]
        ];
    }
}