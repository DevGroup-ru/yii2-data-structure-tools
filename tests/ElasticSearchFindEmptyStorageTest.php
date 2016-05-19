<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\search\elastic\Search;
use DevGroup\DataStructure\tests\models\Product;
use Yii;

/**
 * This tests are separated for different classes because Elasticsearch\Client has Closures inside and we have
 * `Exception: Serialization of 'Closure' is not allowed` while testing
 */

class SearchFindEmptyStorageTest extends DSTCommonTestCase
{
    public function testFilterFormDataEmptyStorage()
    {
        sleep(2);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $data = $search->filterFormData(['modelClass' => Product::class]);
        $this->assertArrayHasKey('props', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('selected', $data);

        $this->assertArraySubset([1 => 'material', 11 => 'Count'], $data['props']);
        $this->assertEmpty($data['selected']);
        $this->assertArrayHasKey(1, $data['data']);
        $this->assertArrayHasKey(11, $data['data']);
        $this->assertCount(2, $data['data'][1]);
        $this->assertCount(3, $data['data'][11]);
    }
}