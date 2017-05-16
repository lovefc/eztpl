<?php

/**
 * Author: Hank
 * Version: 1.6.7
 * Update time: 2017-5-11 15:51
 * Copyright to author Hank all
 */
 
namespace eztpl;

define('EZTPL', true);

class Eztpl
{
    public $eztpl_vars = array();
    public static $eztpl;
    protected $binds;
    protected $instances;
    
    //Get an instance
    public static function instance($ObjName='default')
    {
        if (!isset(self::$eztpl[$ObjName]) || empty($ObjName)) {
            $class        = __CLASS__;
			$ObjName = empty($ObjName) ? 'default' : $ObjName;
            self::$eztpl[$ObjName] = new $class;
            return self::$eztpl[$ObjName];
        }else{
			return self::$eztpl[$ObjName];
		}
    }
    
    //__get () method is used to obtain private attributes
    public function __get($name)
    {
        return $this->$name = isset($this->$name) ? $this->$name : '';
    }
    
    //Magic method used to create methods
    public function __call($method, $args)
    {
        $perfix   = substr($method, 0, 3);
        $property = substr($method, 3);
        if (empty($perfix) || empty($property)) {
            return $this;
        }
        if ($perfix == "set") {
            $this->$property = $args[0];
        }
        return $this;
    }
    
    //Initialization settings (array)
    public function config($setting = null)
    {
        $this->charset     = (!empty($setting['charset'])) ? $setting['charset'] : null;
        $this->tplbegin    = (!empty($setting['tplbegin'])) ? $setting['tplbegin'] : '{(';
        $this->tplend      = (!empty($setting['tplend'])) ? $setting['tplend'] : ')}';
        $this->dirs        = (!empty($setting['dirs'])) ? $setting['dirs'] : './templates';
        $this->tempdirs    = (!empty($setting['tempdirs'])) ? $setting['tempdirs'] : './templates_c';
        $this->suffix      = (!empty($setting['suffix'])) ? $setting['suffix'] : 'tpl';
        $this->errorurl    = (!empty($setting['errorurl'])) ? $setting['errorurl'] : '';
        $this->tempdirname = (!empty($setting['tempdirname'])) ? $setting['tempdirname'] : '';
        $this->tempopen    = (!empty($setting['tempopen'])) ? $setting['tempopen'] : false;
        $this->includeopen = (!empty($setting['includeopen'])) ? $setting['includeopen'] : true;
        return $this;
    }
    
