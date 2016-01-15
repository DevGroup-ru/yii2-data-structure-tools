<?=
\yii\bootstrap\ButtonDropdown::widget(
    [
        'label' => 'Add a new property group',
        'options' => [
            'class' => 'btn btn-default',
        ],
        'dropdown' => [
            'items' => $dropDownItems,
        ]
    ]
)
?>
<?=
\yii\bootstrap\Tabs::widget(
    [
        'items' => [
            [
                'label' => 'One',
                'content' => '1',
                'active' => true
            ],
            [
                'label' => 'Two',
                'content' => '2',
            ],
            [
                'label' => 'Three',
                'content' => '3',
            ],
            [
                'label' => 'Four',
                'content' => '4',
            ],
        ],
    ]
);
