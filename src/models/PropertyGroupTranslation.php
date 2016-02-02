<?php
namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\db\ActiveRecord;

/**
 * Class PropertyGroupTranslation
 * @package DevGroup\DataStructure\models
 *
 * @property string  $name
 * @property integer $language_id
 *
 */
class PropertyGroupTranslation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_group_translation}}';
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
        ];
    }
}