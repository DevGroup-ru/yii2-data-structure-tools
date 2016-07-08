<?php

namespace DevGroup\DataStructure\Properties\controllers;

use DevGroup\AdminUtils\controllers\BaseController;
use DevGroup\DataStructure\Properties\actions\AddModelPropertyGroup;
use DevGroup\DataStructure\Properties\actions\AjaxRelatedEntities;
use DevGroup\DataStructure\Properties\actions\DeleteModelPropertyGroup;
use DevGroup\DataStructure\Properties\actions\DeleteProperty;
use DevGroup\DataStructure\Properties\actions\DeletePropertyGroup;
use DevGroup\DataStructure\Properties\actions\DeleteStaticValue;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\Properties\actions\EditPropertyGroup;
use DevGroup\DataStructure\Properties\actions\EditStaticValue;
use DevGroup\DataStructure\Properties\actions\GetAttributeNames;
use DevGroup\DataStructure\Properties\actions\ListGroupProperties;
use DevGroup\DataStructure\Properties\actions\ListPropertyGroups;
use DevGroup\DataStructure\Properties\actions\RestoreProperty;
use DevGroup\DataStructure\Properties\actions\RestorePropertyGroup;
use DevGroup\DataStructure\Properties\actions\RestoreStaticValue;
use Yii;
use yii\filters\VerbFilter;

class ManageController extends BaseController
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
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
            'add-model-property-group' => [
                'class' => AddModelPropertyGroup::class,
            ],
            'delete-model-property-group' => [
                'class' => DeleteModelPropertyGroup::class,
            ],
            'list-property-groups' => [
                'class' => ListPropertyGroups::class,
            ],
            'edit-property-group' => [
                'class' => EditPropertyGroup::class,
            ],
            'delete-property-group' => [
                'class' => DeletePropertyGroup::class,
            ],
            'list-group-properties' => [
                'class' => ListGroupProperties::class,
            ],
            'edit-property' => [
                'class' => EditProperty::class,
            ],
            'edit-static-value' => [
                'class' => EditStaticValue::class,
            ],
            'delete-static-value' => [
                'class' => DeleteStaticValue::class,
            ],
            'delete-property' => [
                'class' => DeleteProperty::class,
            ],
            'get-attributes-names' => [
                'class' => GetAttributeNames::class,
            ],
            'ajax-related-entities' => [
                'class' => AjaxRelatedEntities::class,
            ],
            'restore-property' => [
                'class' => RestoreProperty::class
            ],
            'restore-property-group' => [
                'class' => RestorePropertyGroup::class
            ],
            'restore-static-value' => [
                'class' => RestoreStaticValue::class
            ]
        ];
    }
}
