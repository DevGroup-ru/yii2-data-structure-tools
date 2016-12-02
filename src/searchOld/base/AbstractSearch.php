<?php

namespace DevGroup\DataStructure\search\base;

use DevGroup\DataStructure\search\interfaces\Search;
use yii\base\Component;

/**
 * Class AbstractSearch
 *
 * @package DevGroup\DataStructure\search\base
 */
abstract class AbstractSearch extends Component implements Search
{
    /** @var string According Watcher class */
    public $watcherClass;

    /** @var AbstractWatch | null */
    protected $watcher = null;

    /** @var boolean */
    protected $started = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (true === $this->beforeInit()) {
            if (true === class_exists($this->watcherClass)) {
                $class = $this->watcherClass;
                $this->watcher = new $class;
                $this->watcher->init();
                $this->started = true;
            }
            parent::init();
        }
    }

    /**
     * Indicates module started successfully or not
     *
     * @return bool
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Performs some additional checks if necessary
     *
     * @return bool
     */
    public function beforeInit()
    {
        return true;
    }

    /**
     * Returns according watcher
     *
     * @return AbstractWatch|null
     */
    public function getWatcher()
    {
        return $this->watcher;
    }
}