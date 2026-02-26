<?php

declare(strict_types=1);

namespace app\dto;

/**
 * Метаданные торгового аккаунта из заголовка HTML-отчёта.
 */
readonly class StatementMeta implements \JsonSerializable
{
    public function __construct(
        /** Номер аккаунта. */
        public string $account = '',
        /** Имя владельца аккаунта. */
        public string $name = '',
        /** Валюта аккаунта. */
        public string $currency = '',
        /** Кредитное плечо. */
        public string $leverage = '',
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'account' => $this->account,
            'name' => $this->name,
            'currency' => $this->currency,
            'leverage' => $this->leverage,
        ];
    }
}
