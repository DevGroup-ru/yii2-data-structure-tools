<?php
namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\db\ActiveRecord;

class PropertyTranslation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_translation}}';
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Module::t('app', 'Name'),
            'description' => Module::t('app', 'Description'),
        ];
    }
}