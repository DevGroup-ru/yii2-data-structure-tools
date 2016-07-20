<?php

namespace DevGroup\DataStructure\tests;

use app\models\Product;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use Yii;

class ElasticStaticTest extends DSTCommonTestCase
{

    public function testFindInPropertiesCorrect()
    {
        sleep(2);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $config = [
            'storage' => [
                EAV::class,
                StaticValues::class,
            ]
        ];
        $res = $search->findInProperties(Product::class, $config, [1, 2], "metal");
        $this->assertEquals([1], $res);


    }

    public function testFindInPropertiesAlias()
    {
        sleep(2);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $config = [
            'storage' => [
                EAV::class,
                StaticValues::class,
            ]
        ];
        $res = $search->findInProperties(Product::class, $config, [1, 2], "iron");
        $this->assertEquals([1], $res);
    }

}