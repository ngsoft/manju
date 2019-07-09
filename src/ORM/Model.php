<?php

namespace Manju\ORM;

use DateTime;
use Manju\Traits\DataTypes;
use Manju\Traits\Metadata;
use NGSOFT\Tools\Interfaces\ContainerAware;
use NGSOFT\Tools\Traits\Container;
use NGSOFT\Tools\Traits\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RedBeanPHP\SimpleModel;

abstract class Model extends SimpleModel implements LoggerAwareInterface, ContainerAware {

    use Metadata,
        DataTypes,
        Logger,
        LoggerAwareTrait,
        Container;

    ////////////////////////////   CONSTANTS   ////////////////////////////

    const VALID_BEAN = '/^[a-z][a-z0-9]+$/';
    const VALID_PARAM = '/^[a-zA-Z]\w+$/';

    ////////////////////////////   DEFAULTS PROPERTIES   ////////////////////////////

    /**
     * @var int
     */
    protected $id;

    /**
     * @var DateTime
     */
    protected $created_at = null;

    /**
     * @var DateTime
     */
    protected $updated_at = null;

    /**
     * Get the Entry ID
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Get Entry Creation Date
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime {
        return $this->created_at;
    }

    /**
     * Get Last Update Date
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime {
        return $this->updated_at;
    }

}
