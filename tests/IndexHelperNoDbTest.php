<?php

namespace DevGroup\DataStructure\tests;


use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\searchOld\elastic\helpers\IndexHelper;
use DevGroup\DataStructure\tests\models\Product;

class IndexHelperNoDbTest  extends \PHPUnit_Framework_TestCase
{

    public function testClassToIndex()
    {
        $index = IndexHelper::classToIndex(Product::class);
        $this->assertEquals('product', $index);
        return $index;
    }

    public function testClassToIndexBadClass()
    {
        $index = IndexHelper::classToIndex([Product::class]);
        $this->assertEmpty($index);
    }

    public function testStorageClassToType()
    {
        $type = IndexHelper::storageClassToType(StaticValues::class);
        $this->assertEquals('static_values', $type);
    }

    public function testStorageClassToTypeBadClass()
    {
        $type = IndexHelper::storageClassToType([StaticValues::class]);
        $this->assertEmpty($type);
    }
}