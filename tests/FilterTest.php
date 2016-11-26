<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\search\helpers\PropertiesFilterHelper;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage;
use DevGroup\DataStructure\tests\models\Category;
use DevGroup\DataStructure\tests\models\Product;
use PHPUnit_Extensions_Database_DataSet_IDataSet;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;
use Yii;
use yii\base\Exception;
use yii\console\Application;
use yii\db\Query;
use yii\di\Container;

class FilterTest extends \PHPUnit_Extensions_Database_TestCase
{

    /**
     * Returns the test database connection.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        return $this->createDefaultDBConnection(Yii::$app->getDb()->pdo);
    }

    protected function setUp()
    {
        Property::$identityMap = [];
        $config = require(Yii::getAlias('@DevGroup/DataStructure/tests/config/console.php'));

        Yii::$app = new Application($config);

        try {
            Yii::$app->runAction(
                'migrate/down',
                [99999, 'interactive' => 0, 'migrationPath' => __DIR__ . '/../src/migrations/']
            );
            Yii::$app->runAction(
                'migrate/up',
                ['interactive' => 0, 'migrationPath' => __DIR__ . '/../src/migrations/']
            );

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
        Yii::$app->cache->flush();
        $lines = explode(';', file_get_contents(__DIR__ . "/migrations/$filename"));

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                Yii::$app->getDb()->pdo->exec($line);
            }
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        $generator = PropertiesTableGenerator::getInstance();

        $generator->drop(Product::className());
        $generator->drop(Category::className());
        // all identity map should be cleared


        Yii::$app->cache->flush();
        $this->destroyApplication();
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        if (Yii::$app && Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }
        Yii::$app = null;
        Yii::$container = new Container();
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/filters.xml');
    }

    public function testFiltering()
    {
//        sleep(10);
        //single selections
        //  static values
        $this->assertFilter(['1' => ['metal'],], 1, [1]);
        $this->assertFilter(['1' => ['plastic'],], 3, [2, 4, 5]);
        $this->assertFilter(['1' => ['glass']]);
        $this->assertFilter(['1' => ['stone']]);


        //  eav
        $this->assertFilter(['3' => ['138*67*7'],], 1, [1]);
        $this->assertFilter(['4' => ['2'],], 1, [1]);
        //$this->assertFilter(['5' => ['5.1'],], 1, [1]); mysql float strange
        $this->assertFilter(['6' => ['A8 (64bit)+M8'],], 1, [1]);

        //multiple selections

        $this->assertFilter(['1' => ['metal'], '2' => ['white']], 1, [1]);
        $this->assertFilter(['1' => ['metal'], '2' => ['black']], 1, [1]);
        $this->assertFilter(['1' => ['metal'], '2' => ['black', 'white']], 1, [1]);
        $this->assertFilter(['1' => ['metal', 'plastic'], '2' => ['white']], 2, [1, 2]);
        $this->assertFilter(['1' => ['metal', 'plastic'], '2' => ['white'], '6' => ['A8 (64bit)+M8']], 1, [1]);

        //range, custom

        $this->assertFilter(
            [
                '4' => PropertiesHelper::getPropertyValuesByParams(
                    Property::findById(4),
                    ['between', '[column]', 2, 4]
                ),
                '2' => ['white'],
            ],
            1,
            [1]
        );

        $this->assertFilter(
            [
                '1' => PropertiesHelper::getPropertyValuesByParams(
                    Property::findById(1),
                    ['not', ['[column]' => 'metal']]
                ),
                '2' => ['white'],
            ],
            1,
            [2]
        );

        $this->assertFilter(
            [
                '1' => PropertiesHelper::getPropertyValuesByParams(
                    Property::findById(1),
                    ['not', ['[column]' => 'metal']]
                ),
                '2' => ['white'],
            ],
            1,
            [2]
        );

        $this->assertFilter(
            [
                '4' => PropertiesHelper::getPropertyValuesByParams(
                    Property::findById(4),
                    '[column] > 1'
                ),
            ],
            1,
            [1]
        );
        //empty request
        $this->assertEquals([], PropertiesFilterHelper::filterObjects([]));

        $exception = false;
        try {
            PropertiesHelper::getPropertyValuesByParams(
                Property::findById(4),
                new Query()
            );
        } catch (Exception $e) {
            $exception = true;
        }
        $this->assertTrue($exception);

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
        $models = [&$product];
        $this->assertTrue(PropertiesHelper::storeValues($models));
        $this->assertTrue($product->save());

        /** @var Product $productFromDb */
        $productFromDb = Product::findOne($product->id);
        $models = [&$productFromDb];
        PropertiesHelper::fillProperties($models);
        $this->assertSame(120, $productFromDb->power);

        $this->assertFilter([$power->id => 120], 1, [$product->id]);
        $this->assertFilter(
            [
                $power->id => PropertiesHelper::getPropertyValuesByParams(
                    Property::findById($power->id),
                    ['between', '[column]', 100, 150]
                ),
            ],
            1,
            [$product->id]
        );
    }

    private function assertFilter($selections, $expectedCount = 0, $expectedIds = [])
    {
        $result = PropertiesFilterHelper::filterObjects($selections);
        $this->assertSame([Product::className()], array_keys($result));

        $resultedIds = $this->sortAndCast($result[Product::className()]);

        $expectedIds = $this->sortAndCast($expectedIds);

        $this->assertSame($expectedCount, count($resultedIds));

        $this->assertSame(count($expectedIds), count($resultedIds));
        $this->assertSame($expectedIds, $resultedIds);

    }

    private function sortAndCast($result)
    {
        $result = array_reduce($result, function($carry, $item) {
            if (is_object($item)) {
                $carry[] = (int) $item->id;
            } else {
                $carry[] = (int) $item;
            }
            return $carry;
        }, []);
        sort($result);
        return $result;
    }
}