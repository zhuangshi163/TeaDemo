<?php
/**
 * Description of MainController
 *
 * @author darkredz
 */
class MainController extends TeaController{

    public function index(){
        $data['title'] = 'Welcome to URI Demo Home';
        $data['content'] = 'Here you can test and learn about the handling of URI routes in Tea framework.';
        $data['content'] .= '<p>Check out the links page for a list of URLs available in this demo.</p>';
        $data['baseurl'] = Tea::conf()->APP_URL;
        $data['printr'] = null;
        $this->view()->render('about', $data);
    }

    public function url(){
        $data['title'] = 'URL used in this demo';
        $data['content'] = 'Replace :var with your values.<br/><em>Request type */GET = You can test and visit these links.</em>';
        $data['baseurl'] = Tea::conf()->APP_URL;

        include Tea::conf()->SITE_PATH .'protected/config/routes.conf.php';
        $data['printr'] = array();
        $n = 1;
        foreach($route as $req=>$r){
            foreach($r as $rname=>$value){
                //$rname_strip = (strpos($rname, '/')===0)? substr($rname, 1, strlen($rname)) : $rname;
                $rname_strip = 'index.php'.$rname;
                $data['printr'][$n++ .strtoupper(" $req")] = '<a href="'.Tea::conf()->APP_URL.$rname_strip.'">'.$rname.'</a>';
            }
        }
        $this->view()->render('template', $data);
    }

    public function example(){
        $data['baseurl'] = Tea::conf()->APP_URL;
        $data['printr'] = file_get_contents(Tea::conf()->SITE_PATH .'protected/config/routes.conf.php');
        $this->view()->render('example', $data);
    }


}
?>