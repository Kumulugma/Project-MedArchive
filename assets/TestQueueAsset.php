<?php

namespace app\assets;

use yii\web\AssetBundle;

class TestQueueAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/test-queue.css',
    ];
    public $js = [
        'js/test-queue.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}