<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'medarchive',
    'name' => 'MedArchive',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
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
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => false,
            'transport' => [
                'scheme' => 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            ],
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
                // Export
                'export/<action>' => 'export/<action>',
                'export/<action>/<format>' => 'export/<action>',
                // Szablony badań - podstawowe operacje
                'test-templates' => 'test-template/index',
                'test-templates/create' => 'test-template/create',
                'test-templates/<id:\d+>' => 'test-template/view',
                'test-templates/<id:\d+>/update' => 'test-template/update',
                'test-templates/<id:\d+>/delete' => 'test-template/delete',
                // Parametry szablonów
                'test-templates/<id:\d+>/add-parameter' => 'test-template/add-parameter',
                'test-templates/<id:\d+>/update-parameter/<parameterId:\d+>' => 'test-template/update-parameter',
                'test-templates/<id:\d+>/delete-parameter/<parameterId:\d+>' => 'test-template/delete-parameter',
                // Konfiguracja ostrzeżeń
                'test-templates/<id:\d+>/configure-warnings' => 'test-template/configure-warnings',
                'test-templates/<id:\d+>/bulk-enable-warnings' => 'test-template/bulk-enable-warnings',
                'test-templates/<id:\d+>/quick-enable-warning/<parameterId:\d+>' => 'test-template/quick-enable-warning',
                // Normy - podstawowe operacje
                'test-templates/<id:\d+>/add-norm/<parameterId:\d+>' => 'test-template/add-norm',
                'test-templates/<id:\d+>/update-norm/<parameterId:\d+>/<normId:\d+>' => 'test-template/update-norm',
                'test-templates/<id:\d+>/delete-norm/<parameterId:\d+>/<normId:\d+>' => 'test-template/delete-norm',
                // AJAX endpoints dla norm
                'test-templates/<id:\d+>/get-parameter-norms' => 'test-template/get-parameter-norms',
                'test-templates/delete-norm-ajax' => 'test-template/delete-norm-ajax',
                'test-templates/enable-norm-warnings' => 'test-template/enable-norm-warnings',
                'test-templates/disable-norm-warnings' => 'test-template/disable-norm-warnings',
                // Wyniki badań
                'test-results' => 'test-result/index',
                'test-results/create' => 'test-result/create',
                'test-results/<id:\d+>' => 'test-result/view',
                'test-results/<id:\d+>/update' => 'test-result/update',
                'test-results/<id:\d+>/delete' => 'test-result/delete',
                'test-results/<id:\d+>/export' => 'test-result/export',
                'test-results/<id:\d+>/export/<format>' => 'test-result/export',
                'test-results/compare/<templateId:\d+>' => 'test-result/compare',
                'test-results/get-chart-data' => 'test-result/get-chart-data',
                // Kolejka badań
                'test-queue' => 'test-queue/index',
                'test-queue/create' => 'test-queue/create',
                'test-queue/<id:\d+>' => 'test-queue/view',
                'test-queue/<id:\d+>/update' => 'test-queue/update',
                'test-queue/<id:\d+>/delete' => 'test-queue/delete',
                'test-queue/<id:\d+>/process' => 'test-queue/process',
                // Użytkownik
                'profile' => 'user/profile',
                'settings' => 'user/settings',
                'login-history' => 'user/login-history',
                'change-password' => 'user/change-password',
                // API endpoints
                'api/<controller:\w+>/<action:\w+>' => 'api/<controller>/<action>',
                // Domyślne trasy
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                // Wyniki badań - zaktualizowane reguły
                'test-results' => 'test-result/index',
                'test-results/create' => 'test-result/create',
                'test-results/<id:\d+>' => 'test-result/view',
                'test-results/<id:\d+>/update' => 'test-result/update',
                'test-results/<id:\d+>/delete' => 'test-result/delete',
                'test-results/<id:\d+>/export' => 'test-result/export',
                'test-results/<id:\d+>/export/<format>' => 'test-result/export',
                'test-results/compare/<templateId:\d+>' => 'test-result/compare',
// AJAX endpoints dla wyników
                'test-results/get-chart-data' => 'test-result/get-chart-data',
                'test-results/get-template-parameters' => 'test-result/get-template-parameters',
                'test-results/validate-value' => 'test-result/validate-value',
// Debug endpoint (opcjonalny, można usunąć w produkcji)
                'test-results/<id:\d+>/debug-norms' => 'test-result/debug-norms',
            ],
        ],
        'thresholdManager' => [
            'class' => 'app\components\MedicalThresholdManager'
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'locale' => 'pl-PL',
            'defaultTimeZone' => 'Europe/Warsaw',
            'dateFormat' => 'php:j F Y', // 10 marca 2025
            'timeFormat' => 'php:H:i', // 14:30
            'datetimeFormat' => 'php:j F Y, H:i' // 10 marca 2025, 14:30
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
