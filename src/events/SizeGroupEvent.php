<?php

namespace DevGroup\DataStructure\events;

use DevGroup\DataStructure\models\Property;
use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Class SizeGroupEvent
 * @package DevGroup\DataStructure\events
 */
class SizeGroupEvent extends Event
{
    /**
     * @var Property|null
     */
    public $property;

    /**
     * @var ActiveRecord|null
     */
    public $model;

    /**
     * @var int
     */
    public $measureId;

    /**
     * @var int
     */
    public $measureFrontendId;

    /**
     * @var string
     */
    public $values = '';

    /**
     * @var string
     */
    public $view = '';
}
