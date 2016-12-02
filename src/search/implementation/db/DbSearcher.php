<?php

namespace DevGroup\DataStructure\search\implementation\db;

use DevGroup\DataStructure\search\base\AbstractSearcher;
use DevGroup\DataStructure\search\base\BaseSearch;
use DevGroup\DataStructure\search\base\SearchableEntity;
use DevGroup\DataStructure\search\base\SearchEvent;
use DevGroup\DataStructure\search\base\SearchQuery;
use DevGroup\DataStructure\search\base\SearchResponse;
use DevGroup\DataStructure\search\implementation\db\QueryModifiers\DefaultAttributesScope;
use DevGroup\DataStructure\search\implementation\db\QueryModifiers\DefaultWith;
use DevGroup\DataStructure\search\response\CountResponse;
use DevGroup\DataStructure\search\response\ResultResponse;
use DevGroup\DataStructure\search\response\QueryResponse;
use yii;

class DbSearcher extends AbstractSearcher
{
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_AFTER_PAGINATION, [DefaultAttributesScope::class, 'modify']);
        $this->on(self::EVENT_AFTER_PAGINATION, [DefaultWith::class, 'modify']);
    }

    /**
     * @param \DevGroup\DataStructure\search\base\SearchQuery $searchQuery
     * @param int                                             $returnType Return type(count, query, result, ids)
     *
     * @return SearchResponse
     */
    public function search(SearchQuery $searchQuery, $returnType = BaseSearch::SEARCH_RESULT)
    {
        $response = static::newResponse($returnType);

        /** @var yii\db\ActiveRecord|SearchableEntity $mainEntityClassName */
        $mainEntityClassName = $searchQuery->mainEntityClassName;

        $activeQuery = $mainEntityClassName::find();

        $success = false;

        // Pagination
        $pages = $searchQuery->getPagination();
        if ($returnType !== BaseSearch::SEARCH_COUNT && $pages !== null) {
            if ($pages->totalCount === 0) {
                /** @var CountResponse $result */
                $result = $searchQuery->search(BaseSearch::SEARCH_COUNT);
                $pages->totalCount = $result->count;
            }
            $activeQuery
                ->offset($pages->offset)
                ->limit($pages->limit);
        }

        // combine query
        $e = new SearchEvent(
            $searchQuery,
            $returnType,
            $response,
            [
                'params' => [
                    'activeQuery' => &$activeQuery,
                ],
            ]
        );
        $this->trigger(self::EVENT_AFTER_PAGINATION, $e);


        switch ($returnType) {
            case BaseSearch::SEARCH_COUNT:
                /** @var CountResponse $response */
                $response->count = (int) $activeQuery->count();
                $success = true;
                break;
            case BaseSearch::SEARCH_QUERY:
                /** @var QueryResponse $response */
                $response->query = $activeQuery;
                $success = true;
                break;
            case BaseSearch::SEARCH_RESULT:
                /** @var ResultResponse $response */
                // query for debug
                $response->query = $activeQuery;
                $response->entities = $activeQuery->all();
                $e = new SearchEvent(
                    $searchQuery,
                    $returnType,
                    $response,
                    [
                        'params' => [
                            'activeQuery' => &$activeQuery,
                        ],
                    ]
                );
                $this->trigger(self::EVENT_AFTER_FIND, $e);
                $success = true;
                break;
        }

        return $response->end($success);
    }
}
