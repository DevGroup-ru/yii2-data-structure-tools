<?php

namespace DevGroup\DataStructure\tests;

use DevGroup\DataStructure\searchOld\elastic\Search;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Yii;
use yii\console\Application;

class ElasticIndexControllerTest extends DSTCommonTestCase
{
    public $runWeb = false;

    public function createApp($c)
    {
        Yii::$app = null;
        $config = include 'config/console.php';
        $config['bootstrap'] = ['properties'];
        $config['modules']['properties'] = $c;
        $config['components']['db'] = include 'config/db.php';
        $app = new Application($config);
        Yii::$app->cache->flush();
    }

    public function testGenerator()
    {
        $this->createApp([
            'class' => 'DevGroup\DataStructure\Properties\Module',
            'searchClass' => Search::class,
        ]);
        $client = ClientBuilder::create()->build();
        if (true === $client->indices()->exists(['index' => 'product'])) {
            $client->indices()->delete(['index' => 'product']);
        }
        Yii::$app->runAction('properties/elastic/fill-index');
        $this->assertTrue($client->indices()->exists(['index' => 'product']));
        return $client;
    }

    /**
     * @depends testGenerator
     * @param Client $client
     */
    public function testGeneratorNoElastic($client)
    {
        $this->createApp([
            'class' => 'DevGroup\DataStructure\Properties\Module',
            'searchClass' => Search::class,
            'searchConfig' => [
                'hosts' => ['localhost:9456'],
            ]
        ]);
        if (true === $client->indices()->exists(['index' => 'product'])) {
            $client->indices()->delete(['index' => 'product']);
        }
        Yii::$app->runAction('properties/elastic/fill-index');
        $this->assertFalse($client->indices()->exists(['index' => 'product']));
    }
}