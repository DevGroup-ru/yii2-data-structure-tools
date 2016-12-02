<?php

namespace DevGroup\DataStructure\search\base;

use yii;

class CachableActiveQuery extends yii\db\ActiveQuery
{
    public $cacheKey = '';
    //! @todo Implement it on count, all queries
}
