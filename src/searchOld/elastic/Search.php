<?php

namespace DevGroup\DataStructure\searchOld\elastic;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\PropertyTranslation;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\searchOld\base\AbstractSearch;
use DevGroup\DataStructure\searchOld\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\searchOld\helpers\LanguageHelper;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class Search
 * @package DevGroup\DataStructure\searchOld\elastic
 * @property Watch $watcher
 */
class Search extends AbstractSearch
{

    const STATIC_VALUES_FILED = 'static_data';
    const EAV_FIELD = 'eav_data';

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
    public function filterByProperties($modelClass = '', $config = [], $params = [])
    {
        $index = IndexHelper::classToIndex($modelClass);
        if (true === empty($index)) {
            return [];
        }
        $types = self::prepareTypes($config);
        self::filterInput($params);
        $query = self::buildFilterQuery($params, $index, $types);
        $res = IndexHelper::primaryKeysByCondition($this->client, $query);
        return array_keys($res);
    }

    /**
     * @inheritdoc
     */
    public function filterByPropertiesRange($modelClass = '', $config = [], $params = [])
    {
        $index = IndexHelper::classToIndex($modelClass);
        if (true === empty($index) || count($params) == 0) {
            return [];
        }
        $fromKey = isset($config['fromKey']) ? $config['fromKey'] : 'min';
        $toKey = isset($config['toKey']) ? $config['toKey'] : 'max';
        $types = self::prepareTypes($config);
        self::filterInput($params);
        $storageToId = (new Query())
            ->from(PropertyStorage::tableName())
            ->select('class_name')
            ->where(['class_name' => array_keys($types)])
            ->indexBy('id')
            ->column();
        $propData = (new Query())
            ->from(Property::tableName())
            ->select(['id', 'data_type', 'storage_id'])
            ->where([
                'id' => array_keys($params),
                'storage_id' => array_keys($storageToId),
                'in_search' => 1
            ])
            ->indexBy('id')
            ->all();
        $currentLang = LanguageHelper::getCurrent();
        $match = [];
        $condition = [];
        $ranges = 0;
        foreach ($params as $propId => $values) {
            if (false === isset($propData[$propId])) {
                continue;
            }
            if (true === isset($values[$fromKey])) {
                $fromValue = Property::castValueToDataType($values[$fromKey], $propData[$propId]['data_type']);
                $condition['gte'] = $fromValue;
            }
            if (true === isset($values[$toKey])) {
                $toValue = Property::castValueToDataType($values[$toKey], $propData[$propId]['data_type']);
                $condition['lte'] = $toValue;
            }
            if (false === isset($values[$toKey]) && false === isset($values[$fromKey])) {
                continue;
            }
            $isNumberColumn = in_array(
                $propData[$propId]['data_type'],
                [Property::DATA_TYPE_FLOAT, Property::DATA_TYPE_INTEGER]
            );
            if (false === $isNumberColumn) {
                continue;
            }
            $indexColumn = self::dataTypeToIndexField($propData[$propId]['data_type']);
            switch ($storageToId[$propData[$propId]['storage_id']]) {
                case EAV::class :
                    $match[] = [
                        'bool' => [
                            'must' => [
                                ['term' => [self::EAV_FIELD . '.prop_id' => $propId]],
                                ['range' => [self::EAV_FIELD . '.' . $indexColumn => $condition]]
                            ]
                        ]
                    ];
                    $ranges++;
                    break;
                case StaticValues::class :
                    $match[] = [
                        'bool' => [
                            'must' => [
                                ['term' => [self::STATIC_VALUES_FILED . '.prop_id' => $propId]],
                                ['range' =>
                                    [self::STATIC_VALUES_FILED . '.value_' . $currentLang . '.raw' => $condition]
                                ]
                            ]
                        ]
                    ];
                    $ranges++;
                    break;
            }
        }
        if ($ranges == 0) {
            return [];
        }
        $query = ['bool' => ['should' => $match]];
        $a = [
            'index' => $index,
            'type' => implode(',', $types),
            'body' => [
                'query' => $query
            ]
        ];
        $res = IndexHelper::primaryKeysByCondition($this->client, $a);
        return array_keys($res);
    }

