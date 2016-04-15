<?php

namespace DevGroup\DataStructure\Properties\controllers;

use DevGroup\AdminUtils\controllers\BaseController;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\Properties\actions\DeleteProperty;
use DevGroup\DataStructure\Properties\actions\DeletePropertyGroup;
use DevGroup\DataStructure\Properties\actions\DeleteStaticValue;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\Properties\actions\EditPropertyGroup;
use DevGroup\DataStructure\Properties\actions\EditStaticValue;
use DevGroup\DataStructure\Properties\actions\ListGroupProperties;
use DevGroup\DataStructure\Properties\actions\ListPropertyGroups;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ManageController extends BaseController
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'delete-model-property-group' => ['delete'],
                    'add-model-property-group' => ['post'],
                ],
            ],
        ];
    }

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
                'class' => EditStaticValue::className(),
            ],
            'delete-static-value' => [
                'class' => DeleteStaticValue::className(),
            ],
            'delete-property' => [
                'class' => DeleteProperty::className(),
            ],
        ];
    }

    /**
     * @param $className string | PropertiesTrait
     * @param $modelId
     * @param $groupId
     */
    public function actionAddModelPropertyGroup($className, $modelId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$groupId = Yii::$app->request->post('groupId')) {
            throw new BadRequestHttpException();
        }

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
    public function actionDeleteModelPropertyGroup($className, $modelId)
    {
        if (!$groupId = Yii::$app->request->post('groupId')) {
            throw new BadRequestHttpException();
        }
        /** @var PropertiesTrait $model */
        $model = $className::findOne($modelId);
        /** @var PropertyGroup $group */
        $group = PropertyGroup::findOne($groupId);
        return $model->deletePropertyGroup($group);
    }
}
