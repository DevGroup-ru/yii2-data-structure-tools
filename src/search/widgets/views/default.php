<?php
/**
 * @var \yii\web\View $this
 * @var array $data
 * @var string | array $filterRoute
 * @codeCoverageIgnore
 */
use yii\helpers\Html;

if (false === isset($data['data'], $data['props'])) {
    return '';
}
echo Html::beginForm($filterRoute, 'GET', ['id' => 'props-filter']);
foreach ($data['data'] as $propId => $values) {
    echo $data['props'][$propId] . "<br>";
    foreach ($values as $id => $value) {
        $checked = false;
        if (true === isset($data['selected'][$propId])) {
            $checked = in_array($id, $data['selected'][$propId]);
        }
        echo Html::checkbox('filter[' . $propId . '][]', $checked, ['label' => $value, 'value' => $id]) . "<br>";
    }
}
?>
    <button type="submit">Search</button>
<?= Html::endForm() ?>