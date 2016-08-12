<?php

namespace DevGroup\DataStructure\assets;


use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class PropertiesAsset extends AssetBundle
{
    public $sourcePath = '@DevGroup/DataStructure/assets';

    public $js = [
        'js/property-wizard.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}