# График баланса

Веб-приложение для парсинга HTML-отчётов (statement) и визуализации баланса торгового счёта в виде интерактивного графика.  
Тестовое (https://docs.google.com/document/d/1zIcfRvuymaKzcOlehkK6CMHfibblH5NEzxei6r_S7L4/edit?tab=t.0)  
Тестовый файл лежит в reportExamples  
Url для теста: http://185.130.212.93/

## Стек технологий

- **Бэкенд:** PHP 8.2, Yii2 (REST API)
- **Фронтенд:** Vue 3, Vuetify 3, Lightweight Charts (TradingView)
- **Инфраструктура:** Docker (nginx + php-fpm)

## Возможности

- Загрузка файла через выбор или drag-and-drop
- Парсинг HTML-отчётов с извлечением метаданных и операций
- Интерактивный график кумулятивного баланса
- Кеширование результатов парсинга (TTL 1 час)
- Максимальный размер файла — 20 МБ

## Запуск

```bash
docker compose up --build -d
```

Приложение будет доступно по адресу `http://localhost:8080`.

Для использования другого порта:

```bash
APP_PORT=3000 docker compose up --build -d
```

## Как работает

1. Загрузите HTML-отчёт (.html или .htm) — выберите файл или перетащите его в область загрузки
2. Бэкенд парсит отчёт и извлекает все операции с числовым значением profit
3. Фронтенд рассчитывает кумулятивный баланс и отображает его на интерактивном графике

## API

**POST /api/parse** — Парсинг HTML-отчёта

Запрос: `multipart/form-data` с полем `file`.

Ответ:
```json
{
  "meta": {
    "account": "841644",
    "name": "Vermes Arnold",
    "currency": "USD",
    "leverage": "1:300"
  },
  "operations": [
    {
      "index": 0,
      "ticket": "14475673",
      "time": "2015.09.15 23:26:35",
      "type": "balance",
      "item": "",
      "profit": 673.52
    }
  ]
}
```

## Разработка

### Запуск тестов

```bash
cd backend
composer install
vendor/bin/phpunit
```

### Форматирование кода

```bash
cd backend
vendor/bin/php-cs-fixer fix
```

### Фронтенд (dev-сервер с hot reload)

```bash
cd frontend
npm install
npm run dev
```
