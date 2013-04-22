<?php
include './protected/config/common.conf.php';
include './protected/config/routes.conf.php';
//include './protected/config/db.conf.php';

#Just include this for production mode
//include $config['BASE_PATH'].'deployment/deploy.php';
include $config['BASE_PATH'].'Tea.php';
include $config['BASE_PATH'].'app/TeaConfig.php';

# Uncomment for auto loading the framework classes.
//spl_autoload_register('Tea::autoload');

Tea::conf()->set($config);

# remove this if you wish to see the normal PHP error view.
include $config['BASE_PATH'].'diagnostic/debug.php';

# database usage
//Tea::useDbReplicate();	#for db replication master-slave usage
//Tea::db()->setMap($dbmap);
//Tea::db()->setDb($dbconfig, $config['APP_MODE']);
//Tea::db()->sql_tracking = true;	#for debugging/profiling purpose

Tea::app()->route = $route;

# Uncomment for DB profiling
//Tea::logger()->beginDbProfile('Teawebsite');
Tea::app()->run();
//Tea::logger()->endDbProfile('Teawebsite');
//Tea::logger()->rotateFile(20);
//Tea::logger()->writeDbProfiles();
?>