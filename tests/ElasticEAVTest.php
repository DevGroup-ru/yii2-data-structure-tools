<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\tests\models\Product;
use DevGroup\DataStructure\searchOld\elastic\Search;
use Yii;

class ElasticEAVTest extends DSTCommonTestCase
{
    public function testEAVFindInPropertiesCorrect()
    {
        sleep(2);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $config = ['storage' => [
            EAV::class,
            StaticValues::class,
        ]];
        $res = $search->findInProperties(Product::class, $config, [1, 2, 6, 7, 8], "wi-fi");
        $this->assertEquals([2], $res);
    }
}