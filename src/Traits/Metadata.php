<?php

namespace Manju\Traits;

use Manju\ORM\Model;

trait Metadata {

    /** @var array */
    private $metadata = [
        "type" => null
    ];

    public function getMeta(string $key = null) {

    }

    private static function ParseMeta(Model $model) {

    }

}
