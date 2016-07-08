<?php

namespace DevGroup\DataStructure\Properties\actions;


use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use yii\web\NotFoundHttpException;
use Yii;

/**
 * Class RestoreStaticValue
 * @package DevGroup\DataStructure\Properties\actions
 */
class RestoreStaticValue extends BaseAdminAction
{


    /**
     * @param $id
     * @param $returnUrl
     * @return \yii\web\Response
     * @throws \Exception
     * @throws bool
     */
    public function run($id, $returnUrl)
    {
        /** @var StaticValue $model */
        $model = StaticValue::loadModel(
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