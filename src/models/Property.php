<?php
namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

class Property extends ActiveRecord
{
    public static $propertyIdToKey = [];

    use MultilingualTrait;
    use TagDependencyTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::className(),
                'translationPublishedAttribute' => false,
            ],
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::className(),
            ],
            'PackedJsonAttributes' => [
                'class' => PackedJsonAttributes::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['key', 'unique'],
            ['key', 'required'],
            ['key', 'string', 'max'=>80],
            [['is_numeric', 'is_internal', 'allow_multiple_values'], 'filter', 'filter'=>'boolval'],
            ['storage_id', function ($attribute, $params) {
                $handlers = PropertiesHelper::storageHandlers();
                return isset($handlers[$this->$attribute]);
            }],
        ];
    }

    /**
     * Perform afterSave events, flush needed caches
     *
     * @param bool  $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (isset($changedAttributes['key'])) {
            Yii::$app->cache->delete("PropertyKeyForId:{$this->id}");
        }
    }

    /**
     * Perform afterFind events, fill static variable(per-process memory cache)
     */
    public function afterFind()
    {
        parent::afterFind();
        static::$propertyIdToKey[$this->id] = $this->key;
    }

    /**
     * Returns property key by property id.
     * Uses identityMap, $propertyIdToKey static variable(per-process memory cache), lazy cache for db query
     *
     * @param int $property_id
     *
     * @return string|null
     */
    public static function propertyKeyForId($property_id)
    {

        if (isset(static::$identityMap[$property_id])) {
            return static::$identityMap[$property_id]->key;
        }
        if (isset(static::$propertyIdToKey[$property_id])) {
            return static::$propertyIdToKey[$property_id];
        }

        static::$propertyIdToKey[$property_id] = Yii::$app->cache->lazy(
            function() use ($property_id) {
                $query = new Query();
                return $query
                    ->select(['key'])
                    ->from(static::tableName())
                    ->where(['id' => $property_id])
                    ->scalar(static::getDb());
            },
            "PropertyKeyForId:$property_id",
            86400
        );
        return static::$propertyIdToKey[$property_id];
    }
}