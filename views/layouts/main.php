<?php

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? 'MedArchive - System archiwizacji wyników badań medycznych']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? 'medArchive, badania medyczne, wyniki, archiwizacja']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header id="header">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark fixed-top']
    ]);
    
    if (!Yii::$app->user->isGuest) {
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav me-auto'],
            'items' => [
                ['label' => '<i class="fas fa-tachometer-alt"></i> Dashboard', 'url' => ['/dashboard/index'], 'encode' => false],
                [
                    'label' => '<i class="fas fa-flask"></i> Badania', 
                    'encode' => false,
                    'items' => [
                        ['label' => '<i class="fas fa-file-medical"></i> Szablony badań', 'url' => ['/test-template/index'], 'encode' => false],
                        ['label' => '<i class="fas fa-clipboard-list"></i> Wyniki badań', 'url' => ['/test-result/index'], 'encode' => false],
                        ['label' => '<i class="fas fa-calendar-alt"></i> Kolejka badań', 'url' => ['/test-queue/index'], 'encode' => false],
                    ]
                ],
            ]
        ]);
    }
    
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto'],
        'items' => [
            Yii::$app->user->isGuest
                ? ['label' => '<i class="fas fa-sign-in-alt"></i> Logowanie', 'url' => ['/site/login'], 'encode' => false]
                : '<li class="nav-item dropdown">'
                    . '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">'
                    . '<i class="fas fa-user-circle"></i> ' . Html::encode(Yii::$app->user->identity->username)
                    . '</a>'
                    . '<ul class="dropdown-menu dropdown-menu-end">'
                    . '<li><a class="dropdown-item" href="' . \yii\helpers\Url::to(['/user/profile']) . '"><i class="fas fa-user-cog"></i> Profil</a></li>'
                    . '<li><a class="dropdown-item" href="' . \yii\helpers\Url::to(['/user/settings']) . '"><i class="fas fa-cog"></i> Ustawienia</a></li>'
                    . '<li><a class="dropdown-item" href="' . \yii\helpers\Url::to(['/user/change-password']) . '"><i class="fas fa-key"></i> Zmień hasło</a></li>'
                    . '<li><hr class="dropdown-divider"></li>'
                    . '<li>'
                    . Html::beginForm(['/site/logout'], 'post', ['class' => 'dropdown-item-form'])
                    . Html::submitButton('<i class="fas fa-sign-out-alt"></i> Wyloguj', [
                        'class' => 'dropdown-item btn btn-link p-0 text-start w-100 border-0 bg-transparent'
                    ])
                    . Html::endForm()
                    . '</li>'
                    . '</ul>'
                    . '</li>'
        ]
    ]);
    NavBar::end();
    ?>
</header>

<main id="main" class="flex-shrink-0" role="main">
    <div class="container-fluid">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <div class="row">
                <div class="col-12">
                    <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
                </div>
            </div>
        <?php endif ?>
        
        <div class="row">
            <div class="col-12">
                <?= Alert::widget() ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="page-content">
                    <?= $content ?>
                </div>
            </div>
        </div>
    </div>
</main>

<footer id="footer" class="mt-auto py-4 bg-light">
    <div class="container-fluid">
        <div class="row text-muted">
            <div class="col-md-6 text-start">
                <strong>&copy; MedArchive <?= date('Y') ?></strong>
            </div>
            <div id="support" class="col-md-6 text-end">
                <span class="mb-3 mb-md-0 text-body-secondary">Wspierane przez: <a href="//k3e.pl"><span>K</span>3e.pl</a></span>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>