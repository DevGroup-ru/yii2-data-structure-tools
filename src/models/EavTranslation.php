<?php

namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use DevGroup\DataStructure\models\ApplicablePropertyModels;

/**
 * Class EavTranslation
 * @package DevGroup\DataStructure\models
 * @property integer $id
 * @property integer $language_id
 * @property integer $apl_model_id
 * @property integer $model_id
 * @property string $string
 * @property string $text
 */
class EavTranslation extends ActiveRecord
{
    protected $eavTable;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%eav_translation}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['model_id', 'language_id', 'apl_model_id'], 'required'],
            [['model_id', 'language_id', 'apl_model_id'], 'integer'],
            [['string', 'text'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '',
            'language_id' => '',
            'apl_model_id' => '',
            'model_id' => '',
            'string' => '',
            'text' => '',
        ];
    }

    /**
     * @return $this
     */
    public function getParent()
    {
        // return $this->hasOne(Realty::class, ['id' => 'model_id']);
    }

    /**
     * @param ActiveRecord | HasProperties | PropertiesTrait $model
     * @return EavTranslation
     * @throws InvalidConfigException
     */
    public static function prepare($model)
    {
        if ($model->hasMethod('ensurePropertyGroupIds', false) === false) {
            throw new InvalidConfigException('Model class must has PropertiesTrait.');
        }
        $translation = new self;
        $translation->apl_model_id = ApplicablePropertyModels::getIdForClass(get_class($model));
        $translation->eavTable = $model->eavTable();
        return $translation;
    }

    public function getTranslation($langId = null)
    {
        $langId = is_null($langId) ? Yii::$app->multilingual->language_id : $langId;
        if (null === $t = self::findOne([
                'language_id' => $langId,
                'apl_model_id' => $this->apl_model_id,
            ])
        ) {
            $t = new self;
            $t->apl_model_id = $this->apl_model_id;
            $t->language_id = $langId;
        }
        return $t;
    }
}