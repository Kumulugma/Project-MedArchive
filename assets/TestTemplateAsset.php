<?php

namespace app\assets;

use yii\web\AssetBundle;

class TestTemplateAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/test-template.css',
    ];
    public $js = [
        'js/test-template.js',
    ];
    public $depends = [
        'app\assets\AppAsset',
    ];
}