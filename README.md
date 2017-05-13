# EZTPL

A very small template engine class library

Quick usage, easy syntax, very efficient, free to define template syntax, only one file at the core!


~~~    
<?php

$obj = src\Eztpl::instance();

$obj->assign('hello', $world);
    
$obj->display('index');

~~~
