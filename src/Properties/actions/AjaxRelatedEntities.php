<?php


namespace DevGroup\DataStructure\Properties\actions;


use DevGroup\AdminUtils\actions\BaseAdminAction;
use Yii;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class AjaxRelatedEntities extends BaseAdminAction
{

    public function run($search, $className, array $attributes, $attribute, $primary, $sortAttribute, $order)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax === false) {
            throw  new BadRequestHttpException;
        }

        $result = ['more' => false, 'results' => [],];

        if (!empty($search)) {
            $query = new Query();
            // change to ptimary and attr
            $query->select([$primary, 'text' => $attribute])->from($className::tableName())
                ->orderBy([$sortAttribute => intval($order)]);
            $or = ['or'];
            foreach ($attributes as $attribute) {
                $or[] = ['like', $attribute, $search];
            }
            $data = $query->where($or)->all();

            $result['results'] = array_values($data);
        }

        return $result;
    }
}