<?php

declare(strict_types=1);

return [
    'id' => 'chart-api',
    'name' => 'Statement Chart API',
    'basePath' => dirname(__DIR__),
    'runtimePath' => dirname(__DIR__) . '/runtime',
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'scriptUrl' => '/index.php',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->data !== null && isset($response->data['status'], $response->data['message']) && !isset($response->data['error'])) {
                    if ($response->statusCode >= 400) {
                        $response->data = [
                            'error' => $response->data['message'] ?? 'Неизвестная ошибка',
                        ];
                    }
                }
            },
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => require __DIR__ . '/routes.php',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
];
