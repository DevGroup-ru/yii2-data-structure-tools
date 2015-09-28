<?php
namespace DevGroup\DataStructure\models;

use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

class PropertyGroup extends ActiveRecord
{

    public static $groupIdToPropertyIds = [];

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
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_group}}';
    }

    /**
     * Relation to pivot table
     * @return \yii\db\ActiveQuery
     */
    public function getGroupProperties()
    {
        return $this
            ->hasMany(
                PropertyPropertyGroup::className(),
                [
                    'property_group_id' => 'id',
                ]
            )
            ->orderBy(
                [
                    'sort_order_group_properties' => SORT_ASC,
                ]
            );
    }

    /**
     * Relation to properties through pivot relation groupProperties
     * @return \yii\db\ActiveQuery
     */
    public function getProperties()
    {
        return $this
            ->hasMany(
                Property::className(),
                [
                    'id' => 'property_id',
                ]
            )
            ->via('groupProperties');
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $this->invalidatePropertyIds();
    }

    /**
     * Invalidates cache of property ids sequence
     */
    public function invalidatePropertyIds()
    {
        Yii::$app->cache->delete("PropertyIdsForGroup:{$this->id}");
    }

    /**
     * Returns property ids sequence array for $property_group_id, uses cache
     *
     * @param int $property_group_id
     *
     * @return int[] Array of property ids for group
     */
    public static function propertyIdsForGroup($property_group_id)
    {
        if (isset(static::$groupIdToPropertyIds[$property_group_id])) {
            return static::$groupIdToPropertyIds[$property_group_id];
        }
        $ids = Yii::$app->cache->lazy(
            function() use($property_group_id) {
                $query = new Query();
                return array_map(
                    function($item) {
                        return intval($item);
                    },
                    $query
                        ->select(['property_id'])
                        ->from(PropertyPropertyGroup::tableName())
                        ->where(['property_group_id' => $property_group_id])
                        ->orderBy(['sort_order_group_properties' => SORT_ASC])
                        ->column(static::getDb())
                );

            },
            "PropertyIdsForGroup:$property_group_id",
            86400
        );
        static::$groupIdToPropertyIds[$property_group_id] = $ids;
        return static::$groupIdToPropertyIds[$property_group_id];
    }
}