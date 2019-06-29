<?php

namespace Manju\Traits;

use Manju\ORM\Model;

trait Metadata {

    /** @var array */
    private $metadata = [
        "type" => null
    ];

    public function getMeta(string $key = null) {
        if (!isset($this->metadata["type"])) {
            //scan Metadata
        }

        if ($key === null) return $this->metadata;
        return $this->metadata[$key] ?? null;
    }

}
