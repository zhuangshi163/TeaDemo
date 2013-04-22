<?php

class HelloController extends TeaController{

	public function index(){
        $data['title'] = 'HelloController->index';
        $data['content'] = 'Hello index';
        $data['baseurl'] = Tea::conf()->APP_URL;
        $data['printr'] = $this->params;
        $this->view()->render('template', $data);
	}

	public function walao(){
        $data['title'] = 'HelloController->walao';
        $data['content'] ='Walao is a common expression by a Malaysian' ;
        $data['baseurl'] = Tea::conf()->APP_URL;
        $data['printr'] = $this->params;
        $this->view()->render('template', $data);
	}

	public function sayhi(){
        if(isset($this->params[0]))
            if(isset($_GET['title']))
        		$data['content'] = 'Hi, ' . $_GET['title']. ' ' . $this->params[0];
            else
        		$data['content'] = 'Hi, ' . $this->params[0];
        else
            $data['content'] =  'Please tell me your name!';

        $data['title'] = 'HelloController->sayhi';
        $data['baseurl'] = Tea::conf()->APP_URL;
        $data['printr'] = $this->params;
        $this->view()->render('template', $data);
	}
}

?>