    /**
     * @inheritdoc
     */
    public function findInProperties(
        $modelClass = '',
        $config = [],
        $params = [],
        $content = '',
        $intersect = false
    )
    {
        $index = IndexHelper::classToIndex($modelClass);
        if (true === empty($index) || true === empty($content) || count($params) == 0) {
            return [];
        }
        $types = self::prepareTypes($config);
        $storageToId = (new Query())
            ->from(PropertyStorage::tableName())
            ->select('class_name')
            ->where(['class_name' => array_keys($types)])
            ->indexBy('id')
            ->column();
        $propData = (new Query())
            ->from(Property::tableName())
            ->select(['id', 'data_type', 'storage_id'])
            ->where([
                'id' => $params,
                'storage_id' => array_keys($storageToId),
                'in_search' => 1
            ])
            ->indexBy('id')
            ->all();
        $currentLang = LanguageHelper::getCurrent();
        $match = [];
        $keyCondition = (true === $intersect) ? 'must' : 'should';
        foreach ($propData as $id => $data) {
            switch ($storageToId[$data['storage_id']]) {
                case StaticValues::class :
                    $match[] = [
                        'bool' => [
                            'must' => [
                                ['term' => [self::STATIC_VALUES_FILED . '.prop_id' => $id]],
                            ],
                            'should' => [
                                ['term' => [self::STATIC_VALUES_FILED . '.value_' . $currentLang . '.raw' => $content]],
                                ['term' => [self::STATIC_VALUES_FILED . '.aliases' => $content]]
                            ],
                            "minimum_should_match" => 1,

                        ]
                    ];
                    break;
                case EAV::class :
                    $isNumeric = in_array(
                        $data['data_type'],
                        [Property::DATA_TYPE_BOOLEAN, Property::DATA_TYPE_FLOAT, Property::DATA_TYPE_INTEGER]
                    );
                    $searchField = self::dataTypeToIndexField($data['data_type']);
                    if (true === is_numeric($content) && true === $isNumeric) {
                        $content = Property::castValueToDataType($content, $data['data_type']);
                        $row = ['term' => [self::EAV_FIELD . '.' . $searchField => $content]];
                    } else if (false === $isNumeric) {
                        $row = ['match' => [self::EAV_FIELD . '.' . $searchField => [
                            'query' => $content,
                            'type' => 'phrase',
                            'operator' => 'and'
                        ]]];
                    } else {
                        continue;
                    }
                    $match[] = [
                        'bool' => [
                            'must' => [
                                ['term' => [self::EAV_FIELD . '.prop_id' => $id]],
                                $row
                            ]
                        ]
                    ];
                    break;
            }
        }
        if (true === empty($match)) {
            return [];
        }
        $query = ['bool' => [$keyCondition => $match]];
        $a = [
            'index' => $index,
            'type' => implode(',', $types),
            'body' => [
                'query' => $query
            ]
        ];
        $res = IndexHelper::primaryKeysByCondition($this->client, $a);
        return array_keys($res);
    }

