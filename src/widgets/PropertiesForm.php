<?php

namespace DevGroup\DataStructure\widgets;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\PropertyGroup;
use yii\base\Exception;
use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class PropertiesForm extends Widget
{
    public $model;
    public $viewFile = 'properties-form';

    public function run()
    {
        if (!$this->model instanceof ActiveRecord) {
            // @todo May be just call empty return here?
            throw new Exception('Field "model" must be an ActiveRecord');
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
        $attachedGroups = $this->model->ensurePropertyGroupIds();
        $dropDownItems = [];
        foreach ($availableGroups as $id => $name) {
            $dropDownItems[] = [
                'label' => $name,
                'url' => '#',
                'linkOptions' => [
                    'data-group-id' => $id,
                    'data-is-attached' => (int) isset($attachedGroups[$id]),
                ],
            ];
        }
        VarDumper::dump($availableGroups, 3, true);
        VarDumper::dump($attachedGroups, 3, true);
        echo $this->render(
            $this->viewFile,
            [
                'attachedGroups' => $attachedGroups,
                'availableGroups' => $availableGroups,
                'dropDownItems' => $dropDownItems,
            ]
        );
    }
}
