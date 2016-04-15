<?php

use yii\db\Connection;

return [
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
            'class' => '\yii\caching\DummyCache',
            'as lazy' => [
                'class' => 'DevGroup\TagDependencyHelper\LazyCache',
            ],
        ],
        'multilingual' => [
            'class' => 'DevGroup\Multilingual\Multilingual',
            'default_language_id' => 1,
        ],
        'db' => [
            'class' => Connection::className(),
            'dsn' => 'mysql:host=localhost;dbname=yii2_datastructure',
            'username' => 'root',
            'password' => '',
        ],
    ],
];