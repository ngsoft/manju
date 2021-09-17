<?php

declare(strict_types=1);

namespace NGSOFT\Manju\Connection;

use InvalidArgumentException;
use NGSOFT\{
    Manju\BeanHelper, Traits\UnionType
};
use RedBeanPHP\{
    R, ToolBox
};
use Stringable;

/**
 * DSN Builder
 */
abstract class DSN implements Stringable {

    use UnionType;

    /** @var ?string */
    protected $username;

    /** @var ?string */
    protected $password;

    /** @var array<string,string> */
    protected $params = [];

    /** @var ?string */
    protected $DSN;

    /** @var string */
    protected $name;

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Generates a uuid V4
     * @link https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
     * @return string
     */
    private function generate_uuid_v4(): string {
        if (function_exists('com_create_guid') === true) return trim(com_create_guid(), '{}');

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

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
     * Get Connection Name
     * @return string
     */
    public function getName(): string {
        if (is_null($this->name)) $this->name = $this->generate_uuid_v4();
        return $this->name;
    }

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

    ////////////////////////////   RedBean Binding   ////////////////////////////

    /**
     * Get RedBean ToolBox for this Connection
     * @return ?ToolBox
     */
    public function getToolbox(): ?ToolBox {
        $name = $this->getName();
        $toolbox = R::$toolboxes[$name] ?? null;
        if (is_null($toolbox)) {
            R::addDatabase($name, $this->getDSN(), $this->username, $this->password);

            if ($toolbox = R::$toolboxes[$name] ?? null) {
                /** @var ToolBox $toolbox */
                $toolbox->getRedBean()->setBeanHelper(BeanHelper::create());
            }
        }

        if ($toolbox !== null and empty(R::$currentDB)) {
            R::selectDatabase($this->getName());
        }
        return $toolbox;
    }

    /**
     * Set Connection as active into RedBean
     * @return bool
     */
    public function setActive(): bool {
        if (
                $this->getToolbox() and
                !$this->isActive()
        ) {
            return R::selectDatabase($this->getName());
        }
        return false;
    }

    /**
     * Check if connection is active into Redbean
     * @return bool
     */
    public function isActive(): bool {
        return R::$currentDB == $this->getName();
    }

    /**
     * Checks if connection is valid
     * @return bool
     */
    public function canConnect(): bool {
        $return = false;
        if ($toolbox = $this->getToolbox()) {
            $database = $toolbox
                    ->getDatabaseAdapter()
                    ->getDatabase();
            try {
                @$database->connect();
            } catch (\Exception $error) { $error->getCode(); }
            $return = $database->isConnected();
            if (!$this->isActive()) $database->close();
        }

        return $return;
    }

    ////////////////////////////   Magic Methods   ////////////////////////////

    /** {@inheritdoc} */
    public function __toString() {
        return $this->getDSN();
    }

    /** {@inheritdoc} */
    public function __debugInfo() {
        return [
            'dsn' => $this->getDSN()
        ];
    }

}
