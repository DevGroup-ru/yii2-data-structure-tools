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
     * @return integer[]
     */
    public function filterByProperties($modelClass = '', $config = [], $params = []);

    /**
     * Performs search in assigned properties values by content
     *
     * @param string|ActiveRecord|PropertiesTrait|HasProperties|TagDependencyTrait $modelClass entity class name
     * @param array $config list of params for configuration of a search component
     * @param array $params list of property names
     * @param string $content a keyword or a phrase for searching
     * @param string $intersect whether to return model ids that contains `content` in all `params` at the same time
     * @return integer[] model ids list
     */
    public function findInProperties(
        $modelClass = '',
        $config = [],
        $params = [],
        $content = '',
        $intersect = false
    );

    /**
     * Performs a search in range of property values
     *
     * @param string|ActiveRecord|PropertiesTrait|HasProperties|TagDependencyTrait $modelClass
     * @param array $config
     * @param array $params
     * @return mixed
     */
    public function filterByPropertiesRange($modelClass = '', $config = [], $params = []);

    /**
     * Prepares data for filter form render
     *
     * @param array $params
     * @return mixed
     */
    public function filterFormData($params = []);
}
