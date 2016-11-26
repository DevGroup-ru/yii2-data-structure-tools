<?php
return [
    'DataStructureManager' => [
        'type' => 1,
        'description' => 'Data Structure Tools Management Role',
        'children' => [
            'dst-property-group-view',
            'dst-property-group-edit',
            'dst-property-view',
            'dst-property-edit',
            'dst-static-values-view',
            'dst-static-values-edit',
            'dst-static-values-delete',
        ],
    ],
    'dst-property-group-view' => [
        'type' => 2,
        'description' => 'View Property Groups',
    ],
    'dst-property-group-edit' => [
        'type' => 2,
        'description' => 'Edit Property Groups',
    ],
    'dst-property-view' => [
        'type' => 2,
        'description' => 'View Properties',
    ],
    'dst-property-edit' => [
        'type' => 2,
        'description' => 'Edit Properties',
    ],
    'dst-static-values-view' => [
        'type' => 2,
        'description' => 'View Static Values',
    ],
    'dst-static-values-edit' => [
        'type' => 2,
        'description' => 'Edit Static Values',
    ],
    'dst-static-values-delete' => [
        'type' => 2,
        'description' => 'Delete Static Values',
    ],
    'DataStructureAdministrator' => [
        'type' => 1,
        'description' => 'Data Structure Tools Administration Role',
        'children' => [
            'dst-property-group-delete',
            'dst-property-delete',
            'DataStructureManager',
        ],
    ],
    'dst-property-group-delete' => [
        'type' => 2,
        'description' => 'Delete Property Groups',
    ],
    'dst-property-delete' => [
        'type' => 2,
        'description' => 'Delete Properties',
    ],
];
