<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\helpers\PropertyStorageHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\propertyStorage\TableInheritance;
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



    public function testFilterByTableInheritance()
    {
        $search = Yii::$app->getModule('properties')->getSearch();
        $propertyGroup = new PropertyGroup(Product::className());
        $propertyGroup->internal_name = 'Specification';
        $propertyGroup->translate(1)->name = 'Specification';
        $propertyGroup->translate(2)->name = 'Specification';
        $this->assertTrue($propertyGroup->save());


        $power = new Property();
        $power->key = 'power';
        $power->translate(1)->name = 'Power';
        $power->translate(2)->name = 'Power';
        $power->in_search = 1;
        $power->data_type = Property::DATA_TYPE_INTEGER;
        $power->storage_id = PropertyStorageHelper::storageIdByClass(TableInheritance::class);
        $power->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );
        $this->assertTrue($power->validate());
        $saved = $power->save();

        $this->assertTrue($saved, var_export($power->errors, true));



        $height = new Property();
        $height->key = 'height';
        $height->translate(1)->name = 'height';
        $height->translate(2)->name = 'height';
        $height->in_search = 1;
        $height->data_type = Property::DATA_TYPE_INTEGER;
        $height->storage_id = PropertyStorageHelper::storageIdByClass(TableInheritance::class);
        $height->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );
        $this->assertTrue($height->validate());
        $saved = $height->save();

        $this->assertTrue($saved, var_export($height->errors, true));


        $productInDb = Product::findOne(['id'=>1]);


        $propertyGroup->link(
            'properties',
            $power
        );

        $propertyGroup->link(
            'properties',
            $height
        );
        $this->assertTrue($productInDb->addPropertyGroup($propertyGroup));
        $productInDb->power = 120;
        $productInDb->height = 2;
        $models = [ &$productInDb ];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $this->assertTrue($productInDb->save());

        $res = $search->filterByProperties(
            Product::class,
            [
                'storage' => [TableInheritance::class]
            ],
            [
                $power->id => ['120'],
                $height->id => ['2']
            ]
        );

        $this->assertCount(1, $res);


        /** Not allow multiple */
        $productInDb2 = Product::findOne(['id'=>2]);
        $this->assertTrue($productInDb2->addPropertyGroup($propertyGroup));
        $productInDb2->power = 130;
        $productInDb2->height = 4;
        $models = [ &$productInDb2 ];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $this->assertTrue($productInDb2->save());

        $res = $search->filterByProperties(
            Product::class,
            [
                'storage' => [TableInheritance::class]
            ],
            [
                $height->id => ['4'],
                $power->id => ['120', '130']
            ]
        );
        $this->assertCount(0, $res);

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
     * @group range
     */
    public function testRangeTableInheritance()
    {
        $search = Yii::$app->getModule('properties')->getSearch();
        $propertyGroup = new PropertyGroup(Product::className());
        $propertyGroup->internal_name = 'Specification';
        $propertyGroup->translate(1)->name = 'Specification';
        $propertyGroup->translate(2)->name = 'Specification';
        $this->assertTrue($propertyGroup->save());


        $power = new Property();
        $power->key = 'power';
        $power->translate(1)->name = 'Power';
        $power->translate(2)->name = 'Power';
        $power->in_search = 1;
        $power->data_type = Property::DATA_TYPE_INTEGER;
        $power->storage_id = PropertyStorageHelper::storageIdByClass(TableInheritance::class);
        $power->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );
        $this->assertTrue($power->validate());
        $saved = $power->save();

        $this->assertTrue($saved, var_export($power->errors, true));



        $height = new Property();
        $height->key = 'height';
        $height->translate(1)->name = 'height';
        $height->translate(2)->name = 'height';
        $height->in_search = 1;
        $height->data_type = Property::DATA_TYPE_INTEGER;
        $height->storage_id = PropertyStorageHelper::storageIdByClass(TableInheritance::class);
        $height->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );
        $this->assertTrue($height->validate());
        $saved = $height->save();

        $this->assertTrue($saved, var_export($height->errors, true));


        $productInDb = Product::findOne(['id'=>1]);


        $propertyGroup->link(
            'properties',
            $power
        );

        $propertyGroup->link(
            'properties',
            $height
        );
        $this->assertTrue($productInDb->addPropertyGroup($propertyGroup));
        $productInDb->power = 130;
        $productInDb->height = 4;
        $models = [ &$productInDb ];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $this->assertTrue($productInDb->save());


        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [TableInheritance::class]],
            [
                $power->id => [
                  'min' => 100,
                ],
                $height->id => [
                    'min' => 4,
                    'max' => 5,
                ],
            ]
        );

        $this->assertCount(1, $res);

        $res = $search->filterByPropertiesRange(
            Product::class,
            ['storage' => [TableInheritance::class]],
            [
                $power->id => [
                    'max' => 120,
                ],
                $height->id => [
                    'min' => 4,
                    'max' => 5,
                ],
            ]
        );

        $this->assertCount(0, $res);
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

    public function testFindContentInPropertiesTableInheritance()
    {
        $search = Yii::$app->getModule('properties')->getSearch();
        $propertyGroup = new PropertyGroup(Product::className());
        $propertyGroup->internal_name = 'Specification';
        $propertyGroup->translate(1)->name = 'Specification';
        $propertyGroup->translate(2)->name = 'Specification';
        $this->assertTrue($propertyGroup->save());


        $description = new Property();
        $description->key = 'description_te';
        $description->translate(1)->name = 'Description';
        $description->translate(2)->name = 'Description';
        $description->in_search = 1;
        $description->data_type = Property::DATA_TYPE_INVARIANT_STRING;
        $description->storage_id = PropertyStorageHelper::storageIdByClass(TableInheritance::class);
        $description->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );
        $this->assertTrue($description->save());


        $announce = new Property();
        $announce->key = 'announce_te';
        $announce->translate(1)->name = 'Announce';
        $announce->translate(2)->name = 'Announce';
        $announce->in_search = 1;
        $announce->data_type = Property::DATA_TYPE_INVARIANT_STRING;
        $announce->storage_id = PropertyStorageHelper::storageIdByClass(TableInheritance::class);
        $announce->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );
        $this->assertTrue($announce->save());



        $productInDb = Product::findOne(['id'=>1]);


        $propertyGroup->link(
            'properties',
            $description
        );
        $propertyGroup->link(
            'properties',
            $announce
        );
        $this->assertTrue($productInDb->addPropertyGroup($propertyGroup));

        $productInDb->description_te = 'one two three';
        $productInDb->announce_te = 'three five';
        $models = [ &$productInDb ];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $this->assertTrue($productInDb->save());


        $res = $search->findInProperties(
            Product::class,
            ['storage' => [TableInheritance::class]],
            [$description->id, $announce->id],
            'two',
            true
        );

        $this->assertCount(1, $res);

        $res = $search->findInProperties(
            Product::class,
            ['storage' => [TableInheritance::class]],
            [$description->id, $announce->id],
            'two',
            false
        );

        $this->assertEmpty($res);

        $productInDb->announce_te = 'two three five';
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $productInDb->save();

        $res = $search->findInProperties(
            Product::class,
            ['storage' => [TableInheritance::class]],
            [$description->id, $announce->id],
            'two',
            false
        );
        $this->assertCount(1, $res);
    }
}


