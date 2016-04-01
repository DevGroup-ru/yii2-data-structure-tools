<?php
namespace DevGroup\DataStructure\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\NamingHelper;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class StaticValue
 * @package DevGroup\DataStructure\models
 * @mixin MultilingualActiveRecord
 * @mixin CacheableActiveRecord
 *
 * @property integer $sort_order
 * @property integer $property_id
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property Property $property
 */
class StaticValue extends ActiveRecord
{
    /**
     * @var array
     */
    public static $valuesByPropertyId = [];

    use MultilingualTrait;
    use TagDependencyTrait;

    /**
     * @param Property|null $property
     * @param array $config
     */
    public function __construct(Property $property = null, $config = [])
    {
        if ($property !== null) {
            $this->property_id = $property->id;
        }
        parent::__construct($config);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sort_order', 'property_id'], 'integer',],
            [['property_id'], 'required',],
            [['slug'], 'default', 'value' => ''],
            [['id'], 'integer', 'on' => 'search'],
            [['name','slug'], 'safe', 'on'=>'search'],
            [['sort_order', 'property_id'], 'filter', 'filter' => 'intval'],
        ];
    }
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
            'ContinuousNumericalSortableBehavior' => [
                'class' => ContinuousNumericalSortableBehavior::className(),
                'sortAttribute' => 'sort_order'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_value}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Module::t('app', 'ID'),
            'property_id' => Module::t('app', 'Property ID'),
            'sort_order' => Module::t('app', 'Sort Order'),
            'name' => Module::t('app', 'Name'),
            'description' => Module::t('app', 'Description'),
            'slug' => Module::t('app', 'Slug'),
        ];
    }

    /**
     * Performs beforeSave event
     *
     * @param bool $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!$this->sort_order) {
            $property = Property::findById($this->property_id);
            $this->sort_order = count(static::valuesForProperty($property));
        }
        return parent::beforeSave($insert);
    }

    /**
     * Returns array of possible values for property.
     * Array consists of arrays with elements: name, description, slug and key is static_value.id
     *
     * @param \DevGroup\DataStructure\models\Property $property
     *
     * @return array
     */
    public static function valuesForProperty(Property $property)
    {
        $propertyId = $property->id;

        $table = static::tableName();
        $translationsTable = StaticValueTranslation::tableName();
        $db = static::getDb();
        $languageId = Yii::$app->multilingual->language_id;

        if (!isset(static::$valuesByPropertyId[$propertyId])) {
            static::$valuesByPropertyId[$propertyId] = Yii::$app->cache->lazy(
                function () use ($propertyId, $table, $translationsTable, $db, $languageId) {
                    $query = new Query();
                    $rows = $query
                        ->select([
                            'sv.id',
                            'svt.name',
                            'svt.description',
                            'svt.slug',
                        ])
                        ->from($table . ' sv')
                        ->innerJoin(
                            $translationsTable . 'svt',
                            'svt.model_id = sv.id AND svt.language_id=:language_id',
                            [
                                ':language_id' => $languageId,
                            ]
                        )
                        ->orderBy('sv.sort_order ASC')
                        ->all($db);
                    return ArrayHelper::map(
                        $rows,
                        'id',
                        function ($item) {
                            return [
                                'name' => $item['name'],
                                'description' => $item['description'],
                                'slug' => $item['slug'],
                            ];
                        }
                    );
                },
                "StaticValues:$propertyId:$languageId",
                86400,
                "StaticValues:$propertyId"
            );
        }
        return static::$valuesByPropertyId[$propertyId];
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        // clear static cache for current property_id static values
        static::$valuesByPropertyId[$this->property_id] = null;

        if (isset($changedAttributes['property_id'])) {
            // clear old static cache for old property_id static values
            static::$valuesByPropertyId[$changedAttributes['property_id']] = null;
        }

        TagDependency::invalidate(
            Yii::$app->cache,
            [
                'StaticValues:' . $this->property_id
            ]
        );
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProperty()
    {
        return $this->hasOne(Property::className(), ['id' => 'property_id']);
    }

    /**
     * @param null $propertyId
     * @param array $params
     *
     * @return ActiveDataProvider
     * @internal param $propertyGroupId
     * @codeCoverageIgnore
     */
    public function search($propertyId = null, $params = [])
    {
        $query = self::find();
        if ($propertyId !== null) {
            $query->where(['property_id' => $propertyId]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 8,
            ],
        ]);
        $dataProvider->sort->attributes['name'] = [
            'asc' => ['static_value_translation.name' => SORT_ASC],
            'desc' => ['static_value_translation.name' => SORT_DESC],
        ];

        $dataProvider->sort->defaultOrder = ['sort_order' => SORT_ASC];

        if (!($this->load($params))) {
            return $dataProvider;
        }

        // perform filtering
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['sort_order' => $this->sort_order]);


        // filter by multilingual field
        $query->andFilterWhere(['like', 'static_value_translation.slug', $this->slug]);
        $query->andFilterWhere(['like', 'static_value_translation.name', $this->name]);


        return $dataProvider;
    }
}
