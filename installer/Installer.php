<?php
declare(strict_types=1);

namespace EntelisTeam\Lbaf\Installer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use DateTimeZone;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Настройка проекта после composer create-project.
 * Задает вопросы, переименовывает Skeleton в название проекта, вырезает ненужные
 * блоки и в конце удаляет себя вместе с упоминаниями в composer.json
 *
 * Условные блоки в файлах шаблона размечаются строками с маркерами (комментарий —
 * в синтаксисе файла, сама строка с маркером удаляется целиком):
 *   # @lbaf-if phinx
 *   ...
 *   # @lbaf-else
 *   ...
 *   # @lbaf-endif
 */
final class Installer
{
    private const PLACEHOLDER = 'Skeleton';
    private const DEFAULT_TIMEZONE = 'UTC';
    private const PHINX_PACKAGE = 'robmorgan/phinx:^0.16';

    /** что удаляется, если фича не выбрана */
    private const FEATURE_PATHS = [
        'phinx' => ['phinx.php', 'db'],
        'docker' => ['docker', 'docker-compose.yml', '.dockerignore'],
    ];

    /** composer-скрипты фичи */
    private const FEATURE_SCRIPTS = [
        'phinx' => ['migrate', 'migrate:docker'],
        'docker' => ['up', 'down', 'migrate:docker'],
    ];

    /** в эти каталоги не заходим при замене плейсхолдера */
    private const SKIP_DIRS = ['vendor', '.git', '.idea', 'installer'];

    public static function run(Event $event): void
    {
        $io = $event->getIO();
        $root = dirname(__DIR__);

        $name = self::askName($io);
        $timezone = self::askTimezone($io);
        $features = [
            'phinx' => $io->askConfirmation('Нужен phinx для миграций базы? [<comment>Y</comment>/n] ', true),
            'docker' => $io->askConfirmation('Нужен docker? [<comment>Y</comment>/n] ', true),
            //блоки, которые нужны только самому шаблону
            'installer' => false,
        ];

        //README и LICENSE шаблона относятся к установщику, а не к проекту
        copy(__DIR__ . '/stubs/README.md', $root . '/README.md');
        @unlink($root . '/LICENSE');

        foreach (self::FEATURE_PATHS as $feature => $paths) {
            if (!$features[$feature]) {
                foreach ($paths as $path) {
                    self::remove($root . '/' . $path);
                }
            }
        }

        self::processFiles($root, $name, $features);
        self::setTimezone($root . '/src/index.php', $timezone);
        self::renameFiles($root . '/src', $name);
        self::updateComposerJson($root . '/composer.json', $name, $features);
        self::remove(__DIR__);

        //autoload и lock-файл собраны по исходному composer.json — пересобираем
        $command = $features['phinx']
            ? 'require ' . escapeshellarg(self::PHINX_PACKAGE)
            : 'update --lock';
        self::composer($io, $root, $command . ' --no-interaction --no-scripts');

        $io->write(sprintf('<info>Проект %s готов.</info> Подробности запуска — в README.md', $name));
    }

    private static function askName(IOInterface $io): string
    {
        //у названия нет значения по умолчанию, без ответа пользователя настраивать нечего
        if (!$io->isInteractive()) {
            throw new RuntimeException('Название проекта задается только интерактивно, запустите create-project без --no-interaction');
        }

        return $io->askAndValidate(
            'Название проекта (неймспейс и префикс классов): ',
            static function (?string $value) {
                $value = self::toStudly((string)$value);
                if (!self::isValidName($value)) {
                    throw new RuntimeException('Название должно начинаться с буквы и состоять из латинских букв и цифр');
                }
                return $value;
            },
        );
    }

    private static function askTimezone(IOInterface $io): string
    {
        return $io->askAndValidate(
            sprintf('Часовой пояс [<comment>%s</comment>]: ', self::DEFAULT_TIMEZONE),
            static function (?string $value) {
                $value = trim((string)$value);
                if (!in_array($value, DateTimeZone::listIdentifiers(), true)) {
                    throw new RuntimeException('Неизвестный часовой пояс, пример: Europe/Moscow');
                }
                return $value;
            },
            null,
            self::DEFAULT_TIMEZONE,
        );
    }

