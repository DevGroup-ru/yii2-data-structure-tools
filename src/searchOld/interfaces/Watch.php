<?php

namespace DevGroup\DataStructure\searchOld\interfaces;

use DevGroup\DataStructure\events\HasPropertiesEvent;

/**
 * Interface Watch
 *
 * @package DevGroup\DataStructure\searchOld\interfaces
 */
interface Watch
{
    /**
     * Updates indices after model created
     *
     * @param HasPropertiesEvent $event
     * @return mixed
     */
    public function onSave($event);

    /**
     * Updates indices after model updated
     *
     * @param HasPropertiesEvent $event
     * @return mixed
     */
    public function onUpdate($event);

    /**
     * Updates indices after model deleted
     *
     * @param HasPropertiesEvent $event
     * @return mixed
     */
    public function onDelete($event);
}