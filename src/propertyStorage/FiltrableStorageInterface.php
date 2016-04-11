<?php


namespace DevGroup\DataStructure\propertyStorage;

interface FiltrableStorageInterface
{
    const RETURN_ALL = 0;
    const RETURN_COUNT = 1;
    const RETURN_QUERY = 2;

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
}
