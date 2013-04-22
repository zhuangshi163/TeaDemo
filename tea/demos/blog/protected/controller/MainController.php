<?php
/**
 * MainController
 * Feel free to delete the methods and replace them with your own code.
 *
 * @author darkredz
 */
class MainController extends TeaController{

    public function index(){
        //Just replace these
        Tea::loadCore('app/TeaSiteMagic');
        TeaSiteMagic::displayHome();
    }

    public function allurl(){
        Tea::loadCore('app/TeaSiteMagic');
        TeaSiteMagic::showAllUrl();
    }

    public function debug(){
        Tea::loadCore('app/TeaSiteMagic');
        TeaSiteMagic::showDebug($this->params['filename']);
    }

    public function gen_sitemap_controller(){
        //This will replace the routes.conf.php file
        Tea::loadCore('app/TeaSiteMagic');
        TeaSiteMagic::buildSitemap(true);
        TeaSiteMagic::buildSite();
    }

    public function gen_sitemap(){
        //This will write a new file,  routes2.conf.php file
        Tea::loadCore('app/TeaSiteMagic');
        TeaSiteMagic::buildSitemap();
    }

    public function gen_site(){
        Tea::loadCore('app/TeaSiteMagic');
        TeaSiteMagic::buildSite();
    }

    public function gen_model(){
        Tea::loadCore('db/TeaModelGen');
        TeaModelGen::genMySQL();
    }

}
?>