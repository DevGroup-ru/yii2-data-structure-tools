<?php


namespace DevGroup\DataStructure\Properties\actions;


use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use Yii;
use yii\web\Response;

class GetAttributeNames extends BaseAdminAction
{
    public function run()
    {
        $className = Yii::$app->request->post('className');
        Yii::$app->response->format = Response::FORMAT_JSON;
        return PropertiesHelper::getAttributeNamesByClassName($className);
    }
}