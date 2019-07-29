<?php

namespace Manju\Helpers;

use ArrayAccess,
    Countable,
    IteratorAggregate,
    Manju\ORM\Model;

class Tags implements ArrayAccess, IteratorAggregate, Countable {

    /** @var array<OODBBean> */
    private $tags;

    public static function create(Model $model) {

    }

}
