<?php

namespace DevGroup\DataStructure\searchOld\base;


use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\events\HasPropertiesEvent;
use DevGroup\DataStructure\searchOld\interfaces\Watch;

/**
 * Class AbstractWatch
 *
 * @package DevGroup\DataStructure\searchOld\base
 */
abstract class AbstractWatch implements Watch
{
    /**
     * Adds event listeners to perform search index actualization if pre initialization was successful
     */
    public function init()
    {
        if (true === $this->beforeInit()) {
            HasPropertiesEvent::on(HasProperties::class, HasProperties::EVENT_AFTER_SAVE, [$this, 'onSave']);
            HasPropertiesEvent::on(HasProperties::class, HasProperties::EVENT_AFTER_UPDATE, [$this, 'onUpdate']);
            HasPropertiesEvent::on(HasProperties::class, HasProperties::EVENT_BEFORE_DELETE, [$this, 'onDelete']);
        }
    }

    /**
     * Proceeds different pre initialization stuff if necessary.
     *
     * @return bool
     */
    public function beforeInit()
    {
        return true;
    }
}