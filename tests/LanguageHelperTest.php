<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\searchOld\helpers\LanguageHelper;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use Yii;

class LanguageHelperTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_FILENAME'] = '';
        $config = include dirname(__DIR__) . '/testapp/config/web.php';
        $config['bootstrap'] = ['properties'];
        $config['modules']['properties'] = [
            'class' => 'DevGroup\DataStructure\Properties\Module',
        ];
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

    public function testGetAll()
    {
        $expected = Yii::$app->multilingual->getAllLanguages();
        $expected = ArrayHelper::map($expected, 'id', 'iso_639_2t');
        $got = LanguageHelper::getAll();
        $this->assertEquals($expected, $got);
        return $expected;
    }

    /**
     * @depends testGetAll
     */
    public function testGetCurrent($langs)
    {
        $this->assertEquals(LanguageHelper::getCurrent(), $langs[Yii::$app->multilingual->language_id]);
    }
}