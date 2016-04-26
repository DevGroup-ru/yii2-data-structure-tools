<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\helpers\PropertyStorageHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\tests\models\Category;
use DevGroup\DataStructure\tests\models\Product;
use Yii;
use yii\base\UnknownPropertyException;
use yii\console\Application;

/**
 * DatabaseTestCase
 */
class DatabaseTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        return $this->createDefaultDBConnection(\Yii::$app->getDb()->pdo);
    }

    /**
     * @inheritdoc
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/test.xml');
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        // all identity map should be cleared
        Property::$identityMap = [];

        $config = require(Yii::getAlias('@DevGroup/DataStructure/tests/config/console.php'));

        Yii::$app = new Application($config);
        try {
            Yii::$app->runAction('migrate/down', [99999, 'interactive'=>0, 'migrationPath' => __DIR__ . '/../src/migrations/']);
            Yii::$app->runAction('migrate/up', ['interactive'=>0, 'migrationPath' => __DIR__ . '/../src/migrations/']);

            $this->importDump('models.sql');


            $generator = PropertiesTableGenerator::getInstance();

            $generator->generate(Product::className());
            $generator->generate(Category::className());

        } catch (\Exception $e) {
            Yii::$app->clear('db');
            throw $e;
        }



        if (Yii::$app->get('db', false) === null) {
            $this->markTestSkipped();
        } else {
            parent::setUp();
        }
        Yii::$app->cache->flush();
    }

    private function importDump($filename)
    {
        $lines = explode(';', file_get_contents(__DIR__ . "/migrations/$filename"));

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                Yii::$app->getDb()->pdo->exec($line);
            }
        }
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();

        $generator = PropertiesTableGenerator::getInstance();

        $generator->drop(Product::className());
        $generator->drop(Category::className());


        Yii::$app->cache->flush();
        $this->destroyApplication();
    }
    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        if (\Yii::$app && \Yii::$app->has('session', true)) {
            \Yii::$app->session->close();
        }
        \Yii::$app = null;
    }

    public function testTableInheritance()
    {
        $product = new Product();
        $product->name = 'Powerbank';
        $this->assertTrue($product->save());

        $propertyGroup = new PropertyGroup(Product::className());
        $propertyGroup->internal_name = 'Specification';
        $propertyGroup->translate(1)->name = 'Specification';
        $propertyGroup->translate(2)->name = 'Specification';
        $this->assertTrue($propertyGroup->save());

        $power = new Property();
        $power->key = 'power';
        $power->translate(1)->name = 'Power';
        $power->translate(2)->name = 'Power';
        $power->data_type = Property::DATA_TYPE_INTEGER;
        $power->storage_id = 3;
        $power->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );
        $this->assertTrue($power->validate());
        $saved = $power->save();

        $this->assertTrue($saved, var_export($power->errors, true));

        $propertyGroup->link(
            'properties',
            $power
        );

        $this->assertTrue($product->addPropertyGroup($propertyGroup));
        $product->power = 120;
        $this->assertSame(120, $product->power);
        $models = [ &$product ];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $this->assertTrue($product->save());

        /** @var Product $productFromDb */
        $productFromDb = Product::findOne($product->id);
        $models = [ &$productFromDb ];
        PropertiesHelper::fillProperties($models);
        $this->assertSame(120, $productFromDb->power);

    }

    public function testAutoAdd()
    {
        $propertyGroup = new PropertyGroup(Product::className());
        $propertyGroup->internal_name = 'Specification';
        $propertyGroup->is_auto_added = true;
        $propertyGroup->name = 'Specs';
        $this->assertTrue($propertyGroup->save());

        /** @var Product $product */
        $product = Product::findOne(1);
        $models = [$product];
        PropertiesHelper::fillPropertyGroups($models);
        $this->assertSame([$propertyGroup->id], $product->propertyGroupIds);

        $newProduct = new Product();
        $newProduct->name = 'Powerbank';
        $this->assertTrue($newProduct->save());
        $this->assertSame([$propertyGroup->id], $product->propertyGroupIds);

        $product = Product::findOne($newProduct->id);
        $models = [$product];
        PropertiesHelper::fillPropertyGroups($models);
        $this->assertSame([$propertyGroup->id], $product->propertyGroupIds);


        $propertyGroup->delete();

        $product = Product::findOne(1);
        $models = [$product];
        PropertiesHelper::fillPropertyGroups($models);
        $this->assertSame([], $product->propertyGroupIds);

        Yii::$app->cache->flush();
    }

    public function testHelpers()
    {
        $good = false;
        try {
            PropertyHandlerHelper::getInstance()->handlerById(PHP_INT_MAX);
        } catch (\Exception $e) {
            $good = true;
        }
        $this->assertTrue($good);

        $handler = PropertyHandlerHelper::getInstance()->handlerById(1);
        $this->assertEquals(\DevGroup\DataStructure\propertyHandler\StaticValues::className(), $handler->className());

        $good = false;
        try {
            PropertyHandlerHelper::getInstance()->handlerIdByClassName(get_class($this));
        } catch (\Exception $e) {
            $good = true;
        }
        $this->assertTrue($good);

        $id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(\DevGroup\DataStructure\propertyHandler\StaticValues::className());
        $this->assertEquals(1, $id);

        $result = false;
        try {
            PropertiesHelper::applicablePropertyModelId('foo\bar\some');
        } catch (\yii\base\Exception $e) {
            $result = true;
        }
        $this->assertTrue($result, 'No exception if there is not correct applicable property models record for classname');

        $result = false;
        try {
            PropertyStorageHelper::storageById(65535);
        } catch (\yii\base\Exception $e) {
            $result = true;
        }
        $this->assertTrue($result, 'No exception if there is not correct storage id specified');
    }

    public function testActiveRecord()
    {

        $this->assertSame(2, PropertiesHelper::applicablePropertyModelId(Category::className()));
        $this->assertSame(1, PropertiesHelper::applicablePropertyModelId(Product::className(), true));
        // property group for all products
        $package_properties = new PropertyGroup(Product::className());
        $package_properties->internal_name = 'Package properties';
        $package_properties->translate(1)->name = 'Package';
        $package_properties->translate(2)->name = 'Упаковка';
        $this->assertTrue($package_properties->save());

        // property group for smartphones only!
        $smartphone_general = new PropertyGroup(Product::className());
        $smartphone_general->internal_name = 'Smartphone - general';
        $smartphone_general->translate(1)->name = 'General';
        $smartphone_general->translate(2)->name = 'Основные';
        $this->assertTrue($smartphone_general->save());

        $weight = new Property();
        $weight->key = 'weight';
        $weight->storage_id = 2;
        $weight->data_type = Property::DATA_TYPE_FLOAT;
        $weight->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );


        $weight->translate(1)->name = 'Weight';
        $weight->translate(2)->name = 'Вес';

        $this->assertTrue($weight->save());


        $package_properties->link(
            'properties',
            $weight,
            ['sort_order_group_properties' => 1]
        );

        $properties = $package_properties->properties;
        $this->assertEquals(1, count($properties));

        $os = new Property();
        $os->key = 'os';
        $os->storage_id = 1;
        $os->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\StaticValues::className()
        );
        $os->translate(1)->name = 'Operating system';
        $os->translate(2)->name = 'Операционная система';
        $this->assertTrue($os->save());
        $smartphone_general->link(
            'properties',
            $os
        );

        /** @var Product $product */
        $product = Product::findOne(1);

        // product should not has property groups filled as autoFetchProperties is off
        $this->assertNull($product->propertyGroupIds);

        $product->ensurePropertyGroupIds();
        // now we fetched and it should be an empty array
        $this->assertTrue(is_array($product->propertyGroupIds));
        $this->assertEquals(0, count($product->propertyGroupIds));

        // try to get non existing property
        $propertyThrowsError = false;
        try {
            $product->weight;
        } catch (UnknownPropertyException $e) {
            $propertyThrowsError = true;
        }
        $this->assertTrue($propertyThrowsError);


        // try to SET non existing property
        $propertyThrowsError = false;
        try {
            $product->tralala = true;
        } catch (UnknownPropertyException $e) {
            $propertyThrowsError = true;
        }
        $this->assertTrue($propertyThrowsError);

        // now add property group
        $this->assertTrue($product->addPropertyGroup($package_properties));

        $this->assertEquals(1, count($product->propertyGroupIds));
        $this->assertSame([], $product->changedProperties);
        $this->assertFalse($product->propertiesValuesChanged);

        $this->assertNull($product->weight);

        $product->weight = 127.001;

        $this->assertSame(127.001, $product->weight);
        $this->assertSame([$weight->id], $product->changedProperties);
        $this->assertTrue($product->propertiesValuesChanged);

        // static values test
        $this->assertTrue($product->addPropertyGroup($smartphone_general));
        $this->assertFalse($product->addPropertyGroup($smartphone_general), 'Should not allow adding one group twice');
        $this->assertSame([], StaticValue::valuesForProperty($os));

        $windows = new StaticValue($os);
        $windows->name = 'Windows';
        $windows->slug = 'win';
        $this->assertTrue($windows->save());

        $this->assertSame([
            $windows->id => [
                'name' => 'Windows',
                'description' => '',
                'slug' => 'win',
            ]
        ], StaticValue::valuesForProperty($os));



        $linux = new StaticValue($os);
        $linux->name = 'Linux';
        
        $this->assertTrue($linux->save());

        $this->assertSame([
            $windows->id => [
                'name' => 'Windows',
                'description' => '',
                'slug' => 'win',
            ],
            $linux->id => [
                'name' => 'Linux',
                'description' => '',
                'slug' => '',
            ],
        ], StaticValue::valuesForProperty($os));

        $product->os = $windows->id;

        $validationResult = $product->validate();

        $this->assertTrue($validationResult);

        // save
        $models = [&$product];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $product->invalidateTags();
        
        // test fill
        /** @var Product $productFromDatabase */
        $productFromDatabase = Product::findOne(1);

        $models = [&$productFromDatabase];

        PropertiesHelper::fillProperties($models);
        // this should not call again and covers special line "if ($firstModel->propertyGroupIds !== null)"
        PropertiesHelper::fillProperties($models);

        $this->assertSame(127.001, $productFromDatabase->weight);
        $this->assertSame($windows->id, $productFromDatabase->os);

        $productFromDatabase->os = 65535;
        $validationResult = $productFromDatabase->validate();
        $this->assertFalse($validationResult, "We have added unexisting static value, but there was no validation error");

        PropertiesHelper::deleteAllProperties($models);
        $this->assertSame('0', Yii::$app->db->createCommand("SELECT COUNT(*) FROM {{product_eav}}")->queryScalar());
