<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\search\elastic\Search;
use Yii;

/**
 * This tests are separated for different classes because Elasticsearch\Client has Closures inside and we have
 * `Exception: Serialization of 'Closure' is not allowed` while testing
*/
class SearchFindWrongModelTest extends DSTCommonTestCase
{
    public function testFilterFormDataBadModel()
    {
        sleep(2);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $res = $search->filterFormData(['modelClass' => StaticValue::class]);
        $this->assertEmpty($res);
    }
}