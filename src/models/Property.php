<?php
namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Class Property
 *
 * @mixin \DevGroup\Multilingual\behaviors\MultilingualActiveRecord
 * @property string  $key
 * @property integer $data_type
 * @property boolean $is_internal
 * @property boolean $allow_multiple_values
 * @property integer $storage_id
 * @property integer $property_handler_id
 * @property array   $default_value
 * @property array   $handler_config
 *
 * @package DevGroup\DataStructure\models
 */
class Property extends ActiveRecord
{
    public static $propertyIdToKey = [];

    use MultilingualTrait;
    use TagDependencyTrait;

    const DATA_TYPE_STRING         = 0;
    const DATA_TYPE_INTEGER        = 1;
    const DATA_TYPE_FLOAT          = 2;
    const DATA_TYPE_TEXT           = 3;
    const DATA_TYPE_PACKED_JSON    = 4;
    const DATA_TYPE_BOOLEAN        = 5;

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
            [['is_internal', 'allow_multiple_values'], 'filter', 'filter'=>'boolval'],
            [['data_type'], 'integer',],
            ['storage_id', function ($attribute) {
                $handlers = PropertiesHelper::storageHandlers();
                return isset($handlers[$this->$attribute]);
            }],
        ];
    }

    /**
     * Perform beforeSave events and do other needed stuff:
     * - send event to property handler(he may want to force is_numeric for example)
     *
     * @param bool $insert
     *
     * @return bool
     * @throws \Exception
     */
    public function beforeSave($insert)
    {
        $result = parent::beforeSave($insert);
        if ($result === false) {
            return false;
        }
        $handler = PropertyHandlerHelper::getInstance()->handlerById($this->property_handler_id);
        return $handler->beforePropertyModelSave($this, $insert);
    }

    /**
     * Perform afterSave events:
     * - flush needed caches
     * - trigger afterPropertyModelSave event of corresponding PropertyHandler
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
        $handler = PropertyHandlerHelper::getInstance()->handlerById($this->property_handler_id);
        $handler->afterPropertyModelSave($this);
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
     * @param int $propertyId
     *
     * @return string|null
     */
    public static function propertyKeyForId($propertyId)
    {

        if (isset(static::$identityMap[$propertyId])) {
            return static::$identityMap[$propertyId]->key;
        }
        if (isset(static::$propertyIdToKey[$propertyId])) {
            return static::$propertyIdToKey[$propertyId];
        }

        static::$propertyIdToKey[$propertyId] = Yii::$app->cache->lazy(
            function () use ($propertyId) {
                $query = new Query();
                return $query
                    ->select(['key'])
                    ->from(static::tableName())
                    ->where(['id' => $propertyId])
                    ->scalar(static::getDb());
            },
            "PropertyKeyForId:$propertyId",
            86400
        );
        return static::$propertyIdToKey[$propertyId];
    }

    /**
     * Returns PropertyHandler for current property
     *
     * @return \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler
     * @throws \Exception
     */
    public function handler()
    {
        return PropertyHandlerHelper::getInstance()->handlerById($this->property_handler_id);
    }
}
