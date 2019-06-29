<?php

namespace Manju\Helpers;

use Manju\Exceptions\ManjuException;
use Manju\ORM\Model;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use RedBeanPHP\OODBBean;
use function NGSOFT\Tools\autoloadDir;
use function NGSOFT\Tools\findClassesImplementing;

class BeanHelper extends SimpleFacadeBeanHelper {

    /** @var array<string,string> */
    protected static $models = [];

    public static function addModel(Model $model) {
        $models[$model::beanType()] = get_class($model);
    }

    public function getModelForBean(OODBBean $bean) {





        return parent::getModelForBean($bean);
    }

    public function __construct(array $models) {
        foreach ($models as $path) {
            autoloadDir($path);
        }
        $models = findClassesImplementing(Model::class);
        if (empty($models)) throw new ManjuException("Cannot locate any models extending " . Model::class);
        foreach ($models as $model) {
            self::addModel(new $model);
        }
    }

}
