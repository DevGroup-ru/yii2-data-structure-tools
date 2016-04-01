<?php


namespace DevGroup\DataStructure\propertyStorage;

use yii\db\ActiveRecord;

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

    /**
     * @param int $propertyId
     * @param array|string $values
     *
     * @return ActiveRecord[]
     */
    public static function getModelsByPropertyValuesParams($propertyId, $values = []);
}
