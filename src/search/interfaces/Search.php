<?php

namespace DevGroup\DataStructure\search\interfaces;

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
     * @param string $modelClass
     * @param array $config
     * @return mixed
     */
    public function findInProperties($modelClass= '', $config = []);

    /**
     * Prepares data for filter form render
     *
     * @param array $params
     * @return mixed
     */
    public function filterFormData($params = []);
}