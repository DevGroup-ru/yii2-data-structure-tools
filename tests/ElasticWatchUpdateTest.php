<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\tests\models\Product;
use Elasticsearch\ClientBuilder;

/**
 * This tests are separated for different classes because Elasticsearch\Client has Closures inside and we have
 * `Exception: Serialization of 'Closure' is not allowed` while testing
 */
class WatchUpdateTest extends DSTCommonTestCase
{
    public function testOnUpdateAndSave()
    {
        sleep(4);
        /** @var Product $prod */
        $prod = new Product();
        $prod->loadDefaultValues();
        $prod->autoSaveProperties = true;
        $prod->name = 'sonOfAWitch';
        if (false === $prod->save()) {
            $this->markTestSkipped();
        }
        $pg = PropertyGroup::findOne(1);
        if(null === $pg) {
            $this->markTestSkipped();
        }
        $prod->addPropertyGroup($pg);
        $prod->material = 2;
        if (false === $prod->save()) {
            $this->markTestSkipped();
        }
        sleep(4);
        $client = ClientBuilder::create()->build();
        $params = [
            'index' => 'product',
            'type' => 'static_values',
            'id' => $prod->id
        ];
        $res = $client->get($params);
        $this->assertArrayHasKey('found', $res);
        $this->assertEquals(1, $res['found']);
    }
}