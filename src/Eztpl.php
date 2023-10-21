<?php

/*
 * @Author: lovefc 
 * Version: 1.7.0
 * @Date: 2017-8-29 09:30
 * @Last Modified by: lovefc
 * @Last Modified time: 2019-09-23 00:08:28
 * @Last Modified time: 2021-02-24 23:28:45 (修改内部编译目录的问题)
 * @Last Modified time: 2023-10-21 13:45:16 (优化部分细节) 
 */

namespace lovefc;

define('EZTPL', true);

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
            $class = __CLASS__;
            $ObjName = empty($ObjName) ? 'default' : $ObjName;
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


    //创建方法
    public function __call($method, $args)
    {
        $perfix = substr($method, 0, 3);
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
        $this->Charset = isset($setting['Charset']) ? $setting['Charset'] : null;
        $this->TplBegin = isset($setting['TplBegin']) ? $setting['TplBegin'] : '{(';
        $this->TplEnd = isset($setting['TplEnd']) ? $setting['TplEnd'] : ')}';
        $this->Dir = isset($setting['Dir']) ? $setting['Dir'] : __DIR__ . '/templates';
        $this->TempDir = isset($setting['TempDir']) ? $setting['TempDir'] : __DIR__ . '/templates_c';
        $this->Suffix = isset($setting['Suffix']) ? $setting['Suffix'] : 'tpl';
        $this->ErrorUrl = isset($setting['ErrorUrl']) ? $setting['ErrorUrl'] : '';
        $this->DirName = isset($setting['DirName']) ? $setting['DirName'] : '';
        $this->TempOpen = isset($setting['TempOpen']) ? $setting['TempOpen'] : false;
        $this->IncludeOpen = isset($setting['IncludeOpen']) ? $setting['IncludeOpen'] : true;
        return $this;
    }

    //获取缓存文件路径
    protected function get_compiledfile_url($file_name)
    {
        return (!empty($this->DirName)) ? $this->TempDir . '/' . $this->DirName . '/' . $file_name . '.php' : $this->TempDir . '/' . $file_name . '.php';
    }

    //获取模版文件路径
    protected function get_sourcefile_url($file_name)
    {
        return (!empty($this->DirName)) ? $this->Dir . '/' . $this->DirName . '/' . $file_name . '.' . $this->Suffix : $this->Dir . '/' . $file_name . '.' . $this->Suffix;
    }

    //判断是否需要编译
    protected function is_compiled($source_url, $compiled_url)
    {
        if (!is_readable($source_url)) {
            $this->show_messages('Template file not readable to ' . $source_url);
        }
        if ($this->TempOpen || !is_file($compiled_url)) {
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
        $lovefc_left = self::_quote($this->TplBegin);
        $lovefc_right = self::_quote($this->TplEnd);
        $content = $this->place($this->get_contents($source_url));
        if (strpos($content, $this->TplBegin . 'include') !== false) {
            $include_regular = '/' . $lovefc_left . 'include\s+file\s*=\s*["](.+?)["]' . $lovefc_right . '/i';
            if (preg_match_all($include_regular, $content, $include_arr)) {
                $include_arr[1] = array_unique($include_arr[1]);
                foreach ($include_arr[1] as $key => $value) {
                    $str = $this->TplBegin . 'include file="' . $value . '"' . $this->TplEnd;
                    $source = $this->get_sourcefile_url($value);
                    if (is_file($source)) {
                        $compiled = $this->get_compiledfile_url($value);
                    } else {
                        $source = $this->get_sourcefile_url($value);
                        if (is_file($source)) {
                            $compiled = $this->get_compiledfile_url($value);
                        } else {
                            $compiled = null;
                        }
                    }
                    if ($compiled) {
                        if ($this->IncludeOpen) {
                            $regular = '<?php if($this->includes(\'' . $source . '\',' . '\'' . $compiled . '\')){ require(\'' . $compiled . '\'); } ?>';
                        } else {
                            $this->compile($source, $compiled);
                            $regular = "<?php\r\nrequire('{$compiled}');\r\n?>";
                        }
                    } else {
                        if (is_file($value)) {
                            $regular = "<?php\r\nrequire('{$value}');\r\n?>";
                        } else {
                            $regular = null;
                        }
                    }
                    $content = str_ireplace($str, $regular, $content);
                }
            }
        }
        $else_end_regular = $this->TplBegin . 'else' . $this->TplEnd;
        if (strpos($content, $else_end_regular)) {
            $else_rep = "<?php\r\n}else{\r\n?>";
            $content = str_ireplace($else_end_regular, $else_rep, $content);
        }
        $x_end_regular = '/' . $lovefc_left . '\/(if|for|foreach|while|end)' . $lovefc_right . '/i';
        if (preg_match_all($x_end_regular, $content, $end_arr)) {
            $end_arr[0] = array_unique($end_arr[0]);
            foreach ($end_arr[0] as $key => $value) {
                $content = str_replace($value, '<?php } ?>', $content);
            }
        }
        if (strpos($content, $this->TplBegin . 'if') !== false) {
            $if_regular = '/' . $lovefc_left . 'if (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($if_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php if(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->TplBegin . 'elseif') !== false) {
            $elseif_regular = '/' . $lovefc_left . 'elseif (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($elseif_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php }elseif(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->TplBegin . 'foreach') !== false) {
            $foreach_regular = '/' . $lovefc_left . 'foreach (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($foreach_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    if (strpos($value, ' as') === false) {
                        $value .= ' as $key=>$value';
                    }
                    $values = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php foreach(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->TplBegin . 'for') !== false) {
            $for_regular = '/' . $lovefc_left . 'for (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($for_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php for(' . $values . '){ ?>', $content);
                }
            }
        }
        if (strpos($content, $this->TplBegin . 'while') !== false) {
            $while_regular = '/' . $lovefc_left . 'while (.*)' . $lovefc_right . '/isU';
            if (preg_match_all($while_regular, $content, $vars_arr)) {
                foreach ($vars_arr[1] as $key => $value) {
                    $values = $this->parse_vars($value);
                    $content = str_replace($vars_arr[0][$key], '<?php while(' . $values . '){ ?>', $content);
                }
            }
        }
        $assign_regular = '/' . $lovefc_left . '(((\$|\@)[\w\.\[\]\$]+)=\s*([\'"].+?[\'"]|.+?))' . $lovefc_right . '/';
        if (preg_match_all($assign_regular, $content, $arr)) {
            foreach ($arr[0] as $key => $value) {
                $rep = '<?php ' . $this->parse_vars($arr[1][$key]) . '; ?>';
                $content = str_replace($value, $rep, $content);
            }
        }
        $varc_regular = '/' . $lovefc_left . '\!(.*)' . $lovefc_right . '/isU';
        if (preg_match_all($varc_regular, $content, $arr)) {
            foreach ($arr[1] as $key => $value) {
                $values = $this->parse_vars($value);
                $content = str_replace($arr[0][$key], '<?php ' . $values . ';?>', $content);
            }
        }
        $var_regular = '/' . $lovefc_left . '(.*)' . $lovefc_right . '/U';
        if (preg_match_all($var_regular, $content, $arr)) {
            foreach ($arr[1] as $key => $value) {
                $values = $this->parse_vars($value);
                $content = str_replace($arr[0][$key], '<?php echo ' . $values . ';?>', $content);
            }
        }
        return $content;
    }

    //模版输出
    public function display($file_name)
    {
        $source_url = $this->get_sourcefile_url($file_name);
        $compiled_url = $this->get_compiledfile_url($file_name);
        $this->compile($source_url, $compiled_url);
        $this->Charset && header('Content-Type:text/html;charset=' . $this->Charset);
        require($compiled_url);
    }

    //结束一个句柄
    public function end($ObjName = 'default')
    {
        self::$eztpl[$ObjName] = null;
        unset(self::$eztpl[$ObjName]);
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
    public function bind($abstract, $concrete)
    {
        if ($concrete instanceof \Closure) {
            $this->binds[$abstract] = $concrete;
        } else {
            $this->instances[$abstract] = $concrete;
        }
    }

    //参数绑定
    public function binds($abstract, $concrete = ' ')
    {
        if (is_array($abstract)) {
            foreach ($abstract as $key => $value) {
                $this->bind($key, $value);
            }
        } else {
            $this->bind($abstract, $concrete);
        }
    }

    //模版变量赋值
    public function assign($vars, $values = null)
    {
        if (is_array($vars)) {
            foreach ($vars as $key => $val) {
                if ($key != null) {
                    $this->eztpl_vars[$key] = $val;
                }
            }
        } else {
            if ($vars != null) {
                if ($values != null) {
                    $this->eztpl_vars[$vars] = $values;
                } else {
                    $this->eztpl_vars['var'] = $vars;
                }
            }
        }
    }

    //转义
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
                $rep = '$this->eztpl_vars[\'' . $vars_arr[1][$key] . '\']';
                $content = preg_replace('/' . self::_quote($value) . '/', $rep, $content, 1);
            }
        }

        return $content;
    }

    /**
     * 创建一个文件或者目录
     * 
     * @param $dir 目录名或者文件名
     * @param $mode 文件的权限
     * @return bool
     */
    public function create($path, $mode = 0775)
    {
        if (empty($path)) return false;
        $path = str_replace("\\", "/", $path);
        list($dirname, $basename, $filename) = array_values(pathinfo($path));
        if (file_exists($path)) {
            $fileperms = substr(base_convert(fileperms($path), 10, 8), 1);
            if ($fileperms != $mode) {
                return @chmod($path, $mode);
            }
            return true;
        }

        $dir = $dirname . '/' . $basename;
        return @mkdir($dir, $mode, true);
    }

    //写入缓存
    protected function write_file($compiled_url, $content)
    {
        $this->create(dirname($compiled_url));
        file_put_contents($compiled_url, '');
        if (is_readable($compiled_url) == false) {
            $this->error('Warning: file generation fails, check permissions to' . $compiled_url);
        }
        $content = "<?php\r\n if(!defined('EZTPL')){\r\n die('Forbidden access');\r\n}\r\n?>\r\n" . $content;
        file_put_contents($compiled_url, $content, LOCK_EX);
    }

    //消息输出
    protected function show_messages($message = null)
    {
        if ($this->ErrorUrl != null) {
            header('Location: ' . $this->ErrorUrl);
        } else {
            $this->error($message);
        }
    }

    //错误输出
    public function error($message)
    {
        throw new \Exception($message);
    }
    /**
     * this class ends here
     */
}
