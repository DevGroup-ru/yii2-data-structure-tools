<?php

namespace DevGroup\DataStructure\search\components;

use DevGroup\DataStructure\search\base\BaseSearch;
use DevGroup\DataStructure\search\implementation\db\DbSearcher;

class Search extends BaseSearch
{
    /** @inheritdoc */
    public $searchers = [
        DbSearcher::class,
    ];
}
