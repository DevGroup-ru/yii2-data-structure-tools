Yii2 data structures tools
===========================
TBD

**WARNING:** This extension is under active development. Don't use it in production!

[![Build Status](https://travis-ci.org/DevGroup-ru/yii2-data-structure-tools.svg)](https://travis-ci.org/DevGroup-ru/yii2-data-structure-tools)
[![codecov.io](https://codecov.io/github/DevGroup-ru/yii2-data-structure-tools/coverage.svg?branch=master)](https://codecov.io/github/DevGroup-ru/yii2-data-structure-tools?branch=master)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist devgroup/yii2-data-structure-tools "*"
```

or add

```
"devgroup/yii2-data-structure-tools": "*"
```

to the require section of your `composer.json` file.


Usage
-----

TBD


Credits and inspiration sources
-------------------------------

Search concept
--------------
*for now it works only with STATIC VALUES property storage*

*Note, that search will work only with models with `DevGroup\DataStructure\traits\PropertiesTrait` trait 
and `DevGroup\DataStructure\behaviors\HasProperties` behavior connected. See [how to connect (Russian for now)](/docs/ru/how-to-use.md)*

Extension provides flexible system of search. Each property have configuration point that switches ability to use this property in search. 

Basic search will be done in two ways:

- common search against regular databases e.g.: `mysql`, `mariadb` etc;
- elasticsearch indices search.

Main feature is that if you want to use elasticseatch, and defined it in the app config,
but not still configure it your search will just work fine with auto fallback to simple mysql searching. And when your 
elasticsearch will be properly started search will be automatically switched for elasticseatch.  

Preferred search mechanism you can define in the application configuration files, like this:
```
    'modules' => [
    ...
         'properties' => [
             'class' => 'DevGroup\DataStructure\Properties\Module',
             'searchClass' => \DevGroup\DataStructure\search\elastic\Search::class,
             'searchConfig' => [
                 'hosts' => ['host1:9200', 'https://host2:9200'],
                 'watcherClass' => MyWatch::class,
             ]
                       
         ],
    ...
    ],
```

- `searchClass` - class to be used for search. If omit - there will be no search configured,
- `searchConfig` - array additional parameters to be applied for search object, except common search.
  For elastic search following special keys may be set:
  - `hosts` see [hosts config](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html),
  - `watcherClass` - you can use your own watcher for elsticsearch if needed.
  
If you want to start using elasticsearch, first of all you have to  [install and configure it](https://www.elastic.co/guide/en/elasticsearch/reference/current/setup.html).

Then, if you already have entries in your database you may want to generate and load start indices. For this run in console:
```
./yii properties/elastic/fill-index
```
This command will create indices for all properties that you allowed to search.

# How to search
*For now only available to perform filtering against properties static values* 
## At any place you want
```
<?= \DevGroup\DataStructure\search\widgets\FilterFormWidget::widget([
    'modelClass' => \app\models\Page::class,
    'filterRoute' => ['/url/to/filter'],
    'config' => [
        'storage' => [
            EAV::class,
            StaticValues::class,
        ]
    ]
]) ?>
```
This will render basic filter form with all properties and values contained in the elasticsearch index
- `'modelClass'` - required param, any model class name you have in your app with assigned properties and their static values,
- `'filterRoute'` - required param, `action` attribute for rendered filter form,
- `'config'` - optional, additional array of config. Special key `storage` will be used for definition against what property storage
search will be proceed. If you omit it search will be work only against `StaticValues` storage by default 

## In your controller
```
public function actionFilter()
{
  /** @var AbstractSearch $component */
  $search = \Yii::$app->getModule('properties')->getSearch();
  $config = ['storage' => [
      EAV::class,
      StaticValues::class,
    ]
  ];
  $modelIds = $search->findInProperties(Page::class, $config);
  $dataProvider = new ActiveDataProvider([
      //provider config
  ]);
  //other stuff here
}
```
- `Page` - any model class name you have in your app with assigned properties and their static values
- `$modelIds` will contain all found model ids, according to selected property values in filter. Using them you can show anything you want,
- `'$config'` - optional, additional array of config. Special key `storage` will be used for definition against what property storage
search will be proceed. If you omit it search will be work only against `StaticValues` storage by default 

## Filtering logic

Filters uses both intersection and union operations while search.

Lets see, for example you have filter request like this:
```
[
    1 => [2,3],
    13 => [18,9,34]
]
```
First of all this means that we want to find products that has property values assigned with id 2,3 from property with id 1, 
and 18, 9, 34 from property with id 13.

*What will filter do?*
- For now it will find all products with assigned values with ids IN(2,3);
- then it will find all products with assigned values with ids IN(12,9,34);
- and finally it will return to you result of intersection from both previous results.

## How to extend and implement
For all `Search` and `Watch` mechanisms you can use your custom implementation.

Actually you can create and use your own database connection, e.g.: `MongoDB`, `ArangoDB`. 

Or you can just use your custom `Watch` class for elasticsearch index actualization.

Both `Search` and `Watch` classes are implements according interfaces and extends abstract classes
- `DevGroup\DataStructure\search\interfaces\Search` and `DevGroup\DataStructure\search\base\AbstractSearch` for `Search`
- `DevGroup\DataStructure\search\interfaces\Watch` and `DevGroup\DataStructure\search\base\AbstractWatch` for `Watch`

Just extend your class from needed abstract class and define it in application config, like described upper.

If you are realizing custom index, you probably need to create own controller for first time index initialization, like 
`DevGroup\DataStructure\commands\ElasticIndexController`

Define your own `Watch` class in your own `Search` class if necessary.

Clearly created and defined `Watch` class will be automatically subscribed to according system events. 
