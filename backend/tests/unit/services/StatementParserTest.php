<?php

declare(strict_types=1);

namespace app\tests\unit\services;

use app\dto\Operation;
use app\dto\StatementMeta;
use app\dto\StatementResult;
use app\services\StatementParser;
use PHPUnit\Framework\TestCase;

class StatementParserTest extends TestCase
{
    private StatementParser $parser;

    protected function setUp(): void
    {
        $this->parser = new StatementParser();
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__ . '/../../fixtures/' . $name);
    }

    private function realStatement(): string
    {
        $path = dirname(__DIR__, 3) . '/../reportExamples/statement1.html';
        if (!file_exists($path)) {
            $this->markTestSkipped('Реальный файл statement1.html не найден');
        }

        return file_get_contents($path);
    }

    // ─── Реальный файл ────────────────────────────────

    public function testRealStatementMeta(): void
    {
        $result = $this->parser->parse($this->realStatement());

        $this->assertNotNull($result);
        $this->assertSame('841644', $result->meta->account);
        $this->assertSame('Vermes Arnold', $result->meta->name);
        $this->assertSame('USD', $result->meta->currency);
        $this->assertSame('1:300', $result->meta->leverage);
    }

    public function testRealStatementOperationsCount(): void
    {
        $result = $this->parser->parse($this->realStatement());

        $this->assertNotNull($result);
        $this->assertCount(243, $result->operations);
    }

    public function testRealStatementFinalBalance(): void
    {
        $result = $this->parser->parse($this->realStatement());

        $this->assertNotNull($result);
        $balance = array_sum(array_map(fn (Operation $op) => $op->profit, $result->operations));
        $this->assertEqualsWithDelta(968.30, $balance, 0.01);
    }

    public function testRealStatementFirstOperationIsBalance(): void
    {
        $result = $this->parser->parse($this->realStatement());

        $this->assertNotNull($result);
        $first = $result->operations[0];
        $this->assertSame('14475673', $first->ticket);
        $this->assertSame('balance', $first->type);
        $this->assertSame(673.52, $first->profit);
    }

    // ─── Минимальный отчёт ────────────────────────────

    public function testMinimalStatementOperationsCount(): void
    {
        $result = $this->parser->parse($this->fixture('statement_minimal.html'));

        $this->assertNotNull($result);
        $this->assertCount(3, $result->operations);
    }

    public function testMinimalStatementMeta(): void
    {
        $result = $this->parser->parse($this->fixture('statement_minimal.html'));

        $this->assertNotNull($result);
        $this->assertSame('12345', $result->meta->account);
        $this->assertSame('Test User', $result->meta->name);
        $this->assertSame('USD', $result->meta->currency);
        $this->assertSame('1:100', $result->meta->leverage);
    }

    public function testMinimalStatementBalanceChange(): void
    {
        $result = $this->parser->parse($this->fixture('statement_minimal.html'));

        $this->assertNotNull($result);
        $ops = $result->operations;

        // balance: 500.00
        $this->assertSame(500.0, $ops[0]->profit);
        // buy EURUSD: Profit = 30.00
        $this->assertEqualsWithDelta(30.00, $ops[1]->profit, 0.01);
        // buy GBPUSD: Profit = -25.00
        $this->assertEqualsWithDelta(-25.00, $ops[2]->profit, 0.01);

        // Итого: 500 + 30 - 25 = 505.00
        $balance = array_sum(array_map(fn (Operation $op) => $op->profit, $ops));
        $this->assertEqualsWithDelta(505.00, $balance, 0.01);
    }

    // ─── Отчёт с Open Trades ──────────────────────────

    public function testOpenTradesSectionParsed(): void
    {
        $result = $this->parser->parse($this->fixture('statement_with_open_trades.html'));

        $this->assertNotNull($result);
        // 1 balance + 1 closed trade + 1 open trade = 3
        $this->assertCount(3, $result->operations);
    }

    public function testOpenTradesMetaCurrency(): void
    {
        $result = $this->parser->parse($this->fixture('statement_with_open_trades.html'));

        $this->assertNotNull($result);
        $this->assertSame('EUR', $result->meta->currency);
    }

    public function testOpenTradesBalance(): void
    {
        $result = $this->parser->parse($this->fixture('statement_with_open_trades.html'));

        $this->assertNotNull($result);
        $ops = $result->operations;

        // balance: 1000.00
        // closed buy: Profit = 250.00
        // open sell: Profit = 60.00
        $balance = array_sum(array_map(fn (Operation $op) => $op->profit, $ops));
        $this->assertEqualsWithDelta(1310.00, $balance, 0.01);
    }

    // ─── Баланс уходящий к нулю ──────────────────────

    public function testNegativeBalanceProfit(): void
    {
        $result = $this->parser->parse($this->fixture('statement_negative_balance.html'));

        $this->assertNotNull($result);
        $balance = array_sum(array_map(fn (Operation $op) => $op->profit, $result->operations));
        // balance: 100, Profit: -95 → итого 5.00
        $this->assertEqualsWithDelta(5.0, $balance, 0.01);
    }

    // ─── Отменённые/истёкшие ордера ──────────────────

    public function testCancelledOrdersSkipped(): void
    {
        $result = $this->parser->parse($this->fixture('only_cancelled.html'));

        $this->assertNotNull($result);
        $this->assertEmpty($result->operations);
        $this->assertSame('88888', $result->meta->account);
    }

    // ─── Невалидные входные данные ────────────────────

    public function testPlainTextReturnsNull(): void
    {
        $result = $this->parser->parse($this->fixture('not_html.txt'));

        $this->assertNull($result);
    }

    public function testEmptyHtmlReturnsNull(): void
    {
        $result = $this->parser->parse($this->fixture('empty.html'));

        $this->assertNull($result);
    }

    public function testEmptyStringReturnsNull(): void
    {
        $result = $this->parser->parse('');

        $this->assertNull($result);
    }

    public function testNoProfitColumnReturnsEmptyOperations(): void
    {
        $result = $this->parser->parse($this->fixture('no_profit_column.html'));

        $this->assertNotNull($result);
        $this->assertEmpty($result->operations);
    }

    public function testWrongTableStructureReturnsEmptyOperations(): void
    {
        $result = $this->parser->parse($this->fixture('wrong_table_structure.html'));

        $this->assertNotNull($result);
        $this->assertEmpty($result->operations);
    }

    // ─── Структурные проверки ─────────────────────────

    public function testAllProfitsAreFloat(): void
    {
        $result = $this->parser->parse($this->realStatement());

        $this->assertNotNull($result);
        foreach ($result->operations as $op) {
            $this->assertIsFloat($op->profit, "Profit должен быть float для ticket {$op->ticket}");
        }
    }

    public function testOperationHasRequiredFields(): void
    {
        $result = $this->parser->parse($this->fixture('statement_minimal.html'));

        $this->assertNotNull($result);
        $this->assertInstanceOf(StatementResult::class, $result);
        $this->assertInstanceOf(StatementMeta::class, $result->meta);

        foreach ($result->operations as $op) {
            $this->assertInstanceOf(Operation::class, $op);
        }
    }

    public function testOperationIndicesAreSequential(): void
    {
        $result = $this->parser->parse($this->realStatement());

        $this->assertNotNull($result);
        foreach ($result->operations as $i => $op) {
            $this->assertSame($i, $op->index, 'Индекс операции должен быть последовательным');
        }
    }

    public function testMinimalStatementItems(): void
    {
        $result = $this->parser->parse($this->fixture('statement_minimal.html'));

        $this->assertNotNull($result);
        $ops = $result->operations;

        $this->assertSame('', $ops[0]->item); // balance — нет инструмента
        $this->assertSame('EURUSD', $ops[1]->item);
        $this->assertSame('GBPUSD', $ops[2]->item);
    }
}
