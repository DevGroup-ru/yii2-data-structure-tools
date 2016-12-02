<?php

namespace DevGroup\DataStructure\search\base;

use yii;

class SearchEvent extends yii\base\Event
{
    /** @var \DevGroup\DataStructure\search\base\SearchQuery  */
    public $searchQuery;
    /** @var int  */
    public $returnType;

    /** @var  SearchResponse */
    public $response;

    /** @var array  */
    public $params = [];

    public function __construct(SearchQuery $searchQuery, $returnType = BaseSearch::SEARCH_RESULT, SearchResponse $response, array $config = [])
    {
        $this->searchQuery = &$searchQuery;
        $this->returnType = $returnType;
        $this->response = &$response;
        parent::__construct($config);
    }

    /**
     * @return SearchQuery
     */
    public function searchQuery()
    {
        return $this->searchQuery;
    }

    /**
     * @return int
     */
    public function returnType()
    {
        return $this->returnType;
    }
}
