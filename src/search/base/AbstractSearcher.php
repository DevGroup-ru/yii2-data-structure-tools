<?php

namespace DevGroup\DataStructure\search\base;

use DevGroup\DataStructure\search\eventHandlers\QueryModifiers\DisablePropertiesAutoFetch;
use DevGroup\DataStructure\search\eventHandlers\QueryModifiers\FetchProperties;
use DevGroup\DataStructure\search\response\CountResponse;
use DevGroup\DataStructure\search\response\ResultResponse;
use DevGroup\DataStructure\search\response\QueryResponse;
use yii;

abstract class AbstractSearcher extends yii\base\Component
{
    const EVENT_BEFORE_PAGINATION = 'after-pagination';
    const EVENT_AFTER_FIND = 'after-find';


    /** @var AbstractWatcher|array */
    public $watcher;

    /**
     * @param \DevGroup\DataStructure\search\base\SearchQuery $searchQuery
     * @param int $returnType Return type(count, query, result, ids)
     * @return SearchResponse
     */
    abstract public function search(SearchQuery $searchQuery, $returnType = BaseSearch::SEARCH_RESULT);

    public function init()
    {
        $this->on(self::EVENT_BEFORE_PAGINATION, [DisablePropertiesAutoFetch::class, 'modify']);
        $this->on(self::EVENT_AFTER_FIND, [FetchProperties::class, 'modify']);
    }

    /**
     * @param int $returnType
     *
     * @return SearchResponse
     */
    protected static function newResponse($returnType = BaseSearch::SEARCH_RESULT)
    {
        switch ($returnType) {
            case BaseSearch::SEARCH_COUNT:
                return new CountResponse();
                break;
            case BaseSearch::SEARCH_RESULT:
                return new ResultResponse();
                break;
            case BaseSearch::SEARCH_QUERY:
            default:
                return new QueryResponse();
                break;
        }
    }
}