//        $this->markTestSkipped('TBD');
    }

    public function testMultiple()
    {
        // Static values
        $propertyGroup = new PropertyGroup(Product::className());
        $propertyGroup->is_auto_added = true;
        $propertyGroup->internal_name = 'Multi';
        $propertyGroup->translate(1)->name = 'Multi';
        $propertyGroup->translate(2)->name = 'Multi';
        $propertyGroup->save();
        $interface = new Property();
        $interface->key = 'interface';
        $interface->translate(1)->name = 'Interface';
        $interface->translate(2)->name = 'Interface';
        $interface->data_type = Property::DATA_TYPE_INTEGER;
        $interface->storage_id = PropertyStorage::find()
            ->select('id')
            ->where(['class_name' => StaticValues::className()])
            ->scalar();
        $interface->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\StaticValues::className()
        );
        $interface->allow_multiple_values = true;
        $interface->save();
        $propertyGroup->link(
            'properties',
            $interface
        );
        $usb2 = new StaticValue($interface);
        $usb2->name = 'USB 2.0';
        $usb2->save();
        $usb3 = new StaticValue($interface);
        $usb3->name = 'USB 3.0';
        $usb3->save();
        $hdmi = new StaticValue($interface);
        $hdmi->name = 'HDMI';
        $hdmi->save();
        $newProduct = new Product;
        $newProduct->name = 'New product';
        $newProduct->save();
        // tests 1
        $interfaces = [$usb2->id, $hdmi->id];
        $newProduct->interface = $interfaces;
        $newProduct->autoSaveProperties = true;
        $this->assertTrue($newProduct->save());
        $this->assertSame($interfaces, $newProduct->interface);
        $newProductId = $newProduct->id;
        $newProduct = Product::findOne($newProductId);
        $products = [$newProduct];
        PropertiesHelper::fillProperties($products);
        $this->assertSame($interfaces, $newProduct->interface);
        // tests 2
        $interfaces = [$usb3->id];
        $newProduct->interface = $interfaces;
        $newProduct->autoSaveProperties = true;
        $this->assertTrue($newProduct->save());
        $this->assertSame($interfaces, $newProduct->interface);
        $newProductId = $newProduct->id;
        $newProduct = Product::findOne($newProductId);
        $products = [$newProduct];
        PropertiesHelper::fillProperties($products);
        $this->assertSame($interfaces, $newProduct->interface);
        $propertyGroup->delete();
        // EAV
        $propertyGroup = new PropertyGroup(Product::className());
        $propertyGroup->is_auto_added = true;
        $propertyGroup->internal_name = 'Multi eav';
        $propertyGroup->translate(1)->name = 'Multi eav';
        $propertyGroup->translate(2)->name = 'Multi eav';
        $this->assertTrue($propertyGroup->save());
        $advise = new Property();
        $advise->key = 'advise';
        $advise->translate(1)->name = 'Advise';
        $advise->translate(2)->name = 'Advise';
        $advise->data_type = Property::DATA_TYPE_TEXT;
        $advise->storage_id = PropertyStorage::find()
            ->select('id')
            ->where(['class_name' => EAV::className()])
            ->scalar();
        $advise->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextArea::className()
        );
        $advise->allow_multiple_values = true;
        $advise->save();
        $propertyGroup->link(
            'properties',
            $advise
        );
        $newProduct = new Product;
        $newProduct->name = 'Newest product';
        $newProduct->save();
        // tests 1
        $advises = ['The only one'];
        $newProduct->advise = $advises;
        $newProduct->autoSaveProperties = true;
        $this->assertTrue($newProduct->save());
        $this->assertSame($advises, $newProduct->advise);
        $newProductId = $newProduct->id;
        $newProduct = Product::findOne($newProductId);
        $products = [$newProduct];
        PropertiesHelper::fillProperties($products);
        $this->assertSame($advises, $newProduct->advise);
        // tests 2
        $advises = ['one', 'tWo', '4'];
        $newProduct->advise = $advises;
        $newProduct->autoSaveProperties = true;
        $this->assertTrue($newProduct->save());
        $this->assertSame($advises, $newProduct->advise);
        $newProductId = $newProduct->id;
        $newProduct = Product::findOne($newProductId);
        $products = [$newProduct];
        PropertiesHelper::fillProperties($products);
        $this->assertSame($advises, $newProduct->advise);
    }

    public function testPackedJsonFields()
    {
        /** @var Product $product */
        $product = Product::findOne(1);
        $this->assertEquals([], $product->data);
        $testArray = ['test'=>1, 2=>'foo', 'bar' => false];
        $product->data = $testArray;
        $this->assertEquals($testArray, $product->data);
        $this->assertTrue($product->save());
        $this->assertEquals($testArray, $product->data);

        $product = Product::findOne(1);
        $this->assertEquals($testArray, $product->data);

        $product = Product::findOne(2);
        $this->assertNull($product->data);


    }

}
