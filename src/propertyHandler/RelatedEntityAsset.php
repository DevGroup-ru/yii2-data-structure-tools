<?php


namespace DevGroup\DataStructure\propertyHandler;


use yii\web\AssetBundle;

class RelatedEntityAsset extends AssetBundle
{
    public $sourcePath = '@DevGroup/DataStructure/propertyHandler/resources/RelatedEntity';
    public $css = [];
    public $js = [
        'scripts.js',
    ];
}