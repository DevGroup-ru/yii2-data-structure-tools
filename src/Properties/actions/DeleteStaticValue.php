<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use yii\web\NotFoundHttpException;
use Yii;

/**
 * Class DeleteStaticValue
 * @package DevGroup\DataStructure\Properties\actions
 */
class DeleteStaticValue extends BaseAdminAction
{

    /**
     * @param $id
     * @param $return_url
     * @param bool|false $hard
     * @return \yii\web\Response
     * @throws \Exception
     * @throws bool
     */
    public function run($id, $return_url, $hard = false)
    {
        $model = StaticValue::loadModel(
            $id,
            false,
            true,
            86400,
            new NotFoundHttpException("StaticValue model with specified id not found")
        );

        if ($hard === false) {
            (boolval($model->delete()) === false && $model->isDeleted() === true) ?
                Yii::$app->session->setFlash('info', Module::t('app', 'Item has been hidden.')) :
                Yii::$app->session->setFlash('warning', Module::t('app', 'Item has not been hidden.'));
        } elseif ((bool)$hard === true) {
            $model->hardDelete() !== false ?
                Yii::$app->session->setFlash('danger', Module::t('app', 'Item has been deleted.')) :
                Yii::$app->session->setFlash('warning', Module::t('app', 'Item has not been deleted.'));
        }
        return $this->controller->redirect($return_url);
    }
}
