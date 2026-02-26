<?php

declare(strict_types=1);

namespace app\dto;

/**
 * Результат парсинга HTML-отчёта: метаданные аккаунта и список операций.
 */
readonly class StatementResult implements \JsonSerializable
{
    /**
     * @param StatementMeta $meta Метаданные аккаунта.
     * @param Operation[] $operations Список операций.
     */
    public function __construct(
        public StatementMeta $meta,
        public array $operations,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta->jsonSerialize(),
            'operations' => array_map(
                fn (Operation $op) => $op->jsonSerialize(),
                $this->operations,
            ),
        ];
    }
}
