<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\propertyHandler\StaticValues;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\searchOld\elastic\Search;
use DevGroup\DataStructure\tests\models\Product;
use Yii;

/**
 * This tests are separated for different classes because Elasticsearch\Client has Closures inside and we have
 * `Exception: Serialization of 'Closure' is not allowed` while testing
 *
 * Class ElasticSearchTest
 * @package DevGroup\DataStructure\tests
 */
class SearchTest extends DSTCommonTestCase
{
    /**
     * this must be the same to \DevGroup\DataStructure\searchOld\common\Search::testFilterFormData()
     * @return Search
     */
    public function testFilterFormData()
    {
        //because of elasticsearch works 'near real time'
        sleep(2);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $data = $search->filterFormData([
            'modelClass' => Product::class,
            'storage' => [
                EAV::class,
                StaticValues::class,
            ]]);
        $this->assertArrayHasKey('props', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('selected', $data);

        $this->assertArraySubset([1 => 'material', 11 => 'Count'], $data['props']);
        $this->assertEmpty($data['selected']);
        $this->assertArrayHasKey(1, $data['data']);
        $this->assertArrayHasKey(11, $data['data']);
        $this->assertCount(2, $data['data'][1]);
        $this->assertCount(3, $data['data'][11]);
        return $search;
    }
}