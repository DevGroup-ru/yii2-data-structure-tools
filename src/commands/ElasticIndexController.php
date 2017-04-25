<?php

namespace DevGroup\DataStructure\commands;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\models\StaticValueTranslation;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\searchOld\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\searchOld\elastic\Search;
use DevGroup\DataStructure\searchOld\helpers\LanguageHelper;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use yii\console\Controller;
use Yii;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use Elasticsearch\ClientBuilder;
use yii\helpers\Json;

/**
 * Class ElasticIndexController
 *
 * @package DevGroup\DataStructure\commands
 */
class ElasticIndexController extends Controller
{
    private $applicables = [];

    /**
     * Contains all properties to be used in search
     *
     * @var array
     */
    private static $props = [];

    /**
     * Contains associative array of all types of storage to be used in search
     * key - class name
     * value - id
     *
     * @var array
     */
    private static $storage = [];

    private static $languages = [];

    /** @var Client | null */
    private $client = null;

    /** @var array */
    public $config = [];

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html
     *
     * @var array config to create elasticsearch index
     */
    private static $indexConfig = [
        'index' => '',
        'body' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
            ]
        ]
    ];

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-2_codes
     *
     * @var array
     */
    private static $langToAnalyzer = [
        'ara' => 'arabic',
        'hye' => 'armenian',
        'eus' => 'basque',
        //'' => 'brazilian', ?
        'bul' => 'bulgarian',
        'cat' => 'catalan',
        //'' => 'cjk',
        'ces' => 'czech',
        'dan' => 'danish',
        'nld' => 'dutch',
        'eng' => 'english',
        'fin' => 'finnish',
        'fra' => 'french',
        'glg' => 'galician',
        'deu' => 'german',
        'grk' => 'greek',
        'hin' => 'hindi',
        'hun' => 'hungarian',
        'ind' => 'indonesian',
        'gle' => 'irish',
        'ita' => 'italian',
        'lav' => 'latvian',
        'lit' => 'lithuanian',
        'nor' => 'norwegian',
        'fas' => 'persian',
        'por' => 'portuguese',
        'ron' => 'romanian',
        'rus' => 'russian',
        'ckb' => 'sorani',
        'spa' => 'spanish',
        'swe' => 'swedish',
        'tur' => 'turkish',
        'tha' => 'thai',
    ];

    /**
     * Each value here are represented in two ways:
     *  - not analysed, to use in filters
     *  - with language based analyser, to use in search
     *
     * 'type' => 'nested' allows us to perform complicated search queries with
     * strict compliance of multiple properties values
     *
     * @var array
     */
    private static $staticMapping = [
        'properties' => [
            'model_id' => [
                'type' => 'long',
            ],
            Search::STATIC_VALUES_FILED => [
                'type' => 'nested',
                'include_in_parent' => true,
                'properties' => [
                    'static_value_id' => [
                        'type' => 'long'
                    ],
                    'prop_id' => [
                        'type' => 'long'
                    ],
                    'prop_key' => [
                        'type' => 'string',
                        'index' => "not_analyzed"
                    ],
                    //slug
                    //...
                    //values
                    //...
                ]
            ],
        ],
    ];

    /**
     * Part of language dependent index mapping for property value slug
     *
     * @var array
     */
    private static $slugMap = [
        'type' => 'string',
        'index' => 'not_analyzed'
    ];

    /**
     * Part of language dependent index mapping for property value
     *
     * @var array
     */
    private static $staticValueMap = [
        'type' => 'string',
        'analyzer' => '',
        'fields' => [
            'raw' => [
                'type' => 'string',
                'index' => 'not_analyzed'
            ],
        ]
    ];

    /**
     * Here is no need to store not analyzed copy of property value
     * EAV values does not taking a part in filtering
     *
     * @var array
     */
    private static $eavMapping = [
        'properties' => [
            'model_id' => [
                'type' => 'long',
            ],
            Search::EAV_FIELD => [
                'type' => 'object',
                'properties' => [
                    'eav_value_id' => [
                        'type' => 'long'
                    ],
                    'prop_id' => [
                        'type' => 'long'
                    ],
                    'prop_key' => [
                        'type' => 'string',
                        'index' => "not_analyzed"
                    ],
                    'value_integer' => [
                        'type' => 'long'
                    ],
                    'value_float' => [
                        'type' => 'float'
                    ],
                    'utr_text' => [
                        'type' => 'string',
                        'index' => "not_analyzed"
                    ],
                    //values
                    //...
                ]
            ],
        ],
    ];

    /**
     * Part of language dependent index mapping for property value
     *
     * @var array
     */
    private static $eavValueMap = [
        'type' => 'string',
        'analyzer' => '',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        self::$languages = LanguageHelper::getAll();
        $apl = ApplicablePropertyModels::find()->asArray(true)->all();
        $client = ClientBuilder::create();
        /** @var Module $searchModule */
        $searchModule = Yii::$app->getModule('properties', false);
        $hosts = empty($searchModule->searchConfig['hosts']) ? [] : $searchModule->searchConfig['hosts'];
        if (true === is_array($hosts) && count($hosts) > 0) {
            $client->setHosts($hosts);
        }
        $this->client = $client->build();
        foreach ($apl as $row) {
            if (true === class_exists($row['class_name'])) {
                $indexName = IndexHelper::classToIndex($row['class_name']);
                $this->applicables[$indexName] = new $row['class_name'];
            }
        }
        self::$props = (new Query())
            ->from(Property::tableName())
            ->select(['id', 'key', 'storage_id'])
            ->where(['in_search' => 1])
            ->all();
        self::$storage = (new Query())
            ->from(PropertyStorage::tableName())
            ->select(['id'])
            ->indexBy('class_name')
            ->column();
        parent::init();
    }

    /**
     * Creates and fills in indices
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function actionFillIndex()
    {
        try {
            $this->client->ping();
        } catch (NoNodesAvailableException $e) {
            $this->stderr($e->getMessage() . ', maybe you first need to configure and run elasticsearch' . PHP_EOL);
            return;
        }
        /** @var HasProperties | PropertiesTrait $model */
        foreach ($this->applicables as $indexName => $model) {
            if (true === $this->client->indices()->exists(['index' => $indexName])) {
                $this->client->indices()->delete(['index' => $indexName]);
            }
            $config = self::prepareIndexConfig();
            $config['index'] = $indexName;
            $response = $this->client->indices()->create($config);
            if (true === isset($response['acknowledged']) && $response['acknowledged'] == 1) {
                foreach (self::$storage as $className => $id) {
                    $indexData = self::prepareIndexData($model, $indexName, $className, $id);
                    if (false === empty($indexData['body'])) {
                        $this->client->bulk($indexData);
                    }
                }
            }
        }
    }

    /**
     * Prepares config for all applicable property storage
     *
     * @return array
     */
    private static function prepareIndexConfig()
    {
        foreach (self::$storage as $className => $id) {
            $key = IndexHelper::storageClassToType($className);
            $config = self::prepareMapping($className);
            if (false === empty($config)) {
                self::$indexConfig['body']['mappings'][$key] = $config;
            }
        }
        return self::$indexConfig;
    }

    /**
     * Prepares language based index mappings according to languages defined in app config multilingual
     *
     * @param string $className PropertyStorage class name
     * @return array
     */
    private static function prepareMapping($className)
    {
        $mapping = [];
        foreach (self::$languages as $iso_639_2t) {
            if (true === isset(self::$langToAnalyzer[$iso_639_2t])) {
                switch ($className) {
                    case StaticValues::class :
                        self::$staticMapping['properties'][Search::STATIC_VALUES_FILED]['properties']['slug_' . $iso_639_2t] = self::$slugMap;
                        self::$staticValueMap['analyzer'] = self::$langToAnalyzer[$iso_639_2t];
                        self::$staticMapping['properties'][Search::STATIC_VALUES_FILED]['properties']['value_' . $iso_639_2t] = self::$staticValueMap;
                        $mapping = self::$staticMapping;
                        break;
                    case EAV::class :
                        self::$eavValueMap['analyzer'] = self::$langToAnalyzer[$iso_639_2t];
                        self::$eavMapping['properties'][Search::EAV_FIELD]['properties']['str_value_' . $iso_639_2t] = self::$staticValueMap;
                        self::$eavMapping['properties'][Search::EAV_FIELD]['properties']['txt_value_' . $iso_639_2t] = self::$staticValueMap;
                        $mapping = self::$eavMapping;
                        break;
                }
            }
        }
        return $mapping;
    }

    /**
     * @param HasProperties | PropertiesTrait $model
     * @param string $indexName
     * @param string $className
     * @param int $id
     * @return array
     */
    private static function prepareIndexData($model, $indexName, $className, $id)
    {
        $indexType = IndexHelper::storageClassToType($className);
        $props = array_filter(self::$props, function ($v) use ($id) {
            return $v['storage_id'] == $id;
        });
        $indexData = [];
        switch ($className) {
            case StaticValues::class :
                $staticTable = $model->staticValuesBindingsTable();
                $indexData = self::buildPsvData($staticTable, $indexName, $indexType, $props);
                break;
            case EAV::class :
                $eavTable = $model->eavTable();
                $indexData = self::buildEavData($eavTable, $indexName, $indexType, $props);
                break;
        }
        return $indexData;
    }

    /**
     * Collects data and builds multidimensional array for store in elasticsearch index
     *
     * @param string $bindingsTable
     * @param string $index
     * @param string $type
     * @param $props
     * @return array
     */
    private static function buildPsvData($bindingsTable, $index, $type, $props)
    {
        $res = ['body' => []];
        $propIds = array_column($props, 'id');
        $propValues = (new Query())->from(StaticValue::tableName())
            ->select(['id', 'property_id', 'packed_json_params'])
            ->indexBy('id')
            ->where(['property_id' => $propIds])
            ->distinct(true)
            ->all();
        $valueIds = array_keys($propValues);
        if (count($valueIds) > 0) {
            $assignedValues = (new Query())->from($bindingsTable)
                ->select(['model_id', 'static_value_id'])
                ->where(new Expression('`static_value_id` IN (' . implode(',', $valueIds) . ')'))
                ->all();
        } else {
            $assignedValues = [];
        }
        $assignedValueIds = array_column($assignedValues, 'static_value_id');
        $assignedValueIds = array_unique($assignedValueIds);
        $props = ArrayHelper::map($props, 'id', 'key');
        $translations = StaticValueTranslation::find()
            ->select(['model_id', 'language_id', 'name', 'slug'])
            ->where(['model_id' => $assignedValueIds])
            ->asArray(true)
            ->all();
        $mapped = [];
        foreach ($translations as $one) {
            if (false === isset($mapped[$one['model_id']])) {
                $mapped[$one['model_id']] = [
                    'slug_' . self::$languages[$one['language_id']] => $one['slug'],
                    'value_' . self::$languages[$one['language_id']] => $one['name'],
                ];
            } else {
                $mapped[$one['model_id']]['value_' . self::$languages[$one['language_id']]] = $one['name'];
                $mapped[$one['model_id']]['slug_' . self::$languages[$one['language_id']]] = $one['slug'];
            }
        }
        $data = [];
        foreach ($assignedValues as $i => $value) {
            $propId = isset($propValues[$value['static_value_id']]['property_id']) ?
                $propValues[$value['static_value_id']]['property_id'] :
                null;
            $propKey = isset($props[$propId]) ? $props[$propId] : null;
            $staticValueId = $value['static_value_id'];
            $propVals = [
                'static_value_id' => $staticValueId,
                'prop_id' => $propId,
                'prop_key' => $propKey,
            ];

            if(isset($propValues[$value['static_value_id']]['packed_json_params'])) {
                $params = Json::decode($propValues[$value['static_value_id']]['packed_json_params']);
                if(empty($params['aliases']) === false) {
                    $propVals['aliases'] = (array) array_values($params['aliases']);
                }
            }

            if (true === isset($mapped[$staticValueId])) {
                $propVals = array_merge($propVals, $mapped[$staticValueId]);
            }
            if (false === isset($data[$value['model_id']])) {
                $data[$value['model_id']] = [
                    'model_id' => $value['model_id'],
                    Search::STATIC_VALUES_FILED => [$propVals]
                ];
            } else {
                $data[$value['model_id']][Search::STATIC_VALUES_FILED][] = $propVals;
            }
        }
        foreach ($data as $id => $row) {
            $res['body'][] = ['index' => [
                '_id' => $id,
                '_index' => $index,
                '_type' => $type,
            ]];
            $res['body'][] = $row;
        }
        return $res;
    }

    private static function buildEavData($eavTable, $index, $type, $props)
    {
        $res = ['body' => []];
        $propIds = array_column($props, 'id');
        $props = ArrayHelper::map($props, 'id', 'key');
        $applValues = (new Query)
            ->from($eavTable)
            ->select([
                'id',
                'model_id',
                'property_id',
                'value_integer',
                'value_float',
                'value_string',
                'value_text',
                'language_id'
            ])
            ->where(['property_id' => $propIds])
            ->all();
        $rows = [];
        foreach ($applValues as $row) {
            $propId = $row['property_id'];
            $propKey = isset($props[$propId]) ? $props[$propId] : null;
            $data = [
                'eav_value_id' => $row['id'],
                'prop_id' => $propId,
                'prop_key' => $propKey,
                'value_integer' => $row['value_integer'],
                'value_float' => $row['value_float'],
            ];
            if ($row['language_id'] == 0) {
                $data['utr_text'] = $row['value_text'];
            } else {
                if (true === isset(self::$languages[$row['language_id']])) {
                    $data['str_value_' . self::$languages[$row['language_id']]] = $row['value_string'];
                    $data['txt_value_' . self::$languages[$row['language_id']]] = $row['value_text'];
                }
            }
            if (true === isset($rows[$row['model_id']])) {
                $rows[$row['model_id']][Search::EAV_FIELD][] = $data;
            } else {
                $rows[$row['model_id']] = [
                    'model_id' => $row['model_id'],
                    Search::EAV_FIELD => [$data]
                ];
            }

        }
        foreach ($rows as $id => $row) {
            $res['body'][] = ['index' => [
                '_id' => $id,
                '_index' => $index,
                '_type' => $type,
            ]];
            $res['body'][] = $row;
        }
        return $res;
    }
}