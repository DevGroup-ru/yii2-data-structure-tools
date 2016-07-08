<?php

namespace DevGroup\DataStructure\Properties\actions;


use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

class RestoreProperty extends BaseAdminAction
{

    public function run($id, $returnUrl)
    {
        /** @var Property $model */
        $model = Property::loadModel(
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