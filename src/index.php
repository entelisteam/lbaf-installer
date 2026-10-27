<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Skeleton\SkeletonApplication;
use EntelisTeam\Lbaf\Core\Helper\Console;
use EntelisTeam\Lbaf\Core\Router\CliRouter;
use EntelisTeam\Lbaf\Core\Router\FastRouteRouter;
use EntelisTeam\Lbaf\Core\Router\Route\RouteGenerator;

//встроенный сервер php (composer serve): существующую статику из public/ отдает сам
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . '/../public' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$serviceNamespace = 'Skeleton';
$serviceControllerFolder = 'Controller';

$app = SkeletonApplication::init('UTC');

if (defined('STDIN')) {
    $app->setRouter(new CliRouter($argv ?? [], $serviceNamespace, $serviceControllerFolder));
    try {
        $app->run();
    } catch (Throwable $e) {
        echo Console::Colorize(implode("\n", [
            $e::class . ' ' . $e->getCode() . ':' . $e->getMessage(),
            'Stack trace',
            $e->getTraceAsString(),
            '  thrown in ' . $e->getFile() . ' on line ' . $e->getLine(),
        ]), 'ERROR');
        echo "\n";
        throw $e;
    }
} else {
    //RouteGenerator сканирует контроллеры относительно текущей директории
    //display_var рендерит шаблоны из view/ тоже относительно текущей директории
    chdir(__DIR__);
    $routes = RouteGenerator::getRoutes(null, $serviceNamespace, $serviceControllerFolder);
    $app->setRouter(new FastRouteRouter($routes));
    $app->run();
}
