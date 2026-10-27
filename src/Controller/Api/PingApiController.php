<?php
declare(strict_types=1);

namespace Skeleton\Controller\Api;

use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class PingApiController extends AbstractSkeletonApiController
{
    #[Route('GET', '/api/ping')]
    public function ping()
    {
        return ['pong' => true, 'time' => date(DATE_ATOM)];
    }
}
