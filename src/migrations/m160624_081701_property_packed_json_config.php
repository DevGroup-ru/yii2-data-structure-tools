<?php

use yii\db\Migration;

class m160624_081701_property_packed_json_config extends Migration
{
    public function up()
    {
        $this->renameColumn('{{%property}}', 'packed_json_default_value', 'packed_json_params');
    }

    public function down()
    {
        $this->renameColumn('{{%property}}', 'packed_json_params', 'packed_json_default_value');
    }

}
