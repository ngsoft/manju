<?php

declare(strict_types=1);

namespace NGSOFT\Manju;

use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;

final class BeanHelper extends SimpleFacadeBeanHelper {

    /** @var static */
    private static $instance;

    /** @return self */
    public static function create(): self {
        self::$instance = self::$instance ?? new static();
        return self::$instance;
    }

}
