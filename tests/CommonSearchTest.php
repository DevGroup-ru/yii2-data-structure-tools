<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\tests\models\Product;
use DevGroup\DataStructure\tests\models\Category;
use Yii;

class CommonSearchTest extends DSTCommonTestCase
{

    public $searchClass = \DevGroup\DataStructure\search\common\Search::class;

    public $prepareIndex = false;

    public function testFilterFormData()
    {
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

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFilterFormDataEmptyModel($search)
    {
        $res = $search->filterFormData([]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFilterFormDataBadModel($search)
    {
        $res = $search->filterFormData(['modelClass' => StaticValue::class]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
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
     * @param Search $search
     */
    public function testFilterFormDataBadStorage($search)
    {
        $data = $search->filterFormData([
            'modelClass' => Product::class,
            'storage' => [
                Category::class,
            ]]);
        $this->assertArrayHasKey('props', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('selected', $data);

        $this->assertEmpty($data['props']);
        $this->assertEmpty($data['data']);
        $this->assertEmpty($data['selected']);
    }

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFilterWithoutParams($search)
    {
        Yii::$app->request->setQueryParams([]);
        $res = $search->findInProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]]);
        $this->assertCount(5, $res);
    }

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFilterWithParamsNoResults($search)
    {
        Yii::$app->request->setQueryParams(['filter' => [1 => [3], 11 => [11, 13]]]);
        $res = $search->findInProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFilterWithParamsWithResults($search)
    {
        Yii::$app->request->setQueryParams(['filter' => [1 => [2], 11 => [11, 13]]]);
        $res = $search->findInProperties(Product::class, ['storage' => [StaticValues::class, EAV::class]]);
        $this->assertArraySubset([4, 5], $res);
    }

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFilterWithNoModel($search)
    {
        Yii::$app->request->setQueryParams([]);
        $res = $search->findInProperties([]);
        $this->assertEmpty($res);
    }

    /**
     * @depends testFilterFormData
     * @param Search $search
     */
    public function testFilterIncorrectModel($search)
    {
        Yii::$app->request->setQueryParams([]);
        $res = $search->findInProperties(PropertyStorage::class);
        $this->assertEmpty($res);
    }
}