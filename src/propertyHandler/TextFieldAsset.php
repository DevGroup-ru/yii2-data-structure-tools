<?php

namespace DevGroup\DataStructure\propertyHandler;

use yii\web\AssetBundle;

class TextFieldAsset extends AssetBundle
{
    public $sourcePath = '@DevGroup/DataStructure/propertyHandler/resources/TextField';
    public $css = [
        'styles.css',
    ];
    public $js = [
        'scripts.js',
    ];
}
