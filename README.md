# entelisteam/lbaf-installer

Шаблон пустого проекта на [entelisteam/lbaf-core](https://github.com/entelisteam/lbaf-core).

## Использование

```bash
composer create-project entelisteam/lbaf-installer my-project
```

Установщик спросит:

- **название проекта** — станет неймспейсом и префиксом классов:
  `MyProjectApplication`, `AbstractMyProjectWebController`, `AbstractMyProjectApiController`;
  обязательный вопрос без значения по умолчанию;
- **часовой пояс** — передается в `Application::init()`, по умолчанию `UTC`;
- **нужен ли phinx** — миграции базы (`phinx.php`, `db/migrations`, `composer migrate`);
- **нужен ли docker** — dev-стенд: nginx, php-fpm, mariadb, phpMyAdmin (`composer up`).

Установка только интерактивная: с `--no-interaction` установщик остановится, так как название
проекта не из чего взять.

После настройки установщик удаляет себя (каталог `installer/` и упоминания в `composer.json`).

## Разработка шаблона

Код шаблона использует плейсхолдер `Skeleton` (`skeleton` — в нижнем регистре): установщик
заменяет его в содержимом и именах файлов. Блоки, которые зависят от ответов, размечаются
строками-маркерами в синтаксисе комментариев файла:

```
# @lbaf-if phinx
...
# @lbaf-else
...
# @lbaf-endif
```

Фича `installer` всегда выключена — так помечаются строки, нужные только самому шаблону.
Проектный README лежит в `installer/stubs/README.md`.

Проверка локально:

```bash
composer create-project entelisteam/lbaf-installer /tmp/test-project dev-main \
    --repository='{"type":"path","url":"'$PWD'","options":{"symlink":false}}'
```
