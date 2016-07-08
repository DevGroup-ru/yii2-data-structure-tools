<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\search\common\Search;
use DevGroup\DataStructure\tests\models\Product;
use DevGroup\DataStructure\tests\models\Category;
use Yii;

class CommonSearchTest extends DSTCommonTestCase
{

    public $searchClass = \DevGroup\DataStructure\search\common\Search::class;

    public $prepareIndex = false;

    /**
     * @group range
     * @return Search
     */
    public function testFilterFormData()
    {
        /** @var Search $search */
        $search = Yii::$app->getModule('properties')->getSearch();
        $data = $search->filterFormData(
            [
                'modelClass' => Product::class,
                'storage' => [
                    EAV::class,
                    StaticValues::class,
                ],
            ]
        );
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

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterFormDataEmptyModel($search)
    {
        $res = $search->filterFormData([]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterFormDataBadModel($search)
    {
        $res = $search->filterFormData(['modelClass' => StaticValue::class]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterFormDataEmptyStorage($search)
    {
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

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterFormDataBadStorage($search)
    {
        $data = $search->filterFormData(
            [
                'modelClass' => Product::class,
                'storage' => [
                    Category::class,
                ],
            ]
        );
        $this->assertArrayHasKey('props', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('selected', $data);

        $this->assertEmpty($data['props']);
        $this->assertEmpty($data['data']);
        $this->assertEmpty($data['selected']);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterWithoutParams($search)
    {
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]]);
        $this->assertCount(5, $res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterWithParamsNoResults($search)
    {
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [1 => [3], 11 => [11, 13]]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterWithParamsWithResults($search)
    {
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [1 => [2], 11 => [11, 13]]);
        sort($res);
        $this->assertArraySubset([4, 5], $res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterWithNoModel($search)
    {
        $res = $search->filterByProperties([]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterIncorrectModel($search)
    {
        $res = $search->filterByProperties(PropertyStorage::class);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterByEav($search)
    {
        // empty result
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['test']]);
        $this->assertEmpty($res);
        // all models
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], []);
        $this->assertSame(5, count($res));
        // single property
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['138*67*7']]);
        $this->assertArraySubset([1], $res);
        // multi properties
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['138*67*7'], 4 => ['2']]);
        $this->assertArraySubset([1], $res);
        // multi properties and multi values check
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [2 => [4, 6], 7 => ['3G', 'wi-fi']]);
        $this->assertArraySubset(['1' => '2'], $res);
    }

    /**
     * @depends testFilterFormData
     *
     * @param Search $search
     */
    public function testFilterByEavAndStatic($search)
    {
        // bad eav + bad static
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['1234'], 1 => [2]]);
        $this->assertEmpty($res);
        // bad eav + static
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['1234'], 1 => [1]]);
        $this->assertEmpty($res);
        // eav + bas static
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['138*67*7'], 1 => [2]]);
        $this->assertEmpty($res);
        // eav + static
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['138*67*7'], 1 => [1]]);
        $this->assertArraySubset([1], $res);
        // eav + 2 static
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['138*67*7'], 1 => [1], 2 => [4]]);
        $this->assertArraySubset([1], $res);
        // 2 eav + static
        $res = $search->filterByProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]], [3 => ['138*67*7'], 4 => [2], 2 => [4]]);
        $this->assertArraySubset([1], $res);
    }

    /**
     * @depends testFilterFormData
     * @group range
     * @param Search $search
     */
    public function testRangeEav($search)
    {
        // bad integer
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                4 => [
                    'min' => 3,
                    'max' => 4,
                ],
            ]
        );
        $this->assertEmpty($res);
        // bad float
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                5 => [
                    'min' => 2,
                    'max' => 3,
                ],
            ]
        );
        $this->assertEmpty($res);
        // 1 integer
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                4 => [
                    'min' => 0,
                    'max' => 1,
                ],
            ]
        );
        $this->assertArraySubset([2], $res);
        // 2 integer
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                4 => [
                    'min' => 0,
                    'max' => 5,
                ],
            ]
        );
        $this->assertArraySubset([1, 2], $res);
        // 1 float
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                5 => [
                    'min' => 4,
                    'max' => 5,
                ],
            ]
        );
        $this->assertArraySubset([1], $res);
        // 2 float
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                5 => [
                    'min' => 2,
                    'max' => 6,
                ],
            ]
        );
        $this->assertArraySubset([1, 2], $res);
    }

    /**
     * @todo getModelIdsByRange method in StaticValues
     * @depends testFilterFormData
     * @group range
     * @param Search $search
     */
    public function testRangeStatic($search)
    {
        $this->markTestIncomplete();
        //bad
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [StaticValues::class, EAV::class]],
            [
                11 => [
                    'min' => 0,
                    'max' => 1,
                ],
            ]
        );
        $this->assertEmpty($res);
        // 1
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                4 => [
                    'min' => 0,
                    'max' => 15,
                ],
            ]
        );
        $this->assertArraySubset([4], $res);
        // 2
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                4 => [
                    'min' => 16,
                    'max' => 17,
                ],
            ]
        );
        $this->assertArraySubset([3, 5], $res);
        // 3
        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [EAV::class]],
            [
                4 => [
                    'min' => 15,
                    'max' => 19,
                ],
            ]
        );
        $this->assertArraySubset([3, 4, 5], $res);
    }

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFindContentInProperties($search)
    {
        $res = $search->findInProperties(
            Product::class,
            ['storage' => [StaticValues::class, EAV::class]],
            [8],
            'wi-fi'
        );
        $this->assertArraySubset([2], $res);
        $res = $search->findInProperties(
            Product::class,
            ['storage' => [StaticValues::class, EAV::class]],
            [6, 8],
            'too',
            false
        );
        $this->assertArraySubset([2], $res);
        $res = $search->findInProperties(
            Product::class,
            ['storage' => [StaticValues::class, EAV::class]],
            [7, 8],
            'too',
            true
        );
        $this->assertArraySubset([2], $res);
        $this->assertArraySubset([2], $res);
        $res = $search->findInProperties(
            Product::class,
            ['storage' => [StaticValues::class, EAV::class]],
            [7, 6],
            'too',
            true
        );
        $this->assertEmpty($res);
    }
}


