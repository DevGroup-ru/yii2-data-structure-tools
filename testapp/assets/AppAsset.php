<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application assets.
 * Here we are using resources produced by gulp(see gulpfile.js).
 * Original resources are located inside folders: js, libs, sass
 *
 * @package app\assets
 */
class AppAsset extends AssetBundle
{
    public $sourcePath = '@app/assets/dist';
    public $css = [
        'styles/main.css',
        'styles/libs.min.css',
    ];
    public $js = [
        'scripts/main.min.js',
        'scripts/libs.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}
