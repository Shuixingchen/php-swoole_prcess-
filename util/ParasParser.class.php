<?php

class ParasParser
{
    public static $options = "hdrp:s:l:n:";
    public static $longopts = array("help", "daemon", "reload", "pid:", "log:");
    public static $help = <<<EOF

  帮助信息:
  Usage: /path/to/php daemon.php [options] -- [args...]

  -h [--help]        显示帮助信息
  -p [--pid]         指定pid文件位置(默认pid文件保存在/tmp目录)
  -s start           启动进程
  -s stop            停止进程
  -s restart         重启进程
  -l [--log]         log文件夹的位置
  -d [--daemon]      是否后台运行
  -r [--reload]      重新载入配置文件
  -n                 指定主进程名(默认为fc_daemon)

EOF;

    /**
     * 解析帮助参数
     * @param $opt
     */
    public static function params_h($opt)
    {
        if (empty($opt) || isset($opt["h"]) || isset($opt["help"])) {
            die(self::$help);
        }
    }

    /**
     * 解析运行模式参数
     * @param $opt
     * @return bool
     */
    public static function params_d($opt)
    {
        $daemon = false;
        if (isset($opt["d"]) || isset($opt["daemon"])) {
            $daemon = true;
        }
        Daemon::$daemon = $daemon;
    }

    /**
     * 重新载入配置文件
     * @param $opt
     */
    public static function params_r($opt)
    {
        if (isset($opt["r"]) || isset($opt["reload"])) {
            $pid = @file_get_contents(Daemon::$pid_file);
            if ($pid) {
                if (swoole_process::kill($pid, 0)) {
                    swoole_process::kill($pid, SIGUSR1);
                    LogUtil::record("对 {$pid} 发送了从新载入配置文件的信号");
                    exit;
                }
            }
            LogUtil::record("进程" . $pid . "不存在");
        }
    }

    /**
     * 解析pid参数
     * @param $opt
     */
    public static function params_p($opt)
    {
        //记录pid文件位置
        if (isset($opt["p"]) && $opt["p"]) {
            Daemon::$pid_file = $opt["p"] . "_pid";
        }
        //记录pid文件位置
        if (isset($opt["pid"]) && $opt["pid"]) {
            Daemon::$pid_file = $opt["pid"] . "_pid";
        }
        if (empty(Daemon::$pid_file)) {
            Daemon::$pid_file = '/tmp/' . Daemon::$process_name . "_pid";
        }
    }

    /**
     * 解析pid参数
     * @param $opt
     */
    public static function params_n($opt)
    {
        //设置主进程名
        if (isset($opt["n"]) && $opt["n"]) {
            Daemon::$process_name = $opt["n"];
        }
    }

    /**
     * 解析日志路径参数
     * @param $opt
     */
    public static function params_l($opt)
    {
        if (isset($opt["l"]) && $opt["l"]) {
            LogUtil::set_log_file($opt["l"]);
        }
        if (isset($opt["log"]) && $opt["log"]) {
            LogUtil::set_log_file($opt["log"]);
        }
    }

    /**
     * 解析启动模式参数
     * @param $opt
     */
    public static function params_s($opt)
    {
        //判断传入了s参数但是值无效，则提示错误
        if ((isset($opt["s"]) && !$opt["s"]) || (isset($opt["s"]) && !in_array($opt["s"], array("start", "stop", "restart")))) {
            LogUtil::record("Please run: path/to/php daemon.php -s [start|stop|restart]");
        }

        if (isset($opt["s"]) && in_array($opt["s"], array("start", "stop", "restart"))) {
            switch ($opt["s"]) {
                case "start":
                    Daemon::start();
                    break;
                case "stop":
                    Daemon::stop(true);
                    break;
                case "restart":
                    Daemon::restart();
                    break;
            }
        }
    }

}

















?>