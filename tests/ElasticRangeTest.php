<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\propertyHandler\StaticValues;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\search\elastic\Search;
use DotPlant\Monster\Tests\models\Product;
use Yii;

class ElasticRangeTest extends DSTCommonTestCase
{
    public function testRangeSearch()
    {
        sleep(2);
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $configOk = ['storage' => [
            EAV::class,
            StaticValues::class,
        ]];
        $paramsOk = [
            5 => [
                'min' => 4,
                'max' => 6
            ],
            22 => [ //does not exist
                'min' => 100,
                'max' => 101
            ],
        ];
        //here is all ok
        $res = $search->filterByPropertiesRange(Product::class, $configOk, $paramsOk);
        $this->assertEquals([1, 2], $res);

        //will search only against static values
        //property exists and values are in range but property is not in search
        $config2 = ['storage' => [
            StaticValues::class,
        ]];
        $params2 = [
            9 => [
                'min' => 89,
                'max' => 95
            ],
        ];
        $res2 = $search->filterByPropertiesRange(Product::class, $config2, $params2);
        $this->assertEmpty($res2);

        //just exact float values
        $params3 = [
            5 => [
                'min' => 4.8,
                'max' => 5.3
            ],
        ];
        //here is all ok
        $res3 = $search->filterByPropertiesRange(Product::class, $configOk, $params3);
        $this->assertEquals([2], $res3);

        //correct property but bad values
        $config4 = ['storage' => [
            EAV::class
        ]];
        $params4 = [
            5 => [
                'mox' => 22,
            ],
        ];
        $res4 = $search->filterByPropertiesRange(Product::class, $config4, $params4);
        $this->assertEmpty($res4);

        //custom key labels
        $config5 = ['storage' => [
            EAV::class
        ],
            'fromKey' => 'from',
            'toKey' => 'to'
        ];
        $params5 = [
            5 => [
                'from' => 4,
                'to' => 6,
            ],
        ];
        $res5 = $search->filterByPropertiesRange(Product::class, $config5, $params5);
        $this->assertEquals([1, 2], $res5);

        //custom key labels
        //but not passed in params
        $config6 = ['storage' => [
            EAV::class
        ],
            'fromKey' => 'from',
            'toKey' => 'to'
        ];
        $params6 = [
            5 => [
                'min' => 4,
                'max' => 6,
            ],
        ];
        $res6 = $search->filterByPropertiesRange(Product::class, $config6, $params6);
        $this->assertEmpty($res6);

        //all is ok
        //with only start param
        $config7 = ['storage' => [
            EAV::class
        ]];
        $params7 = [
            5 => [
                'min' => 4,
            ],
        ];
        $res7 = $search->filterByPropertiesRange(Product::class, $config7, $params7);
        $this->assertEquals([1, 2], $res7);

        //all is ok
        //with only end param
        $config8 = ['storage' => [
            EAV::class
        ]];
        $params8 = [
            5 => [
                'max' => 6,
            ],
        ];
        $res8 = $search->filterByPropertiesRange(Product::class, $config8, $params8);
        $this->assertEquals([1, 2], $res8);

        //all is ok
        //one param ok and one param bad
        $config9 = ['storage' => [
            EAV::class
        ]];
        $params9 = [
            5 => [
                'mon' => 4,
                'max' => 6,
            ],
        ];
        $res9 = $search->filterByPropertiesRange(Product::class, $config9, $params9);
        $this->assertEquals([1, 2], $res9);
    }
}