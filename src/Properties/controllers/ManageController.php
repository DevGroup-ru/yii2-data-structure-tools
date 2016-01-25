<?php

namespace DevGroup\DataStructure\Properties\controllers;

use DevGroup\AdminUtils\controllers\BaseController;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\Properties\actions\DeletePropertyGroup;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\Properties\actions\EditPropertyGroup;
use DevGroup\DataStructure\Properties\actions\EditStaticValue;
use DevGroup\DataStructure\Properties\actions\ListGroupProperties;
use DevGroup\DataStructure\Properties\actions\ListPropertyGroups;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\web\Response;

class ManageController extends BaseController
{

    /**
     * This controller just uses actions in extension
     * @return array
     */
    public function actions()
    {
        return [
            'list-property-groups' => [
                'class' => ListPropertyGroups::className(),
            ],
            'edit-property-group' => [
                'class' => EditPropertyGroup::className(),
            ],
            'delete-property-group' => [
                'class' => DeletePropertyGroup::className(),
            ],
            'list-group-properties' => [
                'class' => ListGroupProperties::className(),
            ],
            'edit-property' => [
                'class' => EditProperty::className(),
            ],
            'edit-static-value' => [
                'class' => EditStaticValue::className()
            ],
            'delete-static-value' => [
                'class' => DeleteStaticValue::className()
            ]
        ];
    }

    /**
     * @param $className string | PropertiesTrait
     * @param $modelId
     * @param $groupId
     */
    public function actionAddModelPropertyGroup($className, $modelId, $groupId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            /** @var PropertiesTrait $model */
            $model = $className::findOne($modelId);
            /** @var PropertyGroup $group */
            $group = PropertyGroup::findOne($groupId);
            return $model->addPropertyGroup($group);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $className string | PropertiesTrait
     * @param $modelId
     * @param $groupId
     */
    public function actionDeleteModelPropertyGroup($className, $modelId, $groupId)
    {
        /** @var PropertiesTrait $model */
        $model = $className::findOne($modelId);
        /** @var PropertyGroup $group */
        $group = PropertyGroup::findOne($groupId);
        return $model->deletePropertyGroup($group);
    }

    public function actionRender()
    {
        //
    }
}
