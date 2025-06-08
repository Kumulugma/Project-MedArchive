<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mt-5">
                    <h1 class="display-1 text-primary">
                        <?= Html::encode($exception->statusCode ?? 500) ?>
                    </h1>
                    <p class="fs-3">
                        <span class="text-danger">Oops!</span> 
                        <?= Html::encode($name) ?>
                    </p>
                    <p class="lead text-muted">
                        <?= Html::encode($message) ?>
                    </p>
                    
                    <div class="mt-4">
                        <?= Html::a('<i class="fas fa-home"></i> Powrót do strony głównej', 
                            \yii\helpers\Url::home(), 
                            ['class' => 'btn btn-primary btn-lg']) ?>
                        
                        <?= Html::a('<i class="fas fa-arrow-left"></i> Powrót', 
                            'javascript:history.back()', 
                            ['class' => 'btn btn-outline-secondary btn-lg']) ?>
                    </div>
                </div>
                
                <?php if (YII_ENV_DEV && isset($exception)): ?>
                    <div class="mt-5">
                        <div class="card">
                            <div class="card-header">
                                <h5>Debug Info (tylko w trybie deweloperskim)</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Exception:</strong> <?= get_class($exception) ?></p>
                                <p><strong>File:</strong> <?= Html::encode($exception->getFile()) ?></p>
                                <p><strong>Line:</strong> <?= $exception->getLine() ?></p>
                                
                                <details class="mt-3">
                                    <summary>Stack Trace</summary>
                                    <pre class="mt-2 p-3 bg-light"><?= Html::encode($exception->getTraceAsString()) ?></pre>
                                </details>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>