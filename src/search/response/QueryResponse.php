<?php

namespace DevGroup\DataStructure\search\response;

use DevGroup\DataStructure\search\base\SearchResponse;
use yii;

class QueryResponse extends SearchResponse
{
    /** @var yii\db\ActiveQuery */
    public $query;
}
