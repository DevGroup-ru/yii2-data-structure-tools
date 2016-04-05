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
     * @return array of values
     */
    public static function getPropertyValuesByParams($propertyId, $params = '');

    /**
     * @param int $propertyId
     * @param array|string $values
     *
     * @param int $returnType
     * if eq FiltrableStorageInterface::RETURN_ALL return array of models
     * if eq FiltrableStorageInterface::RETURN_COUNT return count of records
     * if eq FiltrableStorageInterface::RETURN_QUERY return array of queries
     *
     * @return \yii\db\ActiveRecord[]|\yii\db\ActiveQuery[]|int
     */
    public static function getModelsByPropertyValues(
        $propertyId,
        $values = [],
        $returnType = self::RETURN_ALL
    );
}
