<?php

use yii\db\Migration;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;
use yii\helpers\Console;

class m160520_133649_add_lang_id extends Migration
{
    public function up()
    {
        $apl = ApplicablePropertyModels::find()->select('class_name')->column();
        foreach ($apl as $class) {
            /**
             * @var \yii\db\ActiveRecord | HasProperties | PropertiesTrait $class
             */
            $eavTable = $class::eavTable();
            $schema = Yii::$app->db->getTableSchema($eavTable);
            if (false === isset($schema->columns['language_id'])) {
                $this->addColumn(
                    $eavTable,
                    'language_id',
                    $this->integer()->notNull()->defaultValue(0)
                );
            }
        }
    }

    public function down()
    {
        $apl = ApplicablePropertyModels::find()->select('class_name')->column();
        foreach ($apl as $class) {
            /**
             * @var \yii\db\ActiveRecord | HasProperties | PropertiesTrait $class
             */
            $eavTable = $class::eavTable();
            $schema = Yii::$app->db->getTableSchema($eavTable);
            if (true === isset($schema->columns['language_id'])) {
                $this->dropColumn($eavTable, 'language_id');
            }
        }
    }
}
