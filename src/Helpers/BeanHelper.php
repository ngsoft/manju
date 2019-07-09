<?php

namespace Manju\Helpers;

use Manju\Exceptions\ManjuException;
use Manju\Interfaces\Converter;
use Manju\ORM;
use Manju\ORM\Model;
use NGSOFT\Tools\Traits\Logger;
use Psr\Log\LoggerAwareTrait;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use RedBeanPHP\OODBBean;
use Throwable;
use function NGSOFT\Tools\autoloadDir;
use function NGSOFT\Tools\findClassesImplementing;

class BeanHelper extends SimpleFacadeBeanHelper {

    use Logger,
        LoggerAwareTrait;

    /** @var array<string,string> */
    protected static $models = [];

    /** @var array<string,array> */
    public static $metadatas = [];

    /** @var array<string,Converter> */
    public static $converters = [];

    /** @var array<AnnotationFilter> */
    public static $filters = [];

    /** @var Model|null */
    protected $for;

    /**
     * Add a model to the list
     * @param Model $model
     */
    public static function addModel(Model $model) {
        if ($type = $model->getMeta("type")) self::$models[$type] = get_class($model);
    }

    public function getModelForBean(OODBBean $bean) {
        $type = $bean->getMeta('type');
        try {
            if ($this->for instanceof Model) {
                $model = $this->for;
                $this->for = null;
            } elseif (isset(self::$models[$type])) {
                $class = self::$models[$type];
                $model = new $class();
            } else throw new ManjuException("Cannot find any model with type $type");
            $model->loadBean($bean);
            return $model;
        } catch (Throwable $exc) {
            $this->log($exc->getMessage());
        }
        return parent::getModelForBean($bean);
    }

    /**
     * Dispense or loads a bean for a given model
     * @param Model $model
     * @param int $id
     */
    public function dispenseFor(Model $model, int $id = null) {
        if (($type = $model->getMeta("type"))) {
            $this->for = $model;
            if (is_int($id)) ORM::load($type, $id);
            else ORM::dispense($type);
        }
    }

    public function __construct(array $models) {
        if ($logger = ORM::getPsrlogger()) $this->setLogger($logger);

        foreach ($models as $path) {
            autoloadDir($path);
        }
        $models = findClassesImplementing(Model::class);
        if (empty($models)) throw new ManjuException("Cannot locate any models extending " . Model::class);
        foreach ($models as $model) {
            self::addModel(new $model());
        }
    }

}
