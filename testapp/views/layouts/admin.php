<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
\kartik\icons\Icon::map($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <?php $this->head() ?>

    <!-- here comes hreflang tag output with alternative languages for this page -->
    <?= \DevGroup\Multilingual\widgets\HrefLang::widget() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="blog-masthead">
    <div class="container-fluid">
        <div class="pull-right">
            <div class="pull-left lang-label">Language:</div>
            <?=
            DevGroup\Multilingual\widgets\LanguageSelector::widget([
                'blockClass' => 'b-language-selector dropdown pull-left'
            ])
            ?>
        </div>
        <?=
        \yii\widgets\Menu::widget([
            'items' => [
                [
                    'label' => Yii::t('app', 'Home'),
                    'url' => '/',
                ],
                [
                    'label' => Yii::t('app', 'Manage property groups'),
                    'url' => ['/properties/manage/list-property-groups']
                ],
                (
                Yii::$app->user->isGuest ?
                    ['label' => 'Login', 'url' => ['/site/login']] :
                    [
                        'label' => 'Logout (' . Yii::$app->user->identity->username . ')' ,
                        'url' => ['/site/logout'],
                        'linkOptions' => ['data-method' => 'post'],
                    ]
                ),
                [
                    'label' => 'GitHub',
                    'url' => 'https://github.com/DevGroup-ru/yii2-data-structure-tools',
                ],
            ],
            'options' => [
                'id' => 'menu-top-menu',
                'class' => 'blog-nav',
                'tag' => 'nav',
            ],
            'encodeLabels' => false,
            'itemOptions' => [
                'tag' => 'span',
                'class' => 'blog-nav-item',
            ],

        ])
        ?>


    </div>
</div>
<div class="container-fluid">
    <div class="blog-header">
        <div class="blog-title h1">
            <?= Yii::t('app', 'Demo site for yii2-data-structure-tools') ?>
        </div>
        <p class="lead blog-description">
            <?= Yii::t('app', 'This is an example application showing main package features.') ?>
        </p>
    </div>
    <div class="row">
        <div class="col-sm-8 blog-main">
            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>
            <?= $content ?>
        </div>
        <div class="col-sm-3 col-sm-offset-1 blog-sidebar">
            <div class="sidebar-module sidebar-module-inset">
                <h4>
                    <i class="fa fa-question-circle"></i>
                    <?= Yii::t('app', 'Hint') ?>
                </h4>
                <p>
                    <?= Yii::t('app', 'You can switch languages in top menu') ?>
                </p>
            </div>
        </div>
    </div>

</div>

<footer class="blog-footer">
    <div class="container-fluid">
        <p class="pull-left">&copy; <a href="http://devgroup.ru/?utm_source=opensource&utm_medium=demo-app&utm_term=footer-link&utm_campaign=yii2-data-structure-tools" target="_blank">DevGroup.ru</a> &amp; contributors <?= date('Y') ?></p>

        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
