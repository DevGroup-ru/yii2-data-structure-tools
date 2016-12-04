<?php

namespace DevGroup\DataStructure\search\base;

use yii;
use yii\data\Pagination;

class SearchQuery extends yii\base\Component
{
    /**
     * @var yii\db\ActiveRecord|SearchableEntity|string Class name of main entity we want to retrieve.
     */
    public $mainEntityClassName;

    /**
     * @var BaseSearch
     */
    public $baseSearch;

    /**
     * @var yii\data\Pagination
     */
    protected $pagination;

    /**
     * @var bool Whether to fill properties in all models if they are supported
     */
    public $fillProperties = true;

    /**
     * @var bool Whether to cache this query
     */
    public $cache = true;

    /**
     * @var int Cache lifetime in seconds
     */
    public $cacheLifetime = 86400;

    /**
     * @var array Additional tags for this cache
     */
    public $cacheAdditionalTags;

    /**
     * @var string
     */
    public $cacheKeyPostfix = ':DummySearchQuery';

    /**
     * @var array
     */
    public $mainEntityAttributes = [];

    /**
     * @var array
     */
    public $properties = [];

    /**
     * @var int
     */
    public $limit = 10;

    /**
     * @var int
     */
    public $offset = 0;
    /**
     * SearchQuery constructor.
     *
     * @param string $mainEntityClassName Class name of main entity we want to retrieve.
     * @param BaseSearch $baseSearch
     * @param array $config
     */
    public function __construct($mainEntityClassName, BaseSearch $baseSearch, array $config = [])
    {
        parent::__construct($config);
        $this->mainEntityClassName = $mainEntityClassName;
        $this->baseSearch = &$baseSearch;
    }

    /**
     * @param array|Pagination|string $config Array of component configuration, component instance or classname.
     *
     * @return $this
     */
    public function pagination($config)
    {
        if (is_array($config)) {
            $this->pagination = Yii::createObject($config);
        } elseif (is_object($config)) {
            $this->pagination = &$config;
        } elseif (is_string($config)) {
            $this->pagination = new Pagination([
                'pageParam' => $config,
            ]);
        } else {
            throw new \InvalidArgumentException('Pagination config should be array, object or string.');
        }
        return $this;
    }

    /**
     * @return \yii\data\Pagination
     */
    public function getPagination()
    {
        if ($this->pagination !== null && $this->limit) {
            $this->pagination->defaultPageSize = $this->limit;
        }
        return $this->pagination;
    }

    /**
     * @return \DevGroup\DataStructure\search\base\SearchResponse
     */
    public function count()
    {
        return $this->baseSearch->searchQuery($this, BaseSearch::SEARCH_COUNT);
    }

    /**
     * @return \DevGroup\DataStructure\search\base\SearchResponse
     */
    public function all()
    {
        return $this->baseSearch->searchQuery($this, BaseSearch::SEARCH_RESULT);
    }

    /**
     * @return \DevGroup\DataStructure\search\base\SearchResponse
     */
    public function ids()
    {
        return $this->baseSearch->searchQuery($this, BaseSearch::SEARCH_IDS);
    }

    /**
     * @return \DevGroup\DataStructure\search\base\SearchResponse
     */
    public function query()
    {
        return $this->baseSearch->searchQuery($this, BaseSearch::SEARCH_QUERY);
    }

    /**
     * @param int $returnType Return type(count, query, result, ids)
     * @return \DevGroup\DataStructure\search\base\SearchResponse
     */
    public function search($returnType = BaseSearch::SEARCH_RESULT)
    {
        return $this->baseSearch->searchQuery($this, $returnType);
    }

    /**
     * @return string Cache key for this search query. Compiled every call.
     * @throws \Exception
     */
    public function cacheKey()
    {
        throw new \Exception('Not implemented');
    }
}
