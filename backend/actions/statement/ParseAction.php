<?php

declare(strict_types=1);

namespace app\actions\statement;

use app\forms\UploadForm;
use app\services\StatementParser;
use Yii;
use yii\base\Action;
use yii\web\UploadedFile;

/**
 * Действие для парсинга загруженного HTML-отчёта.
 */
class ParseAction extends Action
{
    public function run(): array
    {
        $form = new UploadForm();
        $form->file = UploadedFile::getInstanceByName('file');

        if (!$form->validate()) {
            Yii::$app->response->statusCode = 400;

            return ['error' => $form->getFirstError('file')];
        }

        $html = file_get_contents($form->file->tempName);
        if ($html === false) {
            Yii::error("Не удалось прочитать файл: {$form->file->tempName}", __METHOD__);
            Yii::$app->response->statusCode = 500;

            return ['error' => 'Не удалось прочитать загруженный файл'];
        }

        $cacheKey = 'statement_' . md5($html);
        $cache = Yii::$app->cache;

        $result = $cache->get($cacheKey);
        if ($result !== false) {
            return $result->jsonSerialize();
        }

        $parser = new StatementParser();
        $result = $parser->parse($html);

        if ($result === null) {
            Yii::$app->response->statusCode = 422;

            return ['error' => 'Не удалось разобрать HTML-отчёт. Убедитесь, что это валидный отчёт.'];
        }

        if (empty($result->operations)) {
            Yii::$app->response->statusCode = 422;

            return ['error' => 'В отчёте не найдены торговые операции.'];
        }

        $cache->set($cacheKey, $result, 3600);

        return $result->jsonSerialize();
    }
}
