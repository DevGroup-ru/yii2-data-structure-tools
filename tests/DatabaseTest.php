<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\DataStructure\tests;

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
        (new \yii\web\Application([
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
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ .'/index.php',
                    'scriptUrl' => '/index.php',
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
            $this->importDump('properties.sql');
            $this->importDump('models.sql');

        } catch (\Exception $e) {
            Yii::$app->clear('db');
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
        $this->fail('TBD');


    }
}
