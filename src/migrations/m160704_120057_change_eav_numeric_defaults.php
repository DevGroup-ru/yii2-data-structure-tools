<?php

use yii\db\Migration;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;

class m160704_120057_change_eav_numeric_defaults extends Migration
{
    public function up()
    {
        $applModels = ApplicablePropertyModels::find()->select(['class_name'])->column();
        foreach ($applModels as $className) {
            /** @var HasProperties | PropertiesTrait $model */
            $model = new $className;
            $eavTable = $model->eavTable();
            $this->alterColumn(
                $eavTable,
                'value_integer',
                $this->integer()
            );
            $this->alterColumn(
                $eavTable,
                'value_float',
                $this->integer()
            );
            $this->update(
                $eavTable,
                ['value_integer' => null],
                ['value_integer' => 0]
            );
            $this->update(
                $eavTable,
                ['value_float' => null],
                ['value_float' => 0]
            );
        }
    }

    public function down()
    {
        $applModels = ApplicablePropertyModels::find()->select(['class_name'])->column();
        foreach ($applModels as $className) {
            /** @var HasProperties | PropertiesTrait $model */
            $model = new $className;
            $eavTable = $model->eavTable();
            $this->alterColumn(
                $eavTable,
                'value_integer',
                $this->integer()->notNull()->defaultValue(0)
            );
            $this->alterColumn(
                $eavTable,
                'value_float',
                $this->integer()->notNull()->defaultValue(0)
            );
            $this->update(
                $eavTable,
                ['value_integer' => 0],
                ['value_integer' => null]
            );
            $this->update(
                $eavTable,
                ['value_float' => 0],
                ['value_float' => null]
            );
        }
    }
}
