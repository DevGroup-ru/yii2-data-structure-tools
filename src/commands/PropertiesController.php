<?php

namespace DevGroup\DataStructure\commands;

use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
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
        PropertiesTableGenerator::getInstance()->generate($className);
    }
}