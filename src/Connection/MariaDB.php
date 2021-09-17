<?php

declare(strict_types=1);

namespace NGSOFT\Manju\Connection;

class MariaDB extends DSN {

    const DEFAULT_CHARSET = 'utf8mb4';

    /** {@inheritdoc} */
    protected function getDBType(): string {
        return 'MariaDB';
    }

    /** {@inheritdoc} */
    protected function getPrefix(): string {
        return 'mysql';
    }

    /** {@inheritdoc} */
    protected function getValidParams(): array {

        return [
            'host',
            'port',
            'dbname',
            'unix_socket',
            'charset'
        ];
    }

    /**
     * @param string $host
     * @param string $dbname
     * @param ?string $username
     * @param ?string $password
     * @param ?int $port
     * @param string $charset
     */
    public function __construct(
            string $host,
            string $dbname,
            string $username = null,
            string $password = null,
            int $port = null,
            string $charset = self::DEFAULT_CHARSET
    ) {

        $this->setParam('host', $host);
        $this->setParam('dbname', $dbname);
        $this->setParam('charset', $charset);
        if (!is_null($username)) $this->setUsername($username);
        if (!is_null($password)) $this->setPassword($password);
        if (!is_null($port)) $this->setParam('port', $port);
    }

}
