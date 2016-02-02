<?php
namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\db\ActiveRecord;

class StaticValueTranslation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_value_translation}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'model_id' => Module::t('app', 'Model ID'),
            'language_id' => Module::t('app', 'Language ID'),
            'name' => Module::t('app', 'Name'),
            'description' => Module::t('app', 'Description'),
            'slug' => Module::t('app', 'Slug'),
        ];
    }
}