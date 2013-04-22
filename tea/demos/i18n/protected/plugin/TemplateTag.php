<?php

//register global/PHP functions to be used with your template files
//You can move this to common.conf.php   $config['TEMPLATE_GLOBAL_TAGS'] = array('isset', 'empty');
//Every public static methods in TemplateTag class (or tag classes from modules) are available in templates without the need to define in TEMPLATE_GLOBAL_TAGS 
Tea::conf()->TEMPLATE_GLOBAL_TAGS = array('upper', 'tofloat', 'sample_with_args', 'debug', 't', 't2');

/**
Define as class (optional)

class TemplateTag {
    public static test(){}
    public static test2(){}
}
**/

// the 1st argument must be the variable passed in from template, the other args should NOT be variables

// And of course you can change it to load ini/xml files for translation
function t($str){
    if(Tea::conf()->lang==Tea::conf()->default_lang)
        return $str;

    include 'lang/' . Tea::conf()->lang .'.lang.php';
    
    if(isset($lang[$str]))
        return $lang[$str];
}

// And of course you can change it to load ini/xml files for translation
function t2($arr){
    if(Tea::conf()->lang==Tea::conf()->default_lang)
        return $arr[1];

    include 'lang/' . Tea::conf()->lang .'.lang2.php';

    if(isset($lang[$arr[0]]))
        return $lang[$arr[0]];
}

?>
