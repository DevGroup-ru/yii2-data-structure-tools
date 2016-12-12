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
use DevGroup\DataStructure\search\implementation\db\QueryModifiers\LimitOffset;
use DevGroup\DataStructure\search\implementation\db\QueryModifiers\MainEntityAttributes;
use DevGroup\DataStructure\search\implementation\db\QueryModifiers\OrderBy;
use DevGroup\DataStructure\search\implementation\db\QueryModifiers\PropertiesModifier;
use DevGroup\DataStructure\search\implementation\db\QueryModifiers\RelationAttributes;
use DevGroup\DataStructure\search\response\CountResponse;
use DevGroup\DataStructure\search\response\ResultResponse;
use DevGroup\DataStructure\search\response\QueryResponse;
use yii;

class DbSearcher extends AbstractSearcher
{
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_PAGINATION, [DefaultAttributesScope::class, 'modify']);
        $this->on(self::EVENT_BEFORE_PAGINATION, [DefaultWith::class, 'modify']);
        $this->on(self::EVENT_BEFORE_PAGINATION, [MainEntityAttributes::class, 'modify']);
        $this->on(self::EVENT_BEFORE_PAGINATION, [RelationAttributes::class, 'modify']);
        $this->on(self::EVENT_BEFORE_PAGINATION, [PropertiesModifier::class, 'modify']);
        $this->on(self::EVENT_BEFORE_PAGINATION, [OrderBy::class, 'modify']);

        $this->on(self::EVENT_AFTER_PAGINATION, [LimitOffset::class, 'modify']);

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
        $this->trigger(self::EVENT_BEFORE_PAGINATION, $e);

        // Pagination
        $pages = $searchQuery->getPagination();
        if ($returnType !== BaseSearch::SEARCH_COUNT && $pages !== null) {
            if ($pages->totalCount === 0) {
                $column = $mainEntityClassName::tableName() . '.id';
                $pages->totalCount = (int) $activeQuery->count($column);
            }
            $searchQuery->limit = $pages->limit;
            $searchQuery->offset = $pages->offset;
        }

        $e = new SearchEvent(
            $searchQuery,
            $returnType,
            $response,
            [
                'params' => [
                    'activeQuery' => &$activeQuery,
                    'pages' => $pages,
                ],
            ]
        );
        $this->trigger(self::EVENT_AFTER_PAGINATION, $e);

        $response->searchQuery = $searchQuery;

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
                $response->pages = $pages;
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
