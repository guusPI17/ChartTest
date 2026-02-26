<?php

declare(strict_types=1);

namespace app\dto;

/**
 * Торговая или балансовая операция из HTML-отчёта.
 */
readonly class Operation implements \JsonSerializable
{
    public function __construct(
        /** Порядковый номер операции (с нуля). */
        public int $index,
        /** Номер тикета. */
        public string $ticket,
        /** Время операции. */
        public string $time,
        /** Тип операции (buy, sell, balance и т.д.). */
        public string $type,
        /** Торговый инструмент (пустая строка для балансовых операций). */
        public string $item,
        /** Изменение баланса (комиссия + налоги + своп + профит). */
        public float $profit,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'index' => $this->index,
            'ticket' => $this->ticket,
            'time' => $this->time,
            'type' => $this->type,
            'item' => $this->item,
            'profit' => $this->profit,
        ];
    }
}
