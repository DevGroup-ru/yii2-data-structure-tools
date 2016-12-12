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
     * @param array $config array to pass to SearchQuery constructor
     *
     * @return SearchQuery
     */
    public function search($mainEntityClassname, $config = [])
    {
        return Yii::$container->get(SearchQuery::class, [$mainEntityClassname, $this, $config]);
    }

    /**
     * @param string|array $json
     *
     * @return \DevGroup\DataStructure\search\base\SearchQuery
     */
    public function searchFromJson($json)
    {
        if (is_string($json)) {
            $json = yii\helpers\Json::decode($json);
        }
        if (isset($json['mainEntityClassName'], $json['searchQuery']) === false) {
            throw new \RuntimeException("mainEntityClassName and searchQuery needed to be in json for search");
        }
        $mainEntityClassName = $json['mainEntityClassName'];
        $q = new SearchQuery($mainEntityClassName, $this, $json['searchQuery']);
        $q->pagination($q->pagination);
        return $q;
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
