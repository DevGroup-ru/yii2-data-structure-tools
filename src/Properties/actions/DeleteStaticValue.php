<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use yii\web\NotFoundHttpException;
use Yii;

class DeleteStaticValue extends BaseAdminAction
{

    public function run($id, $return_url)
    {
        $model = StaticValue::loadModel(
            $id,
            false,
            true,
            86400,
            new NotFoundHttpException("StaticValue model with specified id not found")
        );

        if ($model->delete() !== false) {
            Yii::$app->session->setFlash('warning', Module::t('app', 'Property static value deleted.'));
        }
        return $this->controller->redirect($return_url);
    }
}
