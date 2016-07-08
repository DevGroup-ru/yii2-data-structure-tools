<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\tests\models\Category;
use DevGroup\DataStructure\tests\models\Product;
use Elasticsearch\ClientBuilder;
use yii\web\Application as Web;
use yii\console\Application as Console;
use Yii;

/**
 * Common initiator class for all Elasticsearch tests
 * note that it creates two instances of Yii::$app:
 *  - console - to apply migrations and perform console actions
 *  - then web - to perform main tests stuff
 *
 * Class DSTCommonElasticTestCase
 * @package DevGroup\DataStructure\tests
 */
class DSTCommonTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    public $searchClass = \DevGroup\DataStructure\search\elastic\Search::class;

    public $prepareIndex = true;

    public $runWeb = true;

    public static function setUpBeforeClass()
    {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_FILENAME'] = '';
    }

    /**
     * Returns the test database connection.
     *
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        return $this->createDefaultDBConnection(Yii::$app->getDb()->pdo);
    }

    /**
     * Returns the test dataset.
     *
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/filters.xml');
    }

    public function setUp()
    {
        Property::$identityMap = [];
        $config = include 'config/console.php';
        $config['bootstrap'] = ['properties'];
        $config['modules']['properties'] = [
            'class' => 'DevGroup\DataStructure\Properties\Module',
            'searchClass' => $this->searchClass,
        ];
        $app = new Console($config);
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
        if (true === $this->prepareIndex) {
            $client = ClientBuilder::create()->build();
            if (true === $client->indices()->exists(['index' => 'product'])) {
                $client->indices()->delete(['index' => 'product']);
            }
            if (true === $client->indices()->exists(['index' => 'category'])) {
                $client->indices()->delete(['index' => 'category']);
            }
            Yii::$app->runAction('properties/elastic/fill-index');
        }
        if (true === $this->runWeb) {
            Yii::$app = null;
            $config = include dirname(__DIR__) . '/testapp/config/web.php';
            $config['bootstrap'] = ['properties'];
            $config['modules']['properties'] = [
                'class' => 'DevGroup\DataStructure\Properties\Module',
                'searchClass' => $this->searchClass,
            ];
            $config['components']['db'] = include 'config/db.php';
            $app = new Web($config);
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

    public function tearDown()
    {
        if (true === $this->runWeb) {
            Yii::$app->cache->flush();
            Yii::$app = null;
            $config = include 'config/console.php';
            $app = new Console($config);
        }
        $generator = PropertiesTableGenerator::getInstance();
        $generator->drop(Product::className());
        $generator->drop(Category::className());
        Yii::$app->runAction(
            'migrate/down',
            [99999, 'interactive' => 0, 'migrationPath' => __DIR__ . '/../src/migrations/']
        );
        if (true === $this->prepareIndex) {
            $client = ClientBuilder::create()->build();
            if (true === $client->indices()->exists(['index' => 'product'])) {
                $client->indices()->delete(['index' => 'product']);
            }
            if (true === $client->indices()->exists(['index' => 'category'])) {
                $client->indices()->delete(['index' => 'category']);
            }
        }
        // all identity map should be cleared
        if (Yii::$app && Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }
        Yii::$app->cache->flush();
        Yii::$app = null;
    }
}