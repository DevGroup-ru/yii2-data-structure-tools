<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues;
use DevGroup\DataStructure\tests\models\Category;
use DevGroup\DataStructure\tests\models\Product;
use Yii;
use yii\base\UnknownPropertyException;
use yii\db\Connection;

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
        (new \yii\console\Application([
            'id' => 'unit',
            'basePath' => __DIR__,
            'bootstrap' => ['log', 'multilingual'],
            'components' => [
                'log' => [
                    'traceLevel' => 10,
                    'targets' => [
                        [
                            'class' => 'yii\log\FileTarget',
                            'levels' => ['info'],
                        ],
                    ],
                ],
                'cache' => [
                    'class' => '\yii\caching\FileCache',
                    'as lazy' => [
                        'class' => 'DevGroup\TagDependencyHelper\LazyCache',
                    ],
                ],
                'multilingual' => [
                    'class' => 'DevGroup\Multilingual\Multilingual',
                    'default_language_id' => 1,
                ],
            ],
        ]));
        try {
            Yii::$app->set('db', [
                'class' => Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=yii2_datastructure',
                'username' => 'root',
                'password' => '',
            ]);

            Yii::$app->getDb()->open();

            Yii::$app->runAction('migrate/down', [99999, 'interactive'=>0, 'migrationPath' => __DIR__ . '/../src/migrations/']);
            Yii::$app->runAction('migrate/up', ['interactive'=>0, 'migrationPath' => __DIR__ . '/../src/migrations/']);

            $this->importDump('models.sql');


            $generator = PropertiesTableGenerator::getInstance();

            $generator->generate(Product::className());
            $generator->generate(Category::className());


            Yii::$app->getDb()
                ->createCommand()
                ->batchInsert('{{%property_group_models}}', ['class_name'], [
                    [Product::className(),],
                    [Category::className(),],
                ])->execute();


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
    }

    public function testActiveRecord()
    {
        // property group for all products
        $package_properties = new PropertyGroup(Product::className());
        $package_properties->internal_name = 'Package properties';
        $package_properties->translate(1)->name = 'Package';
        $package_properties->translate(2)->name = 'Упаковка';
        $package_properties->is_auto_added = true;
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
        $weight->property_handler_id = PropertyHandlerHelper::getInstance()->handlerIdByClassName(
            \DevGroup\DataStructure\propertyHandler\TextField::className()
        );

        $weight->translate(1)->name = 'Weight';
        $weight->translate(2)->name = 'Вес';
        var_dump($weight->getAttributes());
        $this->assertTrue($weight->save());
        var_dump($weight->getAttributes());

        $package_properties->link(
            'properties',
            $weight,
            ['sort_order_group_properties' => 1]
        );

        $properties = $package_properties->properties;
        $this->assertEquals(1, count($properties));
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
        $this->assertSame([1], $product->changedProperties);
        $this->assertTrue($product->propertiesValuesChanged);

        $models = [&$product];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $product->invalidateTags();

        // test fill
        /** @var Product $productFromDatabase */
        $productFromDatabase = Product::findOne(1);

        $models = [&$productFromDatabase];
        PropertiesHelper::fillProperties($models);
        $this->assertSame(127.001, $product->weight);


//        $this->markTestSkipped('TBD');
    }

//    public function testPackedJsonFields()
//    {
//        /** @var Product $product */
//        $product = Product::findOne(1);
//        $this->assertEquals([], $product->data);
//        $testArray = ['test'=>1, 2=>'foo', 'bar' => false];
//        $product->data = $testArray;
//        $this->assertEquals($testArray, $product->data);
//        $this->assertTrue($product->save());
//        $this->assertEquals($testArray, $product->data);
//
//        $product = Product::findOne(1);
//        $this->assertEquals($testArray, $product->data);
//
//        $product = Product::findOne(2);
//        $this->assertNull($product->data);
//
//
//    }
}
