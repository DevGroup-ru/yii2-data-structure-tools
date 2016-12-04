<?php

namespace DevGroup\DataStructure\search\base;

use yii;

abstract class SearchResponse extends yii\base\Component
{
    /**
     * @var float Time start
     */
    public $timeStart = 0;
    /**
     * @var float Time end
     */
    public $timeEnd = 0;
    /**
     * @var float Time spent
     */
    public $totalTime = 0;

    /**
     * @var bool
     */
    public $success = false;

    /** @var SearchQuery */
    public $searchQuery;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->timeStart = microtime(true);
    }

    public function end($success = false)
    {
        $this->success = $success;
        $this->timeEnd = microtime(true);
        $this->totalTime = $this->timeEnd - $this->timeStart;
        return $this;
    }
}
