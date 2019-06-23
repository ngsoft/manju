<?php

namespace Manju;

use Manju\Helpers\Bean;
use Psr\Container\ContainerInterface;
use RedBeanPHP\Facade;

define('REDBEAN_OODBBEAN_CLASS', Bean::class);

class ORM extends Facade {

    const VERSION = Bun::VERSION;

    /** @var ContainerInterface */
    protected static $container;

}
