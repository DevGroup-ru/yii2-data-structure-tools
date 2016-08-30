<?php

use yii\db\Connection;

return [
    'id' => 'unit',
    'basePath' => dirname(__DIR__),
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
        'multilingual' => [
            'class' => \DevGroup\Multilingual\Multilingual::class,
            'language_id' => 1,
            'context_id' => 1,
            'handlers' => [
                [
                    'class' => \DevGroup\Multilingual\DefaultGeoProvider::class,
                    'default' => [
                        'country' => [
                            'name' => 'English',
                            'iso' => 'en',
                        ],
                    ],
                ],
            ],
        ],
        'db' => include 'db.php',
        'filedb' => [
            'class' => 'yii2tech\filedb\Connection',
            'path' =>  dirname(dirname(__DIR__)) . '/testapp/config/data',
        ],
        'authManager' => [
            'class' => 'yii\rbac\PhpManager',
            'defaultRoles' => ['DataStructureAdministrator'],
        ],
    ],
];