<?php

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\StaticValue;
use yii\db\Migration;

class m160704_111549_soft_delete extends Migration
{
    public function up()
    {
        $this->addColumn(
            Property::tableName(),
            'is_deleted',
            $this->integer()->notNull()->defaultValue(0)
        );
        $this->addColumn(
            PropertyGroup::tableName(),
            'is_deleted',
            $this->integer()->notNull()->defaultValue(0)
        );
        $this->addColumn(
            StaticValue::tableName(),
            'is_deleted',
            $this->integer()->notNull()->defaultValue(0)
        );
    }

    public function down()
    {
        $this->dropColumn(
            Property::tableName(),
            'is_deleted'
        );
        $this->dropColumn(
            PropertyGroup::tableName(),
            'is_deleted'
        );
        $this->dropColumn(
            StaticValue::tableName(),
            'is_deleted'
        );
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
