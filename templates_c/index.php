<?php
 if(!defined('EZTPL')){
 die('Forbidden access');
}
?>
<b>[1]</b> <br />
<?php $a=123456; ?>
Output: <?php echo $a;?> <br />
<br />
Sometimes not output, you can add in front! For example, perform some functions or methods that do not return values
<br />
<br />
No output: <?php $a;?>
<br />
<br />

<b>[2]</b>
<br />
<?php if($this->includes('./templates/a.HTML','./templates_c/a.php')){ require('./templates_c/a.php'); } ?>
<br />
<br />
The above is a reference to the a.t pl template, which can also be referenced except for the reference template
<br />
For example.
<br />
<br />
<?php
require('./include.php');
?>
<br />
<br />

<b>[3]</b>
 <br />
Sometimes we need to assign a value to an array, which is the same as in PHP, which is the same <br />
<?php echo $m[] = "hello";?> <br />
Array output: <?php echo $m['0'];?> <br />
<br />
If it's not a multidimensional array, you can use the.
$m. 0 is equal to $m [0], and of course, $m [0], for example, the multidimensional array must be used [], for example $m [0] [1], which cannot be written as $m. 0.1
<br />
<br />
<br />
<br />

<b>[4]</b>
<br />
The loop function is one of the most common functions we use, and it plays a huge role in database paging and display
<br />
Foreach loop: <br />
This is a function that we normally use to go through the various groups
There are two methods of use in the template.
<br />
Example a
<br />
<?php echo $arr = array (1, 2, 3, 4, 5);?>
<?php foreach($arr as $key=>$value){ ?>
<?php echo $key;?> : <?php echo $value;?> <br />
<?php } ?>
<br />
Above only wrote one parameter, foreach can have three parameters, it is a kind of simple notation, in the inner loop, $key represents the key value, $value represents an array of values, this is the default definitions, you can define your own, see example 2
<br />
<br />
Example 2: <br />
<?php $arr=array (1, 2, 3, 4, 5); ?>
<?php foreach($arr as $a=>$b){ ?>
<?php echo $a;?> : <?php echo $b;?> <br />
<?php } ?>
<br />
<br />
The following other cycles are used
<br />
<br />
While loop <br />
Example: <br />
<?php $i=0; ?>
<?php while($i<10){ ?>
<?php echo $i++;?> <br />
<?php } ?>
<br />
<br />
For loop <br />
Example: <br />
<?php for($i=0;$i<10;$i++){ ?>
<?php echo $i;?> <br />
<?php } ?> <br />
<br />

<b>[5]</b>
 <br />
If judgment
<br />
<?php $f=123; ?>
<?php if(isset($f)){ ?>
$f is not empty
<?php } ?>
<br />
<br />

<b>[6]</b>
<br />
<?php if($_GET['a']==null){ ?>
The $get['a'] in the template represents $get['a'], $post represents $post, these are global variables,

<br />
<a href="./index.php?a=123 "> clicks the value of $get['a'] at 123 </a>
<?php }elseif($_GET['a']==123){ ?>
$get['a'] is equal to 123 <br />
<br />
<a href="./index.php?a="> clicks this change for $get ['a'] for empty </a>
<?php } ?>
<br />
<br />
<br />

<b>[7]</b>
<br / >
Custom replace <br /> <br />
827ccb0eea8a706c4c34a16891f84e7b <br />
Use this tag to use the md5 encryption string <br /> <br />
Output time: <?php echo date("Y-m-d H:i");?>