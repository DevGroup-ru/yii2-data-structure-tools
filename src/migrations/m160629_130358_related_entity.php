<?php

use DevGroup\DataStructure\propertyHandler\RelatedEntity;
use yii\db\Migration;

class m160629_130358_related_entity extends Migration
{
    public function up()
    {
        $this->insert(
            '{{%property_handlers}}',
            [
                'name' => 'Related entity',
                'class_name' => RelatedEntity::class,
                'sort_order' => 7,
                'packed_json_default_config' => '[]',
            ]
        );
    }

    public function down()
    {
        $this->delete(
            '{{%property_handlers}}',
            ['class_name' => RelatedEntity::class,]
        );
    }

}
