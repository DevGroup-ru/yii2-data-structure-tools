<?php

namespace DevGroup\DataStructure\search\response;

use DevGroup\DataStructure\search\base\SearchableEntity;
use yii;

class CountResponse extends QueryResponse
{
    /**
     * @var int
     */
    public $count = 0;

}