    /**
     * "my-cool project" → "MyCoolProject"
     */
    private static function toStudly(string $value): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return implode('', array_map('ucfirst', $parts));
    }

    private static function isValidName(string $value): bool
    {
        return preg_match('/^[A-Z][A-Za-z0-9]*$/', $value) === 1;
    }

    /**
     * Вырезает условные блоки и заменяет плейсхолдер во всех текстовых файлах проекта
     * @param array<string, bool> $features
     */
    private static function processFiles(string $root, string $name, array $features): void
    {
        $replace = [
            self::PLACEHOLDER => $name,
            strtolower(self::PLACEHOLDER) => strtolower($name),
        ];

        foreach (self::files($root) as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, "\0")) {
                continue;
            }

            $result = strtr(self::applyConditions($content, $features, $file), $replace);
            if ($result !== $content) {
                file_put_contents($file, $result);
            }
        }
    }

    /**
     * @param array<string, bool> $features
     */
    private static function applyConditions(string $content, array $features, string $file): string
    {
        if (!str_contains($content, '@lbaf-')) {
            return $content;
        }

        $result = [];
        //стек вложенных блоков: выводится ли текущая ветка
        $stack = [];
        foreach (preg_split('/(?<=\n)/', $content) as $line) {
            if (preg_match('/@lbaf-if\s+(\w+)/', $line, $m)) {
                if (!array_key_exists($m[1], $features)) {
                    throw new RuntimeException(sprintf('%s: неизвестная фича "%s"', $file, $m[1]));
                }
                $stack[] = ['parent' => self::isVisible($stack), 'value' => $features[$m[1]]];
            } elseif (str_contains($line, '@lbaf-else')) {
                $stack[array_key_last($stack)]['value'] = !$stack[array_key_last($stack)]['value'];
            } elseif (str_contains($line, '@lbaf-endif')) {
                array_pop($stack);
            } elseif (self::isVisible($stack)) {
                $result[] = $line;
            }
        }

        if ($stack !== []) {
            throw new RuntimeException(sprintf('%s: не закрыт блок @lbaf-if', $file));
        }

        return implode('', $result);
    }

    private static function isVisible(array $stack): bool
    {
        $top = end($stack);
        return $top === false || ($top['parent'] && $top['value']);
    }

    private static function setTimezone(string $file, string $timezone): void
    {
        $content = file_get_contents($file);
        $content = str_replace(
            "::init('" . self::DEFAULT_TIMEZONE . "')",
            '::init(' . var_export($timezone, true) . ')',
            $content,
        );
        file_put_contents($file, $content);
    }

    private static function renameFiles(string $dir, string $name): void
    {
        foreach (self::files($dir) as $file) {
            $basename = basename($file);
            if (str_contains($basename, self::PLACEHOLDER)) {
                rename($file, dirname($file) . '/' . str_replace(self::PLACEHOLDER, $name, $basename));
            }
        }
    }

    /**
     * @param array<string, bool> $features
     */
    private static function updateComposerJson(string $file, string $name, array $features): void
    {
        $json = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        //метаданные пакета-шаблона проекту не нужны
        unset($json['name'], $json['description'], $json['license'], $json['authors']);
        unset($json['autoload']['psr-4']['EntelisTeam\\Lbaf\\Installer\\']);
        unset($json['scripts']['post-create-project-cmd']);

        foreach (self::FEATURE_SCRIPTS as $feature => $scripts) {
            if (!$features[$feature]) {
                foreach ($scripts as $script) {
                    unset($json['scripts'][$script]);
                }
            }
        }
        if ($features['docker'] && !$features['phinx']) {
            $json['scripts']['up'] = 'docker compose up -d --build --wait';
        }

        file_put_contents(
            $file,
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
        );
    }

    private static function composer(IOInterface $io, string $root, string $arguments): void
    {
        $binary = getenv('COMPOSER_BINARY') ?: 'composer';
        $command = sprintf('cd %s && %s %s %s', escapeshellarg($root), escapeshellarg(PHP_BINARY), escapeshellarg($binary), $arguments);

        passthru($command, $code);
        if ($code !== 0) {
            $io->writeError(sprintf('<error>Не удалось выполнить composer %s, запустите вручную</error>', $arguments));
        }
    }

    /**
     * @return string[]
     */
    private static function files(string $dir): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                static fn($current) => !($current->isDir() && in_array($current->getFilename(), self::SKIP_DIRS, true)),
            ),
        );

        $result = [];
        foreach ($iterator as $file) {
            $result[] = $file->getPathname();
        }
        return $result;
    }

    private static function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
