<?php

use yii\db\Migration;
use DevGroup\DataStructure\models\Property;

class m160429_121414_add_in_search_column extends Migration
{
    public function up()
    {
        $this->addColumn(
            Property::tableName(),
            'in_search',
            $this->boolean()->defaultValue(0)
        );
    }

    public function down()
    {
        $this->dropColumn(Property::tableName(), 'in_search');
    }
}
