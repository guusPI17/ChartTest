<?php

declare(strict_types=1);

namespace app\forms;

use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Форма валидации загруженного файла HTML-отчёта.
 */
class UploadForm extends Model
{
    public ?UploadedFile $file = null;

    public function rules(): array
    {
        return [
            [['file'], 'required', 'message' => 'Файл не загружен'],
            [
                ['file'],
                'file',
                'extensions' => ['html', 'htm'],
                'maxSize' => 20 * 1024 * 1024,
                'wrongExtension' => 'Файл должен быть HTML-документом (.html или .htm)',
                'tooBig' => 'Файл слишком большой (максимум 20 МБ)',
            ],
        ];
    }
}
