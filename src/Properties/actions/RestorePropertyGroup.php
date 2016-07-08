<?php

namespace DevGroup\DataStructure\Properties\actions;


use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\Properties\Module;
use yii\web\NotFoundHttpException;
use Yii;

class RestorePropertyGroup extends BaseAdminAction
{


    public function run($id, $returnUrl)
    {
        /** @var PropertyGroup $model */
        $model = PropertyGroup::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException("Property model with specified id not found")
        );

        (boolval($model->restore()) === true) ?
            Yii::$app->session->setFlash('info', Module::t('app', 'Item has been restored.')) :
            Yii::$app->session->setFlash('warning', Module::t('app', 'Item has not been restored.'));


        return $this->controller->redirect($returnUrl);
    }

}