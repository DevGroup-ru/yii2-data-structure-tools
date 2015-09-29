<?php

namespace DevGroup\DataStructure\commands;

use Yii;
use yii\console\Controller;
use yii\console\Exception;

class PropertiesController extends Controller
{
    public function actionIndex($className)
    {
        if (class_exists($className) === false) {
            throw new Exception("Class with name $className does not exists.");
        }

    }
}