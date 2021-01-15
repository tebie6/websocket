<?php

namespace tebie6\swoole\model;

class OUtils
{

    /**
     * 生成文件
     */
    public static function makeFile($content = '', $path = '', $format = 0)
    {
        if (empty($path) || empty($content)) return false;

        //由于以cli模式创建的文件在cgi模式下没有写入权限，所以将cli与cgi日志拆分开
        $path = explode('.',$path);
        $num = count($path)-2;
        if(self::is_cli()){
            $path[$num] = $path[$num].'_cli';
        } else {
            $path[$num] = $path[$num].'_cgi';
        }
        $path = implode('.',$path);

        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (is_array($content)) {
            $content = var_export($content, true);
        }

        if ($format)
            $content = '------------' . date('Y-m-d H:i:s') . '-----------' . chr(10) . $content . chr(10);

        $fp = fopen($path, 'ab');
        $r = false;
        if (flock($fp, LOCK_EX)) {
            $r = fwrite($fp, $content);
            fflush($fp);
            flock($fp, LOCK_UN);
        }

        fclose($fp);
        return $r;
    }

    /**
     * 判断当前的运行环境是否是cli模式
     * @return bool
     */
    public static function is_cli(){
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }

    /**
     * 时间的显示规则
     * @param string $time
     * @return string
     */
    public static function timeFormat($time = '')
    {
        if (!$time) {
            return false;
        }

        $f = array(
            '31536000' => '年',
            '2592000' => '个月',
            '604800' => '周',
            '86400' => '天',
            '3600' => '小时',
            '60' => '分钟',
            '1' => '秒'
        );
        foreach ($f as $k => $v) {
            if (0 != $c = floor($time / (int)$k)) {
                return $c . $v;
            }
        }
    }

    /**
     * 时间的显示规则
     * @param string $time
     * @return bool|string
     */
    public static function timeFormat2($time = '')
    {
        $second = time() - $time;
        if ($second < 60) {
            $mytime = "刚刚";
        } else if ($second >= 60 && $second < 3600) {
            $mytime = floor($second / 60) . "分钟前";
        } else if ($second >= 3600 && $second < 86400) {
            $mytime = floor($second / (3600)) . "小时前";
        } else if ($second >= 86400 && $second < 86400 * 2) {
            $mytime = "昨天";
        } else if ($second >= 86400 * 2) {
            $mytime = date("Y-m-d H:i", $time);
        }
        return $mytime;
    }

}