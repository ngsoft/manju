<?php

namespace Manju\Interfaces;

interface RelationMapper {

    public function setModel(\Manju\ORM\Model $model): void;

    public function setTarget(\Manju\ORM\Model $target): void;

    public function setParams(array $params): void;
}
