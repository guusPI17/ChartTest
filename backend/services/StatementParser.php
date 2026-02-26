<?php

declare(strict_types=1);

namespace app\services;

use app\dto\Operation;
use app\dto\StatementMeta;
use app\dto\StatementResult;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

/**
 * Сервис для парсинга HTML-отчётов (statement).
 *
 * Извлекает из HTML-таблицы метаданные аккаунта и список операций
 * с числовым значением profit (изменение баланса).
 */
class StatementParser
{
    /** Секции, содержащие торговые данные. */
    private const DATA_SECTIONS = ['Closed Transactions', 'Open Trades'];

    /** Все известные секции отчёта. */
    private const ALL_SECTIONS = [
        'Closed Transactions',
        'Open Trades',
        'Working Orders',
        'Summary',
        'Details',
    ];

    /**
     * Парсит HTML-отчёт и возвращает структурированные данные.
     *
     * @param string $html Содержимое HTML-файла
     *
     * @return StatementResult|null Результат парсинга или null при ошибке
     */
    public function parse(string $html): ?StatementResult
    {
        if ($html === '') {
            return null;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        if (!$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            libxml_clear_errors();

            return null;
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length === 0) {
            return null;
        }

        $table = $tables->item(0);
        $meta = $this->extractMeta($table, $xpath);
        $operations = $this->parseOperations($table);

        return new StatementResult(meta: $meta, operations: $operations);
    }

    /**
     * Извлекает метаданные аккаунта из первой строки таблицы.
     */
    private function extractMeta(DOMElement $table, DOMXPath $xpath): StatementMeta
    {
        $fields = [
            'account' => '',
            'name' => '',
            'currency' => '',
            'leverage' => '',
        ];

        $bolds = $xpath->query('.//tr[1]//td/b', $table);
        if ($bolds === false) {
            return new StatementMeta();
        }

        foreach ($bolds as $bold) {
            $text = trim($bold->textContent);
            if (preg_match('/^Account:\s*(.+)$/i', $text, $m)) {
                $fields['account'] = trim($m[1]);
            } elseif (preg_match('/^Name:\s*(.+)$/i', $text, $m)) {
                $fields['name'] = trim($m[1]);
            } elseif (preg_match('/^Currency:\s*(.+)$/i', $text, $m)) {
                $fields['currency'] = trim($m[1]);
            } elseif (preg_match('/^Leverage:\s*(.+)$/i', $text, $m)) {
                $fields['leverage'] = trim($m[1]);
            }
        }

        return new StatementMeta(
            account: $fields['account'],
            name: $fields['name'],
            currency: $fields['currency'],
            leverage: $fields['leverage'],
        );
    }

    /**
     * Парсит все строки-операции из секций "Closed Transactions" и "Open Trades".
     */
    private function parseOperations(DOMElement $table): array
    {
        $operations = [];
        $inDataSection = false;
        $index = 0;

        $rows = $table->getElementsByTagName('tr');

        foreach ($rows as $row) {
            if (!$row instanceof DOMElement) {
                continue;
            }

            $tds = $row->getElementsByTagName('td');
            if ($tds->length === 0) {
                continue;
            }

            // Определяем границы секций
            $sectionName = $this->detectSection($tds);
            if ($sectionName !== null) {
                if (in_array($sectionName, self::DATA_SECTIONS, true)) {
                    $inDataSection = true;
                } else {
                    $inDataSection = false;
                }

                continue;
            }

            if (!$inDataSection) {
                continue;
            }

            // Строка с операцией содержит числовой тикет в первом столбце
            $ticket = trim($tds->item(0)->textContent);
            if (!is_numeric($ticket)) {
                continue;
            }

            // Пропускаем отменённые/истёкшие ордера (colspan вместо отдельных финансовых столбцов)
            if ($this->isUnexecutedOrder($tds)) {
                continue;
            }

            $profit = $this->extractProfit($tds);
            if ($profit === null) {
                continue;
            }

            $operations[] = new Operation(
                index: $index++,
                ticket: $ticket,
                time: $tds->length >= 2 ? trim($tds->item(1)->textContent) : '',
                type: $tds->length >= 3 ? trim($tds->item(2)->textContent) : '',
                item: $this->extractItem($tds),
                profit: $profit,
            );
        }

        return $operations;
    }

    /**
     * Определяет секцию по содержимому строки-заголовка.
     *
     * @return string|null Имя секции или null, если строка не является заголовком
     */
    private function detectSection(DOMNodeList $tds): ?string
    {
        $td = $tds->item(0);
        if (!$td instanceof DOMElement) {
            return null;
        }

        $bolds = $td->getElementsByTagName('b');
        if ($bolds->length === 0) {
            return null;
        }

        $text = trim($bolds->item(0)->textContent);

        foreach (self::ALL_SECTIONS as $section) {
            if (stripos($text, $section) !== false) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Проверяет, является ли строка неисполненным ордером.
     * Такие строки имеют <td colspan="4"> вместо отдельных столбцов Commission/Taxes/Swap/Profit.
     */
    private function isUnexecutedOrder(DOMNodeList $tds): bool
    {
        foreach ($tds as $td) {
            if (!$td instanceof DOMElement) {
                continue;
            }
            if ((int) $td->getAttribute('colspan') === 4) {
                return true;
            }
        }

        return false;
    }

    /**
     * Извлекает значение Profit — последнее mspt-значение в строке.
     */
    private function extractProfit(DOMNodeList $tds): ?float
    {
        for ($i = $tds->length - 1; $i >= 0; $i--) {
            $td = $tds->item($i);
            if (!$td instanceof DOMElement || $td->getAttribute('class') !== 'mspt') {
                continue;
            }

            $text = trim($td->textContent);
            // Убираем обычные и неразрывные пробелы (HTML &nbsp;) для корректного парсинга чисел
            $text = str_replace([' ', "\xC2\xA0", "\xA0"], '', $text);

            if (is_numeric($text)) {
                return round((float) $text, 2);
            }
        }

        return null;
    }

    /**
     * Извлекает торговый инструмент (валютную пару) из строки.
     */
    private function extractItem(DOMNodeList $tds): string
    {
        if ($tds->length >= 5) {
            $text = trim($tds->item(4)->textContent);
            if (preg_match('/^[a-zA-Z]{3,}/', $text)) {
                return $text;
            }
        }

        return '';
    }
}
