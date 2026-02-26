<?php

declare(strict_types=1);

namespace app\tests\functional\actions;

use app\actions\statement\ParseAction;
use app\controllers\StatementController;
use PHPUnit\Framework\TestCase;
use yii\web\Application;
use yii\web\UploadedFile;

class ParseActionTest extends TestCase
{
    protected function setUp(): void
    {
        $_FILES = [];
        UploadedFile::reset();

        $config = require dirname(__DIR__, 2) . '/../config/web.php';
        if (\Yii::$app !== null) {
            \Yii::$app = null;
        }
        new Application($config);
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        UploadedFile::reset();
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__) . '/../fixtures/' . $name;
    }

    private function runAction(): array
    {
        $controller = new StatementController('statement', \Yii::$app);
        $action = new ParseAction('parse', $controller);

        return $action->run();
    }

    public function testNoFileReturns400(): void
    {
        $result = $this->runAction();

        $this->assertSame(400, \Yii::$app->response->statusCode);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Файл не загружен', $result['error']);
    }

    public function testWrongExtensionReturns400(): void
    {
        $_FILES = [
            'file' => [
                'name' => 'report.txt',
                'type' => 'text/plain',
                'tmp_name' => $this->fixturePath('not_html.txt'),
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($this->fixturePath('not_html.txt')),
            ],
        ];

        $result = $this->runAction();

        $this->assertSame(400, \Yii::$app->response->statusCode);
        $this->assertStringContainsString('HTML-документом', $result['error']);
    }

    public function testUnparseableHtmlReturns422(): void
    {
        $_FILES = [
            'file' => [
                'name' => 'empty.html',
                'type' => 'text/html',
                'tmp_name' => $this->fixturePath('empty.html'),
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($this->fixturePath('empty.html')),
            ],
        ];

        $result = $this->runAction();

        $this->assertSame(422, \Yii::$app->response->statusCode);
        $this->assertArrayHasKey('error', $result);
    }

    public function testValidFileReturns200(): void
    {
        $statementPath = dirname(__DIR__, 2) . '/../../reportExamples/statement1.html';
        if (!file_exists($statementPath)) {
            $this->markTestSkipped('Реальный файл statement1.html не найден');
        }

        $_FILES = [
            'file' => [
                'name' => 'statement1.html',
                'type' => 'text/html',
                'tmp_name' => $statementPath,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($statementPath),
            ],
        ];

        $result = $this->runAction();

        $this->assertSame(200, \Yii::$app->response->statusCode);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertSame('841644', $result['meta']['account']);
        $this->assertCount(243, $result['operations']);
    }

    public function testOnlyCancelledReturns422(): void
    {
        $_FILES = [
            'file' => [
                'name' => 'only_cancelled.html',
                'type' => 'text/html',
                'tmp_name' => $this->fixturePath('only_cancelled.html'),
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($this->fixturePath('only_cancelled.html')),
            ],
        ];

        $result = $this->runAction();

        $this->assertSame(422, \Yii::$app->response->statusCode);
        $this->assertArrayHasKey('error', $result);
    }
}
