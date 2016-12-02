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
     * This method allows to perform search against defined properties list among we checked to use in search in backend
     * part and a query string.
     *
     * Lets imagine we have usual search field somewhere on our site
     * Using this method we can allow users find some useful data with this method. Somewhere in your controller you can
     * place code like:
     * ```php
     * public function actionSs()
     * {
     *  $params = [2,5,7];
     *  $content = Yii::$app->request->get('query', "");
     *  $search = Yii::$app->getModule('properties')->getSearch();
     *  $config = ['storage' => [
     *          EAV::class,
     *          StaticValues::class,
     *      ]
     *  ];
     *  $modelIds = $search->findInProperties(Realty::class, $config, $params, $content);
     * }
     *
     * where
     * - `$params` - property ids list that we used to search against
     * - `$config` - config array, where `storage` key will be used to define what kind of storage we need to use
     * - `$content` - query string, where you can write any query you want
     *
     * `$modelIds` will contain all found model ids
     *
     * depending chosen type of union or intersection result query inside will be like this:
     * - `$intersect = true`
     * SELECT * ... WHERE  (property_id = 2 AND property_value = 'query')
     *                 AND (property_id = 5 AND property_value = 'query')
     *                 AND (property_id = 7 AND property_value = 'query')
     *
     * - `$intersect = false`
     * SELECT * ... WHERE  (property_id = 2 AND property_value = 'query')
     *                 OR  (property_id = 5 AND property_value = 'query')
     *                 OR  (property_id = 7 AND property_value = 'query')
     *
     * @param string|ActiveRecord|PropertiesTrait|HasProperties|TagDependencyTrait $modelClass entity class name
     * @param array $config list of params for configuration of a search component
     * @param array $params array of property ids that looks like `[2,45,6,7]`
     * @param string $content a keyword or a phrase for searching
     * @param boolean $intersect whether to return model ids that contains `content` in all `params` at the same time
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
     * Input request parameters should look like:
     * [
     *  2 => [
     *     'min' => 20,
     *     'max' => 100,
     *   ],
     *  3 => [
     *     'min' => 1,
     *     'max' => 34,
     *   ],
     * ]
     * where 2,3 are the properties ids
     *
     * @param string|ActiveRecord|PropertiesTrait|HasProperties|TagDependencyTrait $modelClass
     * @param array $config where `storage` key will be used to define what kind of storage we need to use
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
