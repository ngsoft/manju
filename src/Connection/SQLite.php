<?php

declare(strict_types=1);

namespace NGSOFT\Manju\Connection;

class SQLite extends DSN {

    const DEFAULT_PATH = ':memory:';

    /** {@inheritdoc} */
    protected function getDBType(): string {
        return 'SQLite';
    }

    /** {@inheritdoc} */
    protected function getPrefix(): string {

        return 'sqlite';
    }

    /** {@inheritdoc} */
    protected function getValidParams(): array {
        return ['path'];
    }

    /**
     * Set db file path
     *
     * @param string $path
     * @return static
     */
    public function setPath(string $path): self {
        return $this->setParam('path', $path);
    }

    /** {@inheritdoc} */
    public function getDSN(): string {

        if (is_null($this->DSN)) {
            $this->DSN = sprintf('%s:%s', $this->getPrefix(), $this->params['path']);
        }

        return $this->DSN;
    }

    /** @param string $path */
    public function __construct(string $path = self::DEFAULT_PATH) {
        $this->setPath($path);
    }

}
