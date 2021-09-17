<?php

declare(strict_types=1);

namespace NGSOFT\Manju\Connection;

use InvalidArgumentException,
    NGSOFT\Traits\UnionType,
    Stringable;

/**
 * DSN Builder
 */
abstract class DSN implements Stringable {

    use UnionType;

    /** @var ?string */
    protected $username;

    /** @var ?string */
    protected $password;

    /** @var bool */
    protected $auth = true;

    /** @var array<string,string> */
    protected $params = [];

    /** @var ?string */
    protected $DSN;

    ////////////////////////////   Abstract Methods   ////////////////////////////

    /**
     * Valid Params to be used for the dsn
     *
     * @return string[]
     */
    abstract protected function getValidParams(): array;

    /**
     * @return string The DSN Prefix (before ':')
     */
    abstract protected function getPrefix(): string;

    /**
     * @return string The database Name
     */
    abstract protected function getDBType(): string;

    ////////////////////////////   Getters/Setters   ////////////////////////////

    /**
     * Get the DSN
     * @return string
     */
    public function getDSN(): string {

        if (is_null($this->DSN)) {
            $params = '';
            foreach ($this->getValidParams() as $key) {
                if (array_key_exists($key, $this->params)) {
                    if (!empty($params)) $params .= ';';
                    $params .= sprintf('%s=%s', $key, $this->params[$key]);
                }
            }

            $this->DSN = sprintf('%s:%s', $this->getPrefix(), $params);
        }

        return $this->DSN;
    }

    /**
     * Set Database UserName
     *
     * @param string $username
     * @return static
     */
    public function setUsername(string $username): self {
        $this->username = $username;
        return $this;
    }

    /**
     * Set Database Password
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self {
        $this->password = $password;
        return $this;
    }

    /**
     * Set a dsn Param
     *
     * @param string $name
     * @param string|int $value
     * @return static
     */
    public function setParam(string $name, $value): self {

        if (!in_array($name, $this->getValidParams())) {
            throw new InvalidArgumentException(sprintf('Invalid argument $name=%s, %s DSN does not implement this.', $name, $this->getDBType()));
        }

        $this->checkType($value, 'string', 'int');
        $value = (string) $value;
        $this->DSN = null;
        $this->params[$name] = $value;

        return $this;
    }

    ////////////////////////////   Magic Methods   ////////////////////////////

    /** {@inheritdoc} */
    public function __toString() {
        return $this->getDSN();
    }

    /** {@inheritdoc} */
    public function __debugInfo() {
        return [
            $this->getPrefix() => $this->params
        ];
    }

}
