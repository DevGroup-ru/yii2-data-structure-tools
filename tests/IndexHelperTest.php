<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\searchOld\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\tests\models\Product;
use Elasticsearch\ClientBuilder;
use Yii;

/**
 * Class IndexHelperTest
 *
 * @package DevGroup\DataStructure\tests
 */
class IndexHelperTest extends DSTCommonTestCase
{
    public function testPrimaryKeysByCondition()
    {
        $index = IndexHelper::classToIndex(Product::class);
        $client = ClientBuilder::create()->build();
        //because of elasticsearch works 'near real time'
        sleep(2);
        $query = [
            'index' => $index,
            'body' => [
                'query' => [
                    'constant_score' => [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    [
                                        'bool' => [
                                            'should' => [
                                                [
                                                    'bool' => [
                                                        'must' => [
                                                            [
                                                                'term' => [
                                                                    'static_data.prop_id' => 1
                                                                ]
                                                            ],
                                                            [
                                                                'term' => [
                                                                    'static_data.value_rus.raw' => 'plastic'
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'should' => [
                                                [
                                                    'bool' => [
                                                        'must' => [
                                                            [
                                                                'term' => [
                                                                    'static_data.prop_id' => 11
                                                                ]
                                                            ],
                                                            [
                                                                'term' => [
                                                                    'static_data.value_rus.raw' => '19'
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $res = IndexHelper::primaryKeysByCondition($client, $query);
        $this->assertArraySubset([4 => ['static_values'], 5 => ['static_values']], $res);
    }
}