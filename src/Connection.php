<?php

namespace Manju;

use Manju\Exceptions\ManjuException;
use RedBeanPHP\{
    Facade, ToolBox
};

class Connection {

    /** @var string */
    private $name = "manju";

    /** @var string */
    private $dsn;

    /** @var string|null */
    private $username;

    /** @var string|null */
    private $password;

    public function __construct(
            iterable $config = []
    ) {
        foreach (['name', 'dsn', 'username', 'password'] as $param) {
            if (array_key_exists($param, $config)) {
                $this->{$param} = $config[$param];
            }
        }
    }

    /**
     * Set Connection Name
     * @param string $name
     * @return static
     */
    public function setName(string $name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Set DB DSN
     * @param string $dsn
     * @return static
     */
    public function setDsn(string $dsn) {
        $this->dsn = $dsn;
        return $this;
    }

    /**
     * Set DB Username
     * @param string $username
     * @return static
     */
    public function setUsername(string $username) {
        $this->username = $username;
        return $this;
    }

    /**
     * Set DB Password
     * @param string $password
     * @return static
     */
    public function setPassword(string $password) {
        $this->password = $password;
        return $this;
    }

    /** @return string */
    public function getName(): string {
        return $this->name;
    }

    /** @return string|null */
    public function getDSN(): ?string {
        return $this->dsn;
    }

    /** @return string|null */
    public function getUsername(): ?string {
        return $this->username;
    }

    /** @return string|null */
    public function getPassword(): ?string {
        return $this->password;
    }

    /**
     * Adds Connection to RedBeanPHP
     * @internal
     * @return bool
     */
    public function addToRedBean() {
        $name = $this->getName();
        if (!isset(Facade::$toolboxes[$name])) {
            if (!$this->getDSN()) throw new ManjuException("No DSN provided for $name connection.");
            Facade::addDatabase($name, $this->getDSN(), $this->getUsername(), $this->getPassword());
        }
        return isset(Facade::$toolboxes[$name]);
    }

    /**
     * Set Connection as active into RedBean
     * @return boolean
     * @throws ManjuException
     */
    public function setActive() {
        if (!isset(Facade::$toolboxes[$this->getName()])) ORM::addConnection($this);
        if (Facade::selectDatabase($this->getName(), true)) {
            if (Facade:: testConnection() === false) {
                throw new ManjuException("Cannot connect to database on connection " . $this->getName());
            }
            return true;
        }
        return false;
    }

    public function isActive(): bool {
        return Facade::$currentDB == $this->name;
    }

    /**
     * Get RedBean Toolbox for this Connection
     * @return ToolBox|null
     */
    public function getToolbox(): ?ToolBox {
        return Facade::$toolboxes[$this->getName()] ?? null;
    }

}
