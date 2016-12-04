<?php

namespace DevGroup\DataStructure\search\response;

use DevGroup\DataStructure\search\base\SearchableEntity;
use yii;

class ResultResponse extends QueryResponse
{
    /**
     * @var yii\db\ActiveRecord[]|SearchableEntity[] Entities
     */
    public $entities = [];
    /** @var  yii\data\Pagination */
    public $pages;
}
