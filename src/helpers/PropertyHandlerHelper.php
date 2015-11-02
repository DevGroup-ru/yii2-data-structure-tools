<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\PropertyHandlers;
use Yii;
use yii\base\Exception;

/**
 * Class PropertyHandlerHelper represents helper functions for Property Handlers
 * @package DevGroup\DataStructure\helpers
 */
class PropertyHandlerHelper
{
    /** @var PropertyHandlerHelper */
    public static $instance = null;

    /**
     * @var null|\DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler[] Array of all handlers with initiated classes(not ActiveRecord)
     */
    private $handlers = null;

    /**
     * @return PropertyHandlerHelper
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * PropertyHandlerHelper constructor.
     */
    public function __construct()
    {
        $this->handlers();
    }

    /**
     * Retrieves handlers from database.
     * Uses lazy cache.
     * @return \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler[]
     */
    public function handlers()
    {
        if ($this->handlers === null) {
            $this->handlers = Yii::$app->cache->lazy(function () {
                $models = PropertyHandlers::find()
                    ->orderBy(['sort_order' => SORT_ASC])
                    ->all();

                $handlers = [];
                foreach ($models as $model) {
                    $className = $model->class_name;
                    $handlers[$model->id] = new $className($model->default_config);
                }

                return $handlers;
            }, 'AllPropertyHandlers', 86400);
        }
        return $this->handlers;
    }

    /**
     * Returns handler by ID
     *
     * @param integer $id
     *
     * @return \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler
     * @throws \Exception
     */
    public function handlerById($id)
    {
        if (isset($this->handlers[$id])) {
            return $this->handlers[$id];
        }
        throw new \Exception("Property handler with id $id not found");
    }

    /**
     * Returns handler id bu it's class name
     *
     * @param string $className
     *
     * @return integer
     */
    public function handlerIdByClassName($className)
    {
        foreach ($this->handlers() as $id => $class) {
            if ($class->className() === $className) {
                return $id;
            }
        }

        throw new Exception("Handler with classname $className not found.");
    }
}