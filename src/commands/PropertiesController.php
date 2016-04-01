<?php

namespace DevGroup\DataStructure\commands;

use DevGroup\DataStructure\helpers\PropertiesTableGenerator;
use Yii;
use yii\console\Controller;
use yii\console\Exception;

/**
 * Class PropertiesController is a helper for yii2 cli
 * @codeCoverageIgnore
 * @package DevGroup\DataStructure\commands
 */
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
