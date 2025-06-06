<?php

namespace app\assets;

use yii\web\AssetBundle;

class TestResultAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/test-result.css',
    ];
    public $js = [
        'js/test-result.js',
        'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}