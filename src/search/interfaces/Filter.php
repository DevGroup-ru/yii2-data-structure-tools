<?php

namespace DevGroup\DataStructure\search\interfaces;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use yii\db\ActiveRecord;
use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;

/**
 * Interface Filter
 *
 * @package DevGroup\DataStructure\search\interfaces
 */
interface Filter
{
    const RETURN_ALL = 0;
    const RETURN_COUNT = 1;
    const RETURN_QUERY = 2;
    const RETURN_IDS = 3;

    /**
     * @param int $propertyId
     * @param array|string $params see [[\yii\db\QueryInterface::where()]] on how to specify this parameter
     * [column] is alias to column name used in query
     *
     * @param null|string|\yii\caching\Dependency $customDependency
     * @param string $customKey
     * @param int $cacheLifetime
     *
     * @return array of values
     */
    public static function getPropertyValuesByParams(
        $propertyId,
        $params = '',
        $customDependency = null,
        $customKey = '',
        $cacheLifetime = 86400
    );

    /**
     * @param int $propertyId
     * @param array|string $values
     *
     * @param int $returnType
     * if eq FiltrableStorageInterface::RETURN_ALL return array of models
     * if eq FiltrableStorageInterface::RETURN_COUNT return count of records
     * if eq FiltrableStorageInterface::RETURN_QUERY return array of queries
     *
     * @param null|string|\yii\caching\Dependency $customDependency
     *
     * @param int $cacheLifetime
     *
     * @return int|\yii\db\ActiveQuery[]|\yii\db\ActiveRecord[]
     */
    public static function getModelsByPropertyValues(
        $propertyId,
        $values = [],
        $returnType = self::RETURN_ALL,
        $customDependency = null,
        $cacheLifetime = 86400
    );


    /**
     * @param string|ActiveRecord|HasProperties|PropertiesTrait|TagDependencyTrait $modelClass
     *
     * @param array $selections selected properties ids and theirs values to filter for like:
     *  [
     *   'property_1_Id' => [
     *         'property_1_value_1_id',
     *         'property_1_value_2_id',
     *         'property_1_value_3_id',
     *          ...
     *    ],
     *   'property_2_Id' => [
     *         'property_2_value_1_id',
     *         'property_2_value_2_id',
     *         'property_2_value_3_id',
     *          ...
     *    ],
     *  ]
     * @param null|string|\yii\caching\Dependency $customDependency
     * @param int $cacheLifetime
     * @return false|array of all found model ids
     */
    public static function getModelIdsByValues(
        $modelClass,
        $selections,
        $customDependency = null,
        $cacheLifetime = 86400);

    /**
     * Prepares all available properties values for use in filter form view.
     *
     * @param string $modelClass correct model class name. Model have to have PropertiesTrait
     * @param array $config
     * @param null|string|\yii\caching\Dependency $customDependency
     * @param int $cacheLifetime
     * @return array
     */
    public static function filterFormSet(
        $modelClass,
        $config,
        $customDependency = null,
        $cacheLifetime = 86400
    );
}
