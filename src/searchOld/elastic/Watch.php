<?php

namespace DevGroup\DataStructure\searchOld\elastic;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValueTranslation;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\searchOld\base\AbstractWatch;
use DevGroup\DataStructure\searchOld\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\searchOld\helpers\LanguageHelper;
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
 * @package DevGroup\DataStructure\searchOld\elastic
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
            foreach ($pks as $id => $types) {
                foreach ($types as $type) {
                    $params['body'][] = [
                        'delete' => [
                            '_index' => $index,
                            '_type' => $type,
                            '_id' => $id
                        ]
                    ];
                }
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
                if (true === is_array($e)) {
                    foreach ($e as $val) {
                        return (count($val) > 0 && false === empty($val[0]));
                    }
                } else {
                    return false === empty($e);
                }
            });
            //collecting storages
            $storage = (new Query())
                ->from(PropertyStorage::tableName())
                ->select('id')
                ->indexBy('class_name')
                ->column();
            //selecting all applicable properties to work with
            $rawProps = (new Query())->from(Property::tableName())
                ->select(['id', 'key', 'storage_id', 'data_type'])
                ->where([
                    'id' => array_keys($workingProps),
                    'in_search' => 1
                ])
                ->all();
            $props = ArrayHelper::map($rawProps, 'id', 'key', 'storage_id');
            foreach ($storage as $className => $id) {
                if (false === isset($props[$id]) || true === empty($props[$id])) {
                    continue;
                }
                $indexData = self::prepareIndexData($className, $props[$id], $workingProps, $index, $model);
                if (false === empty($indexData)) {
                    $this->client->bulk($indexData);
                }
            }
        }
    }

    /**
     * @param $className
     * @param $props
     * @param $workingProps
     * @param $index
     * @param ActiveRecord | HasProperties | PropertiesTrait $model
     * @return array
     */
    private static function prepareIndexData($className, $props, $workingProps, $index, $model)
    {
        $data = [];
        $languages = LanguageHelper::getAll();
        $type = IndexHelper::storageClassToType($className);
        switch ($className) {
            case StaticValues::class :
                $data = self::prepareStatic($props, $model->id, $workingProps, $index, $type, $languages);
                break;
            case EAV::class :
                $data = self::prepareEav($model, $props, $index, $type, $languages);
                break;
        }
        return $data;
    }

    /**
     * Prepares bulk data to store in elasticsearch index for model properties static values
     *
     * @param array $props
     * @param integer $modelId
     * @param array $workingProps
     * @param string $index index name i.e.: page
     * @param string $type index type i.e.: static_values
     * @param array $languages
     * @return array
     */
    private static function prepareStatic($props, $modelId, $workingProps, $index, $type, $languages)
    {
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
        if (false === empty($propertyValues)) {
            $res['body'][] = ['index' => [
                '_id' => $modelId,
                '_index' => $index,
                '_type' => $type,
            ]];
            $res['body'][] = [
                'model_id' => $modelId,
                Search::STATIC_VALUES_FILED => array_values($propertyValues),
            ];
        }
        return $res;
    }

    /**
     * Prepares bulk data to store in elasticsearch index for model properties eav values
     *
     * @param HasProperties | PropertiesTrait $model
     * @param array $props
     * @param string $index
     * @param string $type
     * @param array $languages
     * @return array
     */
    private static function prepareEav($model, $props, $index, $type, $languages)
    {
        $eavTable = $model->eavTable();
        $values = (new Query())
            ->from($eavTable)
            ->where([
                'model_id' => $model->id,
                'property_id' => array_keys($props)
            ])
            ->select([
                'id',
                'property_id',
                'value_integer',
                'value_float',
                'value_string',
                'value_text',
                'language_id'
            ])
            ->all();
        $rows = $data = [];
        foreach ($values as $val) {
            $propKey = isset($props[$val['property_id']]) ? $props[$val['property_id']] : null;
            $row = [
                'eav_value_id' => $val['id'],
                'prop_id' => $val['property_id'],
                'prop_key' => $propKey,
                'value_integer' => $val['value_integer'],
                'value_float' => $val['value_float'],
            ];
            if ($val['language_id'] == 0) {
                $row['utr_text'] = $val['value_text'];
            } else {
                if (false === isset($languages[$val['language_id']])) {
                    continue;
                }
                $row['str_value_' . $languages[$val['language_id']]] = $val['value_string'];
                $row['txt_value_' . $languages[$val['language_id']]] = $val['value_text'];
            }
            $rows[] = $row;
        }
        if (false === empty($rows)) {
            $data['body'][] = ['index' => [
                '_id' => $model->id,
                '_index' => $index,
                '_type' => $type,
            ]];
            $data['body'][] = [
                'model_id' => $model->id,
                Search::EAV_FIELD => $rows,
            ];
        }
        return $data;
    }
}