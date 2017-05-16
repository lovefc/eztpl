<?php

/**
 * author:Hank
 * by Eztpl
 *
 */

// Define error report
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Refer to the class file
require('./src/Eztpl.php');


$setting = array(
    // left locator
    'tplbegin' => '{(',
    
    // right locator
    'tplend' => ')}',
    
    // template file suffixes
    'suffix' => 'HTML',
    
    // template character encoding
    'charset' => 'utf-8',
    
    // template file for directory, please be aware that you must have read and write permissions
    'dirs' => './templates',
    
    // compile files for directories, please note that you must have read and write permissions
    'tempdirs' => './templates_c',
    
    // the template does not exist, the address that needs to jump, the default will prompt a warning, relative path, absolute path
    'errorurl' => '',
    
    // subdirectory name under the template directory
    'tempdirname' => '',
    
    // forced compiled page, the use of debugging, each time, template file will be forced to compile, true to open, 
    //false to shut down, off by default, such as open, consumption of resources
    'tempopen' => false,
    
    // define template references, for true to be compiled in real time, and when the template file changes, 
    //the file that references this template file will be recompiled
    // the drawback is that the efficiency drops, but the benefit is that you don't have to modify the template file 
    //that contains the template file to recompile it
    // false to shut down, the default open, if sure templates don't need to change, you can delete all compiled file
    //close the,can accelerate the speed of the program
    'includeopen' => true
);

try {
    
    // get a singleton
    $obj = eztpl\Eztpl::instance();
    
    // To configure
    $obj->config($setting);
    
    
    // Called regular regular relationship, one to one correspondence, key, 
	// keys to replace the value, can be a string or anonymous functions, you can write the template code, will be the second compilation
    $preg = array(
        '#\[\@(.*)\]#isuU' => '{(if isset(@\\1))}{(@\\1)}{(/if)}',
        
        '#\[\$(.*)\]#isuU' => '{(if isset($\\1))}{($\\1)}{(/if)}',
        
        '#\[inc\((.*)\)\]#isuU' => '{(include file="\\1")}',
        
        '#\[md5\((.*)\)\]#isuU' => function($m)
        {
            return md5($m[1]);
        },
        
        '#\[list="(.*)"\]([\w\W]+?)\[\/list\]#' => '{(if isset(\\1))}{(foreach \\1)}\\2{(/foreach)}{(/if)}',
        
        '#\[time\]#' => '{(date("Y-m-d H:i"))}'
    );
    
    // Match the regular binding
    $obj->bind($preg);
    
    // If you want to change a setting in the middle, you can use the magic method set class variable name for setting
    $obj->settempopen(true); // Change to mandatory compilation
    
    $world = 'hello world';
    
    // To value within the template, which can be used in the template “hello” on behalf of the variable name in the template, 
	// in order to   better distinguish between variables, all the external variables into use to get the @ symbol
    $obj->assign('hello', $world);
    
    // Compile the template to generate the cache
    $obj->display('index');
    
}
catch (\Exception $e) {
    
    die($e->getMessage());
    
}
