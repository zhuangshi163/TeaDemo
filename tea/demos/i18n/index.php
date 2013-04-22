<?php
include './protected/config/common.conf.php';
include './protected/config/routes.conf.php';
#include './protected/config/db.conf.php';

include $config['BASE_PATH'].'Tea.php';
include $config['BASE_PATH'].'app/TeaConfig.php';

Tea::conf()->set($config);
include $config['BASE_PATH'].'diagnostic/debug.php';

Tea::app()->route = $route;
Tea::app()->run();
?>