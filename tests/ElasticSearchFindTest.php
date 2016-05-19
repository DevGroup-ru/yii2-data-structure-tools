<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\propertyHandler\StaticValues;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\search\elastic\Search;
use DevGroup\DataStructure\tests\models\Product;
use Yii;

/**
 * This tests are separated for different classes because Elasticsearch\Client has Closures inside and we have
 * `Exception: Serialization of 'Closure' is not allowed` while testing
 *
 * Class ElasticSearchFindTest
 * @package DevGroup\DataStructure\tests
 */
class SearchFindTest extends DSTCommonTestCase
{
    public function testFilterWithParamsWithResults()
    {
        sleep(4);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        Yii::$app->request->setQueryParams(['filter' => [1 => ['plastic'], 11 => ['15', '19']]]);
        $res = $search->findInProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]]);
        $this->assertArraySubset([4, 5], $res);
    }
}