<?php

namespace DevGroup\DataStructure\searchOld\elastic\helpers;

use Elasticsearch\Client;
use yii\base\Object;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class IndexHelper
 *
 * @package DevGroup\DataStructure\searchOld\elastic\helpers
 */
class IndexHelper extends Object
{
    /**
     * Builds index name according to given model class
     *
     * @param string $className
     * @return string
     */
    public static function classToIndex($className)
    {
        if (false === is_string($className)) {
            return '';
        }
        $modelClass = strtolower($className);
        return StringHelper::basename($modelClass);
    }

    /**
     * Builds index type according to given property storage class name
     *
     * @param $storageClass
     * @return string
     */
    public static function storageClassToType($storageClass)
    {
        if (false === is_string($storageClass)) {
            return '';
        }
        $name = StringHelper::basename($storageClass);
        return Inflector::camel2id($name, '_');
    }

    /**
     * Performs fast scan for docs ids in elasticsearch indices
     *
     * @param Client $client
     * @param array $condition query condition to find docs
     * @return array
     */
    public static function primaryKeysByCondition($client, $condition)
    {
        $primaryKeys = [];
        $count = $client->count($condition);
        $count = empty($count['count']) ? 10 : $count['count'];
        $condition['size'] = $count;
        $condition['_source'] = true;
        $res = $client->search($condition);
        if (false === empty($res['hits']['hits'])) {
            foreach ($res['hits']['hits'] as $doc) {
                if (false === isset($primaryKeys[$doc['_id']])) {
                    $primaryKeys[$doc['_id']] = [$doc['_type']];
                } else {
                    if (false === in_array($doc['_type'], $primaryKeys[$doc['_id']])) {
                        $primaryKeys[$doc['_id']][] = $doc['_type'];
                    }
                }
            }
        }
        return $primaryKeys;
    }
}