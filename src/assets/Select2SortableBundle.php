<?php

namespace DevGroup\DataStructure\assets;

use arogachev\sortable\assets\SortableColumnAsset;
use kartik\select2\Select2Asset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;
use yii\web\View;

class Select2SortableBundle extends AssetBundle
{

    public $js = [
        'js/select2.sortable.js',
    ];

    public function init()
    {
        parent::init();
        $this->sourcePath = __DIR__ ;
    }

    public $depends = [
        JqueryAsset::class,
        Select2Asset::class,
        SortableColumnAsset::class
    ];
}
