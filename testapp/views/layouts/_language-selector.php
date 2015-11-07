<?php
use yii\helpers\Html;

/** @var DevGroup\Multilingual\models\Language[] $languages */
/** @var integer $currentLanguageId */
/** @var \DevGroup\Multilingual\Multilingual $multilingual */
/** @var \yii\web\View $this */
/** @var string $blockClass */
/** @var string $blockId */
?>
<ul class="menu">
    <li class="menu-item">
        Language:
        <a class="parent" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#">
            <?= $languages[$currentLanguageId]->name ?> <i class="fa fa-angle-down"></i>
        </a>
        <ul class="sub-menu">
            <?php
            foreach ($languages as $language):
                if ($language->id === $currentLanguageId) {
                    continue;
                }
                ?>
                <li class="menu-item">
                    <a href="<?= $multilingual->translateCurrentRequest($language->id) ?>">
                        <?= $language->name ?>
                    </a>
                </li>
                <?php
            endforeach;
            ?>
        </ul>
    </li>

</ul>