    /**
     * Future feature. This method have ability to use combined extended search & filter form
     * TODO implement
     *
     * @codeCoverageIgnore
     *
     * @param string $modelClass
     * @param array $config
     * @param array $params
     * @param bool $intersect
     * @return array
     */
    final private function extendedFilter($modelClass = '', $config = [], $params = [], $intersect = false)
    {
        //перечсечение: внутри свойства ИЛИ между свойствами И
        //объединение: везде ИЛИ
        $index = IndexHelper::classToIndex($modelClass);
        if (true === empty($index)) {
            return [];
        }
        $types = self::prepareTypes($config);
        $storageToId = (new Query())
            ->from(PropertyStorage::tableName())
            ->select('id')
            ->where(['class_name' => array_keys($types)])
            ->indexBy('class_name')
            ->column();
        $eavQuery = [];
        foreach ($types as $storageClass => $type) {
            switch ($storageClass) {
                case StaticValues::class :
                    //TODO include filter query against static values
                    break;
                case EAV::class :
                    //TODO refactor
                    $propData = (new Query())
                        ->from(Property::tableName())
                        ->select('data_type')
                        ->where([
                            'id' => array_keys($params),
                            'storage_id' => $storageToId[EAV::class]
                        ])
                        ->indexBy('id')
                        ->column();
                    $queryCondition = (true === $intersect) ? 'must' : 'should';
                    foreach ($params as $propId => $vals) {
                        if (false === isset($propData[$propId])) {
                            continue;
                        }
                        $vals = is_array($vals) ? $vals : [$vals];
                        //query between different props
                        $searchField = self::dataTypeToIndexField($propData[$propId]);
                        $match = [];
                        $isTranslatable = in_array(
                            $propData[$propId],
                            [Property::DATA_TYPE_STRING, Property::DATA_TYPE_TEXT]
                        );
                        foreach ($vals as $val) {
                            //query between values of one property
                            $fields = [
                                'query' => $val,
                                'operator' => 'and',
                            ];
                            if (true === $isTranslatable) {
                                $fields['type'] = 'phrase';
                            }
                            $match[] = ['match' => [self::EAV_FIELD . '.' . $searchField => $fields]];
                        }
                        $propQuery = ['bool' => ['should' => $match]];
                        $eavQuery['bool'][$queryCondition][] = $propQuery;
                    }
                    break;
            }
        }
        //TODO combine upper queries like im manual below
        /**
         * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_search_operations.html#_a_more_complicated_example
         */
        $a = [
            'index' => $index,
            'body' => [
                'query' => $eavQuery
            ]
        ];
        $res = IndexHelper::primaryKeysByCondition($this->client, $a);
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
                            'path' => self::STATIC_VALUES_FILED,
                        ],
                        'aggs' => [
                            'prop_id' => [
                                'terms' => [
                                    'field' => self::STATIC_VALUES_FILED . '.prop_id',
                                ],
                                'aggs' => [
                                    'values' => [
                                        'terms' => [
                                            'field' => self::STATIC_VALUES_FILED . '.value_' . LanguageHelper::getCurrent() . '.raw'
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
     * @param array $params
     * @param string $index
     * @return array
     */
    public static function buildFilterQuery($params, $index)
    {
        $query = ['bool' => ['must' => []]];
        $currentLang = LanguageHelper::getCurrent();
        foreach ($params as $propId => $values) {
            $q = ['bool' => ['should' => []]];
            foreach ($values as $val) {
                $q['bool']['should'][] = [
                    'bool' => ['must' => [['term' => [self::STATIC_VALUES_FILED . '.prop_id' => $propId]],
                        ['term' => [self::STATIC_VALUES_FILED . '.value_' . $currentLang . '.raw' => $val]]]]
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

    /**
     * Leave only not empty values to work with
     *
     * @param $params
     */
    protected static function filterInput(&$params)
    {
        $params = array_filter($params, function ($v) {
            if (true === is_array($v)) {
                $first = reset($v);
                return (count($v) > 0 && false === empty($first));
            } else {
                return false === empty($v);
            }
        });
    }

    /**
     * Cast property data_type to elastic index column
     *
     * @param $type
     * @return string
     */
    protected static function dataTypeToIndexField($type)
    {
        $key = '';
        $currentLang = LanguageHelper::getCurrent();
        switch ($type) {
            case Property::DATA_TYPE_STRING :
                $key = 'str_value_' . $currentLang;
                break;
            case Property::DATA_TYPE_TEXT :
                $key = 'txt_value_' . $currentLang;
                break;
            case Property::DATA_TYPE_BOOLEAN :
            case Property::DATA_TYPE_INTEGER :
                $key = 'value_integer';
                break;
            case Property::DATA_TYPE_FLOAT :
                $key = 'value_float';
                break;
            case Property::DATA_TYPE_INVARIANT_STRING :
            case Property::DATA_TYPE_PACKED_JSON :
                $key = 'utr_text';
                break;
        }
        return $key;
    }

    /**
     * Prepares list of types to search against for
     *
     * @param array $config
     * @return array
     */
    protected static function prepareTypes($config)
    {
        $list = isset($config['storage']) ? $config['storage'] : [];
        $list = is_array($list) ? $list : [$list];
        if (count($list) == 0) {
            $list[] = StaticValues::class;
        }
        foreach ($list as $i => $storageClass) {
            $list[$storageClass] = IndexHelper::storageClassToType($storageClass);
            unset($list[$i]);
        }
        return $list;
    }
}