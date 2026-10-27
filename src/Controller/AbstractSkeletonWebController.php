<?php
declare(strict_types=1);

namespace Skeleton\Controller;

use EntelisTeam\Lbaf\Core\Controller\AbstractWebController;

/**
 * Абстрактный класс для всех web контроллеров
 */
abstract class AbstractSkeletonWebController extends AbstractWebController
{
    /**
     * Рендерит шаблон страницы внутри общего каркаса view/layout.php.
     * Содержимое собирается первым и передается в layout готовой строкой
     */
    protected function render(string $template, string $title, array $params = []): string
    {
        $content = $this->display_var($template, $params);

        return $this->display_var('layout', [
            'title' => $title,
            'content' => $content,
        ]);
    }
}