    //Get the compiling file path
    protected function get_compiledfile_url($file_name)
    {
        return $compiledfile_url = (!empty($this->tempdirname)) ? $this->tempdirs . '/' . $this->tempdirname . '/' . $file_name . '.php' :        $this->tempdirs . '/' . $file_name . '.php';
    }
    //Get the template file path
    protected function get_sourcefile_url($file_name)
    {
        
        return $get_sourcefile_url = (!empty($this->tempdirname)) ? $this->dirs . '/' . $this->tempdirname . '/' . $file_name . '.' . $this->        suffix : $this->dirs . '/' . $file_name . '.' . $this->suffix;
    }
    //Determine whether to compile
    protected function is_compiled($source_url, $compiled_url)
    {
        if (!is_readable($source_url)) {
            $this->show_messages('Template file not readable to ' . $source_url);
        }
        if ($this->tempopen or !is_file($compiled_url)) {
            return true;
        }
        if (filemtime($source_url) > filemtime($compiled_url)) {
            return true;
        }
    }
    //Compile the template and write the file
    protected function compile($source_url, $compiled_url)
    {
        if ($this->is_compiled($source_url, $compiled_url)) {
            $this->write_file($compiled_url, $this->compileds($source_url));
        }
    }
    //Refer to the template file
    protected function includes($source_url, $compiled_url)
    {
        $this->compile($source_url, $compiled_url);
        return $compiled_url;
    }
    //The build process
    protected function compileds($source_url)
    {
        $eztpl_left  = self::_quote($this->tplbegin);
        $eztpl_right = self::_quote($this->tplend);
        $content      = $this->place(file_get_contents($source_url));
        if (strpos($content, $this->tplbegin . 'include') !== false) {
            $include_regular = '/' . $eztpl_left . 'include\s+file\s*=\s*["](.+?)["]' . $eztpl_right . '/i';
            if (preg_match_all($include_regular, $content, $include_arr)) {
                $include_arr[1] = array_unique($include_arr[1]);
                foreach ($include_arr[1] as $key => $value) {
                    $str    = $this->tplbegin . 'include file="' . $value . '"' . $this->tplend;
                    $source = $this->get_sourcefile_url($value);
                    if (is_file($source)) {
                        $compiled = $this->tempdirs . '/' . $value . '.php';
                    } else {
                        $source = $this->dirs . '/' . $value . '.' . $this->suffix;
                        if (is_file($source)) {
                            $compiled = $this->tempdirs . '/' . $value . '.php';
                        } else {
                            $compiled = null;
                        }
                    }
                    if ($compiled) {
                        if ($this->includeopen) {
                            $regular = '<?php if($this->includes(\'' . $source . '\',' . '\'' . $compiled . '\')){ require(\'' . $compiled .                            '\'); } ?>';
                        } else {
                            $this->compile($source, $compiled);
                            $regular = "<?php\r\nrequire('{$compiled}');\r\n?>";
                        }
                    } else {
                        if (is_file($value))
                            $regular = "<?php\r\nrequire('{$value}');\r\n?>";
                        else
                            $regular = null;
                    }
                    $content = str_ireplace($str, $regular, $content);
                }
            }
        }
        $else_end_regular = $this->tplbegin . 'else' . $this->tplend;
        if (strpos($content, $else_end_regular)) {
            $else_rep = "<?php\r\n}else{\r\n?>";
            $content  = str_ireplace($else_end_regular, $else_rep, $content);
        }
        $x_end_regular = '/' . $eztpl_left . '\/(if|for|foreach|while|end)' . $eztpl_right . '/i';
        if (preg_match_all($x_end_regular, $content, $end_arr)) {
            $end_arr[0] = array_unique($end_arr[0]);
            foreach ($end_arr[0] as $key => $value) {
                $content = str_replace($value, '<?php } ?>', $content);
            }
        }
        if (strpos($content, $this->tplbegin . 'if') !== false) {
            $if_regular = '/' . $eztpl_left . 'if (.*)' . $eztpl_right . '/isU';
            if (preg_match_all($if_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php if(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->tplbegin . 'elseif') !== false) {
            $elseif_regular = '/' . $eztpl_left . 'elseif (.*)' . $eztpl_right . '/isU';
            if (preg_match_all($elseif_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php }elseif(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->tplbegin . 'foreach') !== false) {
            $foreach_regular = '/' . $eztpl_left . 'foreach (.*)' . $eztpl_right . '/isU';
            if (preg_match_all($foreach_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    if (strpos($value, ' as') === false)
                        $value .= ' as $key=>$value';
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php foreach(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->tplbegin . 'for') !== false) {
            $for_regular = '/' . $eztpl_left . 'for (.*)' . $eztpl_right . '/isU';
            if (preg_match_all($for_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php for(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->tplbegin . 'while') !== false) {
            $while_regular = '/' . $eztpl_left . 'while (.*)' . $eztpl_right . '/isU';
            if (preg_match_all($while_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php while(' . $values . '){ ?>', $content);
                }
            }
        }
        $assign_regular = '/' . $eztpl_left . '(((\$|\@)[\w\.\[\]\$]+)=\s*([\'"].+?[\'"]|.+?))' . $eztpl_right . '/';
        if (preg_match_all($assign_regular, $content, $arr)) {
            foreach ($arr[0] as $key => $value) {
                $rep     = '<?php ' . $this->parse_vars($arr[1][$key]) . '; ?>';
                $content = str_replace($value, $rep, $content);
            }
        }
        $varc_regular = '/' . $eztpl_left . '\!(.*)' . $eztpl_right . '/isU';
        if (preg_match_all($varc_regular, $content, $arr)) {
            foreach ($arr[1] as $key => $value) {
                $values  = $this->parse_vars($value);
                $content = str_replace($arr[0][$key], '<?php ' . $values . ';?>', $content);
            }
        }
        $var_regular = '/' . $eztpl_left . '(.*)' . $eztpl_right . '/U';
        if (preg_match_all($var_regular, $content, $arr)) {
            foreach ($arr[1] as $key => $value) {
                $values  = $this->parse_vars($value);
                $content = str_replace($arr[0][$key], '<?php echo ' . $values . ';?>', $content);
            }
        }
        return $content;
    }
    //Output the template
    public function display($file_name)
    {
        $source_url   = $this->get_sourcefile_url($file_name);
        $compiled_url = $this->get_compiledfile_url($file_name);
        $this->compile($source_url, $compiled_url);
        $this->charset && header('Content-Type:text/html;charset=' . $this->charset);
        require($compiled_url);
    }
    //end
    public function end()
    {
        self::$eztpl = null;
        unset(self::$eztpl);
    }
    
    //cpu
    public static function cpu()
    {
        $memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 5);
        return $memory;
    }
    
    //Read the contents of the template
    protected function get_contents($source_url)
    {
        $content = file_get_contents($source_url);
        return $content;
    }
    
    //Template additional transformation
    protected function place($content)
    {
        if (is_array($this->instances) && count($this->instances) >= 1) {
            $se = $re = array();
            foreach ($this->instances as $key => $value) {
                $se[] = $key;
                $re[] = $value;
            }
            $content = preg_replace($se, $re, $content);
        }
        if (is_array($this->binds) && count($this->binds) >= 1) {
            foreach ($this->binds as $key2 => $value2) {
                $content = preg_replace_callback($key2, $value2, $content);
            }
        }
        return $content;
    }
    
    //Classified parameters
    public function binds($abstract, $concrete)
    {
        if ($concrete instanceof \Closure) {
            $this->binds[$abstract] = $concrete;
        } else {
            $this->instances[$abstract] = $concrete;
        }
    }
    
    //Binding parameters
    public function bind($abstract, $concrete = ' ')
    {
        if (is_array($abstract)) {
            foreach ($abstract as $key => $value) {
                $this->binds($key, $value);
            }
        } else {
            $this->binds($abstract, $concrete);
        }
    }
    
    //The incoming variables
    public function assign($vars, $values = null)
    {
        if (is_array($vars)) {
            foreach ($vars as $key => $val) {
                if ($key != null)
                    $this->eztpl_vars[$key] = $val;
            }
        } else {
            if ($vars != null) {
                if ($values != null)
                    $this->eztpl_vars[$vars] = $values;
                else
                    $this->eztpl_vars['var'] = $vars;
            }
        }
    }
    //Escape a string
    protected static function _quote($val)
    {
        return preg_quote($val, '/');
    }
    //Variable substitution
    protected function parse_vars($content)
    {
        $vars = array(
            '$post' => '$_POST',
            '$get' => '$_GET',
            '$cookie' => '$_COOKIE',
            '$session' => '$_SESSION',
            '$files' => '$_FILES',
            '$server' => '$_SERVER',
            '$this' => '$this',
            '$cpu' => 'self::cpu()'
        );
        if (preg_match_all('/\$(\w+)\.(\w+)/', $content, $arr)) {
            foreach ($arr[2] as $key => $value) {
                $content = preg_replace('/\.' . $value . '/', '[\'' . $value . '\']', $content, 1);
            }
        }
        if (preg_match_all('/\$(\w+)/', $content, $vars_arr)) {
            foreach ($vars_arr[0] as $key => $value) {
                if (array_key_exists($value, $vars)) {
                    $rep = $vars[$value];
                } else {
                    $rep = $value;
                }
                $content = preg_replace('/' . self::_quote($value) . '/', $rep, $content, 1);
            }
        }
        $content = $this->parse_internal_var($content);
        return $content;
    }
    //Internal variable substitution
    protected function parse_internal_var($content)
    {
        if (preg_match_all('/\@(\w+)\.(\w+)/', $content, $arr)) {
            foreach ($arr[2] as $key => $value) {
                $content = preg_replace('/\.' . $value . '/', '[\'' . $value . '\']', $content, 1);
            }
        }
        if (preg_match_all('/\@(\w+)/', $content, $vars_arr)) {
            foreach ($vars_arr[0] as $key => $value) {
                $rep     = '$this->eztpl_vars[\'' . $vars_arr[1][$key] . '\']';
                $content = preg_replace('/' . self::_quote($value) . '/', $rep, $content, 1);
            }
        }
        return $content;
    }
    
    /**
     * create a multi-level file or a directory
     * $dir file or directory name
     * $false is set to true if it is a file
     * file or directory permissions
     */
    public function createdir($dir, $file = false, $mode = 0775)
    {
        
        $path = str_replace("\\", "/", $dir);
        if (is_dir($path) && $file == false)
            return true;
        if ($file) {
            if (is_file($path))
                return true;
            $temp_arr = explode('/', $path);
            array_pop($temp_arr);
            $path2 = implode('/', $temp_arr);
        }
        $mdir = isset($path2) ? $path2 : $path;
        if (!is_dir($mdir)) {
            @mkdir($mdir, $mode, true);
            @chmod($mdir, $mode);
        }
        
        if ($file) {
            $fh = @fopen($path, 'a');
            if ($fh) {
                fclose($fh);
                return true;
            }
        }
        if (is_dir($dir))
            return true;
    }
    
    //Written to the file
    protected function write_file($path, $content)
    {
        $compiled_url = $path;
        if ($this->createdir($compiled_url, true) == false && is_readable($compiled_url) == false) {
            $this->error('Warning: file generation fails, check permissions to' . $compiled_url);
        }
        $content = "<?php\r\n if(!defined('EZTPL')){\r\n die('Forbidden access');\r\n}\r\n?>\r\n" . $content;
        file_put_contents($path, $content);
    }
    
    //The output message
    protected function show_messages($message = null)
    {
        if ($this->errorurl != null) {
            header('Location: ' . $this->errorurl);
        } else {
            throw new \Exception($message);
        }
    }
	
    //error
    public function error($msg)
    {
        throw new \Exception($message);
        //die($msg);
    }
	
    /**
     * this class ends here
     */
}