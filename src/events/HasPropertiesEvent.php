<?php

namespace DevGroup\DataStructure\events;

use yii\base\Event;

/**
 * Class HasPropertiesEvent
 *
 * @package DevGroup\DataStructure\events
 */
class HasPropertiesEvent extends Event
{
    /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $owner */
    public $model;
}