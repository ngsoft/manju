<?php

namespace Manju;

class Connection {

    /** @var string */
    private $name = "default";

    /** @var string */
    private $dsn;

    /** @var string|null */
    private $username;

    /** @var string|null */
    private $password;

    /** @var bool */
    private $frozen;

    public function __construct(
            iterable $config = [],
            bool $frozen = false,
            string $name = null
    ) {
        if ($name !== null) $this->name = $name;
        $this->dsn = $config["dsn"] ?? null;
        $this->username = $config["username"] ?? null;
        $this->password = $config["password"] ?? null;
        $this->frozen = $frozen;
        if (empty($this->dsn)) throw new RuntimeException("Invalid Connection, no dsn is supplied.");
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDsn(): string {
        return $this->dsn;
    }

    public function getUsername(): ?string {
        return $this->username;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function getFrozen(): bool {
        return $this->frozen;
    }

}
