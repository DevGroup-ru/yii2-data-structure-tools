<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
use DevGroup\DataStructure\tests\models\Category;
use DevGroup\DataStructure\tests\models\Product;
use Yii;
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
            'bootstrap' => ['log'],
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

    public function testActiveRecord()
    {
        $this->markTestSkipped('TBD');
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
