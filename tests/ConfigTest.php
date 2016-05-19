<?php

namespace DevGroup\DataStructure\tests;


use DevGroup\DataStructure\search\common\Search as Common;
use DevGroup\DataStructure\search\elastic\Search as Elastic;
use DevGroup\DataStructure\search\elastic\Watch;
use yii\web\Application;
use Yii;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_FILENAME'] = '';
    }

    public function createApp($searchConfig = '')
    {
        $config = include dirname(__DIR__) . '/testapp/config/web.php';
        $config['bootstrap'] = ['properties'];
        $config['modules']['properties'] = [
            'class' => 'DevGroup\DataStructure\Properties\Module',
        ];
        if (false === empty($searchConfig)) {
            $config['modules']['properties'] = array_merge($config['modules']['properties'], $searchConfig);
        }
        $app = new Application($config);
        Yii::$app->cache->flush();
    }

    public function tearDown()
    {
        if (Yii::$app && Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }
        Yii::$app = null;
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testEmptySearch()
    {
        $this->createApp();
        Yii::$app->getModule('properties')->getSearch();
    }

    public function testCommonSearch()
    {
        $this->createApp(['searchClass' => Common::class]);
        $search = Yii::$app->getModule('properties')->getSearch();
        $this->assertInstanceOf(Common::class, $search);
    }

    public function testBadElastic()
    {
        //try to run Elastic with no elastic configured (or badly configured like here)
        $this->createApp(['searchClass' => Elastic::class, 'searchConfig' => ['hosts' => ['127.0.0.5:9455']]]);
        $search = Yii::$app->getModule('properties')->getSearch();
        $this->assertInstanceOf(Common::class, $search);
    }

    public function testElastic()
    {
        $this->createApp(['searchClass' => Elastic::class]);
        $search = Yii::$app->getModule('properties')->getSearch();
        $this->assertInstanceOf(Elastic::class, $search);
    }

    public function testElasticWithoutWatcher()
    {
        $this->createApp(['searchClass' => Elastic::class, 'searchConfig' => ['watcherClass' => 'MyNotExistingWatcher']]);
        $search = Yii::$app->getModule('properties')->getSearch();
        $this->assertInstanceOf(Common::class, $search);
    }

    public function testCorrectWatcher()
    {
        $this->createApp(['searchClass' => Elastic::class]);
        $search = Yii::$app->getModule('properties')->getSearch();
        $this->assertInstanceOf(Watch::class, $search->getWatcher());
    }
}