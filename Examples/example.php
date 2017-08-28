<?php

require_once dirname(__DIR__) . '/Manju/autoloader.php';
R::setup(sprintf('sqlite:%s',__DIR__ . '/data/example.db'));


$p = new Examples\models\product();
//$s = new Examples\models\shop();

