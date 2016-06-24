<?php

namespace DevGroup\DataStructure\search\interfaces;

use yii\db\ActiveRecord;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\TagDependencyHelper\TagDependencyTrait;

/**
 * Interface Search
 *
 * @package DevGroup\DataStructure\search\interfaces
 */
interface Search
{
    /**
     * Performs search in model content fields
     *
     * @param string $modelClass
     * @return mixed
     */
    public function findInContent($modelClass = '');

    /**
     * Performs search in assigned properties values
     *
     * @param string|ActiveRecord|PropertiesTrait|HasProperties|TagDependencyTrait $modelClass
     * @param array $config
     * @param array $params
     * @return array
     */
    public function findInProperties($modelClass = '', $config = [], $params = []);

    /**
     * Prepares data for filter form render
     *
     * @param array $params
     * @return mixed
     */
    public function filterFormData($params = []);
}
