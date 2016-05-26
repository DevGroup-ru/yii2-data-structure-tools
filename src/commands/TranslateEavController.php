<?php

namespace DevGroup\DataStructure\commands;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyHandlers;
use DevGroup\DataStructure\propertyHandler\TextField;
use DevGroup\DataStructure\propertyHandler\TextArea;
use DevGroup\DataStructure\traits\PropertiesTrait;
use yii\console\Controller;
use yii\helpers\Console;
use Yii;

class TranslateEavController extends Controller
{
    public function actionIndex()
    {
        $handlers = PropertyHandlers::find()
            ->select('id')
            ->where(['class_name' => [TextField::class, TextArea::class]])
            ->column();
        $props = Property::find()
            ->select('id')
            ->where(['property_handler_id' => $handlers])
            ->column();
        $langs = Yii::$app->multilingual->getAllLanguages();
        $ids = [];
        foreach ($langs as $one) {
            $ids[] = $one->id;
            $this->stdout("id: {$one->id}, name: {$one->name}" . PHP_EOL);
        }
        do {
            $this->stdout("please enter your current data language id from list upper: " . PHP_EOL);
            $input = trim(Console::stdin());
        } while (false === in_array($input, $ids));
        $apl = ApplicablePropertyModels::find()->select('class_name')->column();
        foreach ($apl as $class) {
            /**
             * @var \yii\db\ActiveRecord | HasProperties | PropertiesTrait $class
             */
            $eavTable = $class::eavTable();
            $schema = Yii::$app->db->getTableSchema($eavTable);
            if (false === isset($schema->columns['language_id'])) {
                $this->stderr('Before processing you have to apply new migrations!');
                return;
            }
            Yii::$app->db->createCommand()->update(
                $eavTable,
                ['language_id' => $input],
                ['property_id' => $props]
            )->execute();
        }
    }
}