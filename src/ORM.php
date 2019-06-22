<?php

namespace Manju;

use RedBeanPHP\Facade;

class ORM extends Facade {

    const VERSION = Bun::VERSION;

    /** @var ContainerInterface */
    protected static $container;

}
