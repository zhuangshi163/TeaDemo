<?php
include './protected/config/common.conf.php';
include './protected/config/routes.conf.php';
include './protected/config/db.conf.php';
include './protected/config/acl.conf.php';

#Just include this for production mode
//include $config['BASE_PATH'].'deployment/deploy.php';
include $config['BASE_PATH'].'Tea.php';
include $config['BASE_PATH'].'app/TeaConfig.php';

Tea::conf()->set($config);
include $config['BASE_PATH'].'diagnostic/debug.php';

Tea::acl()->rules = $acl;
Tea::acl()->defaultFailedRoute = '/error';

Tea::db()->setDb($dbconfig, $config['APP_MODE']);
//Tea::db()->sql_tracking = true;

Tea::app()->route = $route;

Tea::app()->run();
?>