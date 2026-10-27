<?php
declare(strict_types=1);

namespace Skeleton;

use EntelisTeam\Lbaf\Core\Application\AbstractApplication;
use EntelisTeam\Lbaf\Core\Container\Container;
use EntelisTeam\Lbaf\MySql\MySql;
use Skeleton\Config\StaticConfig;

class SkeletonApplication extends AbstractApplication
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        //соединение создается лениво — только когда MySql впервые попросят через #[Inject]
        $container->addDefinitions([
            MySql::class => function () {
                return new MySql(StaticConfig::getMySqlConfig());
            },
        ]);
    }
}
