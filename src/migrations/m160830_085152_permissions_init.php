<?php

use yii\db\Migration;
use yii\rbac\Item;

class m160830_085152_permissions_init extends Migration
{
    public static $permissionsConfig = [
        'DataStructureManager' => [
            'descr' => 'Data Structure Tools Management Role',
            'permits' => [
                'dst-property-group-view' => 'View Property Groups',
                'dst-property-group-edit' => 'Edit Property Groups',
                'dst-property-view' => 'View Properties',
                'dst-property-edit' => 'Edit Properties',
                'dst-static-values-view' => 'View Static Values',
                'dst-static-values-edit' => 'Edit Static Values',
                'dst-static-values-delete' => 'Delete Static Values',
            ]

        ],
        'DataStructureAdministrator' => [
            'descr' => 'Data Structure Tools Administration Role',
            'permits' => [
                'dst-property-group-delete' => 'Delete Property Groups',
                'dst-property-delete' => 'Delete Properties',
            ],
            'roles' => [
                'DataStructureManager'
            ]
        ]
    ];

    public function up()
    {
        $createdMap = [];
        $auth = Yii::$app->authManager;
        foreach (self::$permissionsConfig as $roleName => $roleData) {
            if (null !== $auth->getRole($roleName)) {
                continue;
            }
            $role = $auth->createRole($roleName);
            $role->description = $roleData['descr'];
            if (true === $auth->add($role)) {
                $createdMap[$roleName] = $role;
                if (true === isset($roleData['permits'])) {
                    foreach ($roleData['permits'] as $permName => $permDescr) {
                        $canAdd = true;
                        if (true === isset($createdMap[$permName])) {
                            $permission = $createdMap[$permName];
                        } else {
                            if (null === $permission = $auth->getPermission($permName)) {
                                $permission = $auth->createPermission($permName);
                                $permission->description = $permDescr;
                                $canAdd = $auth->add($permission);
                            }
                        }
                        if ($permission instanceof Item && true === $canAdd) {
                            $auth->addChild($role, $permission);
                        }
                    }
                }
                if (true === isset($roleData['roles'])) {
                    foreach ($roleData['roles'] as $roleName) {
                        if (
                            true === isset($createdMap[$roleName])
                            && (true === $createdMap[$roleName] instanceof Item)
                        ) {
                            $auth->addChild($role, $createdMap[$roleName]);
                        }
                    }
                }
            }
        }
    }

    public function down()
    {
        $auth = Yii::$app->authManager;
        $permissions = [];
        foreach (array_column(self::$permissionsConfig, 'permits') as $set) {
            $permissions = array_merge($permissions, array_keys($set));
        }
        foreach ($permissions as $name) {
            $item = $auth->getPermission($name);
            if (null !== $item) {
                $auth->remove($item);
            }
        }
        $roles = array_keys(self::$permissionsConfig);
        foreach ($roles as $name) {
            $item = $auth->getRole($name);
            if (null !== $item) {
                $auth->remove($item);
            }
        }
    }
}
