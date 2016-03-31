<?php


namespace DevGroup\DataStructure\propertyStorage;


interface FiltrableStorageInterface
{
    /**
     * @param int $propertyId
     * @param array|string $params see [[\yii\db\QueryInterface::where()]] on how to specify this parameter
     * :column is alias to column name used in query
     *
     * @return array of values
     */
    public static function getPropertyValuesByParams($propertyId, $params = '');
}