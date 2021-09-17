<?php

declare(strict_types=1);

namespace NGSOFT\Manju\Connection;

class PostgreSQL extends DSN {

    protected function getDBType(): string {
        return 'PostgreSQL';
    }

    protected function getPrefix(): string {
        return 'pgsql';
    }

    protected function getValidParams(): array {
        return [
            'host',
            'port',
            'dbname',
        ];
    }

    /**
     * @param string $host
     * @param string $dbname
     * @param ?string $username
     * @param ?string $password
     * @param ?int $port
     */
    public function __construct(
            string $host,
            string $dbname,
            string $username = null,
            string $password = null,
            int $port = null
    ) {

        $this->setParam('host', $host);
        $this->setParam('dbname', $dbname);
        if (!is_null($username)) $this->setUsername($username);
        if (!is_null($password)) $this->setPassword($password);
        if (!is_null($port)) $this->setParam('port', $port);
    }

}
