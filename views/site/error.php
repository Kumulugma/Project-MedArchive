use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="text-center">
                <h1 class="display-1"><?= Html::encode($exception->statusCode ?? 500) ?></h1>
                <p class="fs-3"><span class="text-danger">Oops!</span> <?= Html::encode($name) ?></p>
                <p class="lead"><?= Html::encode($message) ?></p>
                <a href="<?= \yii\helpers\Url::home() ?>" class="btn btn-primary">Powrót do strony głównej</a>
            </div>
        </div>
    </div>
</div>