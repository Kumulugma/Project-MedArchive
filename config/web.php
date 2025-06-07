<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'name' => 'MedArchive',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
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
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false, // Ustaw na true dla testów lokalnych
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.hostinger.com',  // Dla Gmail
                'username' => 'automat@k3e.pl', // Twój email
                'password' => 'XlrpmmOM@9;', // Hasło aplikacji (nie zwykłe hasło!)
                'port' => '465',
                'encryption' => 'tls',
            ],
            // Możesz też użyć innych dostawców:
            /*
            // Dla Outlook/Hotmail:
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp-mail.outlook.com',
                'username' => 'twoj-email@outlook.com',
                'password' => 'twoje-haslo',
                'port' => '587',
                'encryption' => 'tls',
            ],
            
            // Dla Yahoo:
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.mail.yahoo.com',
                'username' => 'twoj-email@yahoo.com',
                'password' => 'twoje-haslo-aplikacji',
                'port' => '587',
                'encryption' => 'tls',
            ],
            
            // Dla innych dostawców SMTP:
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'mail.twoja-domena.pl',
                'username' => 'noreply@twoja-domena.pl',
                'password' => 'haslo',
                'port' => '587', // lub 465 dla SSL
                'encryption' => 'tls', // lub 'ssl'
            ],
            */
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
                'change-password' => 'user/change-password',
                
                // Domyślne trasy
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
            ],
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