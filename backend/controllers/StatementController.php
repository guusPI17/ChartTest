<?php

declare(strict_types=1);

namespace app\controllers;

use yii\web\Controller;

class StatementController extends Controller
{
    public function actions(): array
    {
        return [
            'parse' => \app\actions\statement\ParseAction::class,
        ];
    }
}
