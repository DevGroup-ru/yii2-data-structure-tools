<?php

$params = require(__DIR__ . '/params.php');

return [
    'id' => 'minimal-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'gii'],
    'controllerNamespace' => 'app\commands',
    'controllerMap' => [
        'properties' => '\DevGroup\DataStructure\commands\PropertiesController',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'multilingual' => [
            'class' => \DevGroup\Multilingual\Multilingual::className(),
            'context_id' => 1,
            'language_id' => 1,
            'handlers' => [
                [
                    'class' => \DevGroup\Multilingual\DefaultGeoProvider::className(),
                    'default' => [
                        'country' => [
                            'name' => 'England',
                            'iso' => 'en',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
        ],
    ],
    'params' => $params,
];
