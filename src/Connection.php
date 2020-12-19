<?php

namespace Manju;

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

    /** @return string|null */
    public function getName(): ?string {
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

}
