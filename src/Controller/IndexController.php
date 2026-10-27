<?php
declare(strict_types=1);

namespace Skeleton\Controller;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class IndexController extends AbstractSkeletonWebController
{
    #[Route('GET', '/')]
    public function index()
    {
        return $this->render('index', 'Skeleton');
    }
}
