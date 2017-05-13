![](http://104.224.175.51/eztpl.png)

A very small PHP template engine class library

Quick usage, easy syntax, very efficient, free to define template syntax, only one file at the core!

If you notice compliance oversights, please send a patch 

```php 
<?php

$obj = src\Eztpl::instance();

$obj->assign('hello', $world);
    
$obj->display('index');

```

# Requirements

The following versions of PHP are supported by this version.

PHP 5.4

PHP 5.5

PHP 5.6

PHP 7.0

PHP 7.1

# License

The MIT License (MIT). Please see [License File](https://github.com/thephpleague/route/blob/master/LICENSE.md) for more information.
