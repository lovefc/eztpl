<?php
namespace eztpl;
define('EZTPL', true);

/**
 * php单文件模版引擎 eztpl
 * 作者: lovefc
 * 版本: 1.6.8
 * 最后更新: 2017/6/7 23:17:30
 * Copyright to author lovefc all
 */

class Eztpl
{
    public $eztpl_vars = array();
    
    public static $eztpl;
    
    protected $binds;
    
    protected $instances;
    
    //获取单例
    public static function instance($ObjName = 'default')
    {
        if (!isset(self::$eztpl[$ObjName]) || empty($ObjName)) {
            $class                 = __CLASS__;
            $ObjName               = empty($ObjName) ? 'default' : $ObjName;
            self::$eztpl[$ObjName] = new $class;
            return self::$eztpl[$ObjName];
        } else {
            return self::$eztpl[$ObjName];
        }
    }
    
    //用于获取私有属性
    public function __get($name)
    {
        return $this->$name = isset($this->$name) ? $this->$name : '';
    }
    
    //动态创建方法来给类变量赋值，例如$obj->setdirs('./templates');
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
    
    //初始化设置 (array)
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
    
    //获取缓存文件路径
    protected function get_compiledfile_url($file_name)
    {
        return (!empty($this->tempdirname)) ? $this->tempdirs . '/' . $this->tempdirname . '/' . $file_name . '.php' : $this->tempdirs . '/' . $file_name . '.php';
    }
    //获取模版文件路径
    protected function get_sourcefile_url($file_name)
    {
        
        return (!empty($this->tempdirname)) ? $this->dirs . '/' . $this->tempdirname . '/' . $file_name . '.' . $this->suffix : $this->dirs . '/' . $file_name . '.' . $this->suffix;
    }
    //判断是否需要编译
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
    //编译并写入到缓存
    protected function compile($source_url, $compiled_url)
    {
        if ($this->is_compiled($source_url, $compiled_url)) {
            $this->write_file($compiled_url, $this->compileds($source_url));
        }
    }
    //模版文件引入
    protected function includes($source_url, $compiled_url)
    {
        $this->compile($source_url, $compiled_url);
        return $compiled_url;
    }
    //编译过程
    protected function compileds($source_url)
    {
        $lovefc_left  = self::_quote($this->tplbegin);
        $lovefc_right = self::_quote($this->tplend);
        $content      = $this->place(file_get_contents($source_url));
        if (strpos($content, $this->tplbegin . 'include') !== false) {
            $include_regular = '/' . $lovefc_left . 'include\s+file\s*=\s*["](.+?)["]' . $lovefc_right . '/i';
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
                            $regular = '<?php if($this->includes(\'' . $source . '\',' . '\'' . $compiled . '\')){ require(\'' . $compiled . '\'); } ?>';
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
        $x_end_regular = '/' . $lovefc_left . '\/(if|for|foreach|while|end)' . $lovefc_right . '/i';
        if (preg_match_all($x_end_regular, $content, $end_arr)) {
            $end_arr[0] = array_unique($end_arr[0]);
            foreach ($end_arr[0] as $key => $value) {
                $content = str_replace($value, '<?php } ?>', $content);
            }
        }
        if (strpos($content, $this->tplbegin . 'if') !== false) {
            $if_regular = '/' . $lovefc_left . 'if (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($if_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php if(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->tplbegin . 'elseif') !== false) {
            $elseif_regular = '/' . $lovefc_left . 'elseif (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($elseif_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php }elseif(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->tplbegin . 'foreach') !== false) {
            $foreach_regular = '/' . $lovefc_left . 'foreach (.*)' . $lovefc_right . '/isU';
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
            $for_regular = '/' . $lovefc_left . 'for (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($for_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php for(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->tplbegin . 'while') !== false) {
            $while_regular = '/' . $lovefc_left . 'while (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($while_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values  = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php while(' . $values . '){ ?>', $content);
                }
            }
        }
        $assign_regular = '/' . $lovefc_left . '(((\$|\@)[\w\.\[\]\$]+)=\s*([\'"].+?[\'"]|.+?))' . $lovefc_right . '/';
        if (preg_match_all($assign_regular, $content, $arr)) {
            foreach ($arr[0] as $key => $value) {
                $rep     = '<?php ' . $this->parse_vars($arr[1][$key]) . '; ?>';
                $content = str_replace($value, $rep, $content);
            }
        }
        $varc_regular = '/' . $lovefc_left . '\!(.*)' . $lovefc_right . '/isU';
        if (preg_match_all($varc_regular, $content, $arr)) {
            foreach ($arr[1] as $key => $value) {
                $values  = $this->parse_vars($value);
                $content = str_replace($arr[0][$key], '<?php ' . $values . ';?>', $content);
            }
        }
        $var_regular = '/' . $lovefc_left . '(.*)' . $lovefc_right . '/U';
        if (preg_match_all($var_regular, $content, $arr)) {
            foreach ($arr[1] as $key => $value) {
                $values  = $this->parse_vars($value);
                $content = str_replace($arr[0][$key], '<?php echo ' . $values . ';?>', $content);
            }
        }
        return $content;
    }
    //模版输出
    public function display($file_name)
    {
        $source_url   = $this->get_sourcefile_url($file_name);
        $compiled_url = $this->get_compiledfile_url($file_name);
        $this->compile($source_url, $compiled_url);
        $this->charset && header('Content-Type:text/html;charset=' . $this->charset);
        require($compiled_url);
    }
    //结束一个句柄
    public function end()
    {
        self::$lovefc = null;
        unset(self::$lovefc);
    }
    
    //获取cpu
    public static function cpu()
    {
        $memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 5);
        return $memory;
    }
    
    //获取模版内容
    protected function get_contents($source_url)
    {
        $content = file_get_contents($source_url);
        return $content;
    }
    
    //自定义的正则编译
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
    
    //参数绑定判断
    public function binds($abstract, $concrete)
    {
        if ($concrete instanceof \Closure) {
            $this->binds[$abstract] = $concrete;
        } else {
            $this->instances[$abstract] = $concrete;
        }
    }
    
    //参数绑定
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
    
    //模版变量赋值
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
    
    //正则字符串转义
    protected static function _quote($val)
    {
        return preg_quote($val, '/');
    }
    
    //变量解析
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
                $content = preg_replace('/\.' . $value . '/', '[\'' . $value . '\']', $content);
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
    
    //内部变量解析
    protected function parse_internal_var($content)
    {
        if (preg_match_all('/\@(\w+)\.(\w+)/', $content, $arr)) {
            foreach ($arr[2] as $key => $value) {
                $content = preg_replace('/\.' . $value . '/', '[\'' . $value . '\']', $content);
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
    
    //创建目录或者文件
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
    
    //写入缓存
    protected function write_file($path, $content)
    {
        $compiled_url = $path;
        if ($this->createdir($compiled_url, true) == false && is_readable($compiled_url) == false) {
            $this->error('Warning: file generation fails, check permissions to' . $compiled_url);
        }
        $content = "<?php\r\n if(!defined('EZTPL')){\r\n die('Forbidden access');\r\n}\r\n?>\r\n" . $content;
        file_put_contents($path, $content);
    }
    
    //消息输出或者跳转
    protected function show_messages($message = null)
    {
        if ($this->errorurl != null) {
            header('Location: ' . $this->errorurl);
        } else {
            $this->error($message);
        }
    }
    
    //错误输出
    public function error($message)
    {
        throw new \Exception($message);
    }
}