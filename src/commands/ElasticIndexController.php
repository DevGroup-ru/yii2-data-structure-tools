<?php

namespace DevGroup\DataStructure\commands;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\models\StaticValueTranslation;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\search\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\search\helpers\LanguageHelper;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use yii\console\Controller;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use Elasticsearch\ClientBuilder;


/**
 * Class ElasticIndexController
 *
 * @package DevGroup\DataStructure\commands
 */
class ElasticIndexController extends Controller
{
    private $applicables = [];

    private static $languages = [];

    /** @var Client | null */
    private $client = null;

    /** @var array */
    public $config = [];

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html
     *
     * @var array config to create elasticsearch index to store properties static values data
     */
    private static $staticIndexConfig = [
        'index' => '',
        'body' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
                'static_values' => [
                    'properties' => [
                        'model_id' => [
                            'type' => 'long',
                        ],
                        'propertyValues' => [
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
                ],
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
    private static $valueMap = [
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
     * @inheritdoc
     */
    public function init()
    {
        self::$languages = LanguageHelper::getAll();
        self::prepareMapping();
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
        $staticType = IndexHelper::storageClassToType(StaticValues::class);
        /** @var HasProperties | PropertiesTrait $model */
        foreach ($this->applicables as $indexName => $model) {
            if (true === $this->client->indices()->exists(['index' => $indexName])) {
                $this->client->indices()->delete(['index' => $indexName]);
            }
            $config = self::$staticIndexConfig;
            $config['index'] = $indexName;
            $response = $this->client->indices()->create($config);
            if (true === isset($response['acknowledged']) && $response['acknowledged'] == 1) {
                $staticTable = $model->staticValuesBindingsTable();
                $staticIndexData = self::buildPsvData($staticTable, $indexName, $staticType);
                if (false === empty($staticIndexData['body'])) {
                    $this->client->bulk($staticIndexData);
                }
            }
        }
    }

    /**
     * Prepares language based index mappings according to languages defined in app config multilingual
     */
    private static function prepareMapping()
    {
        foreach (self::$languages as $iso_639_2t) {
            if (true === isset(self::$langToAnalyzer[$iso_639_2t])) {
                self::$staticIndexConfig['body']['mappings']['static_values']['properties']['propertyValues']['properties']['slug_' . $iso_639_2t] = self::$slugMap;
                self::$valueMap['analyzer'] = self::$langToAnalyzer[$iso_639_2t];
                self::$staticIndexConfig['body']['mappings']['static_values']['properties']['propertyValues']['properties']['value_' . $iso_639_2t] = self::$valueMap;
            }
        }
    }

    /**
     * Collects data and builds multidimensional array for store in elasticsearch index
     *
     * @param string $bindingsTable
     * @param string $index
     * @param string $type
     * @return array
     */
    private static function buildPsvData($bindingsTable, $index, $type)
    {
        $res = ['body' => []];
        $props = (new Query())->from(Property::tableName())->select(['id', 'key'])->where(['in_search' => 1])->all();
        $propIds = array_column($props, 'id');
        $propValues = (new Query())->from(StaticValue::tableName())
            ->select(['id', 'property_id'])
            ->where(['property_id' => $propIds])
            ->distinct(true)
            ->all();
        $valueIds = array_column($propValues, 'id');
        $assignedValues = (new Query())->from($bindingsTable)
            ->select(['model_id', 'static_value_id'])
            ->where(['static_value_id' => $valueIds])
            ->all();
        $assignedValueIds = array_column($assignedValues, 'static_value_id');
        $assignedValueIds = array_unique($assignedValueIds);
        $propValuesMap = ArrayHelper::map($propValues, 'id', 'property_id');
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
            $propId = isset($propValuesMap[$value['static_value_id']]) ? $propValuesMap[$value['static_value_id']] : null;
            $propKey = isset($props[$propId]) ? $props[$propId] : null;
            $staticValueId = $value['static_value_id'];
            $propVals = [
                'static_value_id' => $staticValueId,
                'prop_id' => $propId,
                'prop_key' => $propKey,
            ];
            if (true === isset($mapped[$staticValueId])) {
                $propVals = array_merge($propVals, $mapped[$staticValueId]);
            }
            if (false === isset($data[$value['model_id']])) {
                $data[$value['model_id']] = [
                    'model_id' => $value['model_id'],
                    'propertyValues' => [$propVals]
                ];
            } else {
                $data[$value['model_id']]['propertyValues'][] = $propVals;
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

    //TODO implement EAV collecting
    /*
     private static function buildEavData()
      {

      }
     */
}