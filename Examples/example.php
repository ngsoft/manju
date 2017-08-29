<?php

require_once dirname(__DIR__) . '/Manju/autoloader.php';
R::setup(sprintf('sqlite:%s',__DIR__ . '/data/example.db'));
R::debug(true);

$p = new Examples\models\product();
//$s = new Examples\models\shop();

