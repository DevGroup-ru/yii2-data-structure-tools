<?php

namespace DevGroup\DataStructure\widgets;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyPropertyGroup;
use DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler;
use DevGroup\DataStructure\traits\PropertiesTrait;
use rmrevin\yii\fontawesome\component\Icon;
use yii\base\Exception;
use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\VarDumper;

class PropertiesForm extends Widget
{
    /**
     * @var ActiveRecord | PropertiesTrait
     */
    public $model;
    public $viewFile = 'properties-form';

    public $addPropertyGroupRoute = 'properties/manage/add-model-property-group';
    public $deletePropertyGroupRoute = 'properties/manage/delete-model-property-group';

    public function run()
    {
        if (!$this->model instanceof ActiveRecord || !$this->model->hasMethod('ensurePropertyGroupIds')) {
            // @todo May be just call empty return here?
            throw new Exception('Field "model" must be an ActiveRecord and uses a PropertiesTrait');
        }
        //
        $applicablePropertyModelId = PropertiesHelper::applicablePropertyModelId(get_class($this->model));
        $availableGroups = ArrayHelper::map(
            PropertyGroup::findAll(['applicable_property_model_id' => $applicablePropertyModelId]),
            'id',
            function($model) {
                return !empty($model->name) ? $model->name : $model->internal_name;
            }
        );
        //
        $models = [$this->model];
        PropertiesHelper::fillProperties($models);
        $attachedGroups = $this->model->propertyGroupIds;
        $dropDownItems = [];
        $tabs = [];
        foreach ($availableGroups as $id => $name) {
            $isAttached = in_array($id, $attachedGroups);
            $dropDownItems[] = [
                'content' => '',
                'label' => $name,
                'url' => '#' . $id,
                'linkOptions' => [
                    'data-group-id' => $id,
                    'data-is-attached' => $isAttached,
                    'data-action' => 'add-property-group',
                ],
            ];
            if ($isAttached) {
                $content = '';
                /** @var Property[] $properties */
                $properties = Property::find()
                    ->from(Property::tableName() . ' p')
                    ->innerJoin(
                        PropertyPropertyGroup::tableName() . ' ppg',
                        'p.id = ppg.property_id'
                    )
                    ->where(['ppg.property_group_id' => $id])
                    ->all();
                foreach ($properties as $property) {
                    $content .= $property
                        ->handler()
                        ->renderProperty(
                            $this->model, $property,
                            AbstractPropertyHandler::BACKEND_EDIT
                        );
                }
                $tabs[] = [
                    'label' => Html::encode($name)
                        . '&nbsp;'
                        . Html::button(
                            new Icon('close'),
                            [
                                'class' => 'btn btn-danger btn-xs',
                                'data-group-id' => $id,
                                'data-action' => 'delete-property-group',
                            ]
                        ),
                    'content' => $content,
                ];
            }
        }
        $tabs[] = [
            'label' => Html::button(new Icon('plus'), ['class' => 'btn btn-primary btn-xs']),
            'items' => $dropDownItems,
        ];
        $dropDownItems = null;
        echo $this->render(
            $this->viewFile,
            [
                'attachedGroups' => $attachedGroups,
                'availableGroups' => $availableGroups,
                'tabs' => $tabs,
            ]
        );
    }
}
