<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'name' => 'MedArchive',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    // Konfiguracja języka polskiego
    'language' => 'pl-PL',
    'sourceLanguage' => 'en-US',
    'timeZone' => 'Europe/Warsaw',
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'your-secret-key-here',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
            'messageClass' => 'yii\symfonymailer\Message'
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        // Konfiguracja formattera dla polskich dat
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'defaultTimeZone' => 'Europe/Warsaw',
            'timeZone' => 'Europe/Warsaw',
            'locale' => 'pl-PL',
            'dateFormat' => 'php:j F Y', // 11 marca 2025
            'datetimeFormat' => 'php:j F Y, H:i', // 11 marca 2025, 14:30
            'timeFormat' => 'php:H:i',
        ],
        // Konfiguracja internacjonalizacji (i18n)
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                ],
                'yii' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Podstawowe trasy
                '' => 'site/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                'request-password-reset' => 'site/request-password-reset',
                'reset-password/<token>' => 'site/reset-password',
                'export/<action>' => 'export/<action>',
                'export/<action>/<format>' => 'export/<action>',
                // Dashboard
                'dashboard' => 'dashboard/index',
                // Badania
                'test-templates' => 'test-template/index',
                'test-templates/create' => 'test-template/create',
                'test-templates/<id:\d+>' => 'test-template/view',
                'test-templates/<id:\d+>/update' => 'test-template/update',
                'test-templates/<id:\d+>/delete' => 'test-template/delete',
                // Wyniki badań
                'test-results' => 'test-result/index',
                'test-results/create' => 'test-result/create',
                'test-results/<id:\d+>' => 'test-result/view',
                'test-results/<id:\d+>/update' => 'test-result/update',
                'test-results/<id:\d+>/delete' => 'test-result/delete',
                // Kolejka badań
                'test-queue' => 'test-queue/index',
                'test-queue/create' => 'test-queue/create',
                'test-queue/<id:\d+>' => 'test-queue/view',
                'test-queue/<id:\d+>/update' => 'test-queue/update',
                'test-queue/<id:\d+>/delete' => 'test-queue/delete',
                // Użytkownik
                'profile' => 'user/profile',
                'settings' => 'user/settings',
                'login-history' => 'user/login-history',
                'change-password' => 'user/change-password',
                'logout-all' => 'user/logout-all',
                'export-login-history' => 'user/export-login-history',
                // Domyślne trasy
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
            ],
        ],
        'thresholdManager' => [
            'class' => 'app\components\MedicalThresholdManager'
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
