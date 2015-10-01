<?php
namespace DevGroup\DataStructure\models;

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
}