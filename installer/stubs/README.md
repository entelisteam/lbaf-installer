# Skeleton

## Техническая реализация

- PHP 8.2+, фреймворк [entelisteam/lbaf-core](https://github.com/entelisteam/lbaf-core)
<!-- @lbaf-if phinx -->
- Миграции базы через [Phinx](https://phinx.org), лежат в `db/migrations`
<!-- @lbaf-endif -->

## Структура

| Путь | Что это |
|------|---------|
| `src/index.php` | точка входа для web и cli |
| `src/SkeletonApplication.php` | приложение, регистрация зависимостей в контейнере |
| `src/Config/StaticConfig.php` | конфиг базы из переменных окружения `APP_DB_*` |
| `src/Controller` | контроллеры, роуты задаются атрибутом `#[Route]` |
| `src/view` | php-шаблоны для `display_var()` |
| `public` | статика |

## Локальный запуск

<!-- @lbaf-if docker -->
### Docker

```sh
<!-- @lbaf-if phinx -->
composer up      # поднять контейнеры и накатить миграции
<!-- @lbaf-else -->
composer up      # поднять контейнеры
<!-- @lbaf-endif -->
composer down    # остановить
```

| URL                          | Что это                             |
|------------------------------|-------------------------------------|
| `http://localhost:8080`      | приложение                          |
| `http://localhost:8080/dbui` | phpMyAdmin, логинится сам под root  |
| `127.0.0.1:33306`            | база снаружи, для IDE               |

### Без docker

<!-- @lbaf-endif -->
```sh
composer serve   # встроенный сервер php на http://127.0.0.1:8000
```

Подключение к базе задается переменными окружения `APP_DB_HOST`, `APP_DB_USER`,
`APP_DB_PASSWORD`, `APP_DB_NAME`, `APP_DB_SOCKET`.

CLI: `php src/index.php Cli/SomeController method --arg=value`.
<!-- @lbaf-if phinx -->

## Миграции

```sh
vendor/bin/phinx create MyNewMigration   # новая миграция в db/migrations
composer migrate                         # накатить
<!-- @lbaf-if docker -->
composer migrate:docker                  # накатить в базу docker-стенда
<!-- @lbaf-endif -->
```
<!-- @lbaf-endif -->

## Обновление фреймворка

```sh
composer update
composer rector:fix   # миграции кода под новую версию lbaf
```
