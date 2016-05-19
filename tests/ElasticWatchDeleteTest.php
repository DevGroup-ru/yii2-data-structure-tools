<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\tests\models\Product;
use Elasticsearch\ClientBuilder;
use Yii;

/**
 * This tests are separated for different classes because Elasticsearch\Client has Closures inside and we have
 * `Exception: Serialization of 'Closure' is not allowed` while testing
 */
class WatchDeleteTest extends DSTCommonTestCase
{
    /**
     * @expectedException \Elasticsearch\Common\Exceptions\Missing404Exception
     */
    public function testOnDelete()
    {
        sleep(2);
        /** @var Product $prod */
        $prod = Product::findOne(1);
        $prod->delete();

        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'product',
            'type' => 'static_values',
            'id' => '1'
        ];
        $res = $client->get($params);
    }

}