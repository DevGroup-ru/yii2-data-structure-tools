<?php

namespace DevGroup\DataStructure\search\base;

use yii;

class BaseSearch extends yii\base\Component
{
    /** @var AbstractSearcher[]|array Searchers components configurations in priority order */
    public $searchers = [];

    const SEARCH_RESULT = 0;
    const SEARCH_COUNT = 1;
    const SEARCH_QUERY = 2;
    const SEARCH_IDS = 3;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        foreach ($this->searchers as $key => $config) {
            if (is_array($config) || is_string($config)) {
                $this->searchers[$key] = Yii::createObject($config);
            }
        }
    }

    /**
     * @param string $mainEntityClassname
     *
     * @return \DevGroup\DataStructure\search\base\SearchQuery
     */
    public function search($mainEntityClassname)
    {
        return new SearchQuery($mainEntityClassname, $this);
    }

    /**
     * @param \DevGroup\DataStructure\search\base\SearchQuery $searchQuery
     * @param int $returnType Return type(count, query, result, ids)
     * @return SearchResponse
     */
    public function searchQuery(SearchQuery $searchQuery, $returnType = self::SEARCH_RESULT)
    {
        $result = null;
        foreach ($this->searchers as $searcher) {
            $result = $searcher->search($searchQuery, $returnType);
            if ($result !== null) {
                return $result;
            }
        }

        return $result;
    }
}
