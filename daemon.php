<?php

define('DAEMON_ROOT_PATH', dirname(__FILE__) . '/');
include_once DAEMON_ROOT_PATH . './util/LogUtil.class.php';
include_once DAEMON_ROOT_PATH . './util/ParasParser.class.php';
include_once DAEMON_ROOT_PATH . './util/DaemonWorker.class.php';


class Daemon
{
    static public $process_name = "php_swoole";  //进程名称
    static public $pid_file;                    //pid文件位置
    static public $log_path;                    //日志文件位置
    static public $taskParams;                  //获取task任务参数
    static public $taskType;                    //获取task任务的类型
    static public $tasksHandle;                 //获取任务的句柄
    static public $daemon = true;              //运行模式
    static public $pid;                        //pid
    static public $tasks = array();

    /**
     * 后台任务程序入口
     * @return void
     */
    public static function run()
    {
        $opt = getopt(ParasParser::$options, ParasParser::$longopts);
        ParasParser::params_h($opt);
        ParasParser::params_n($opt);
        ParasParser::params_d($opt);
        ParasParser::params_p($opt);
        ParasParser::params_l($opt);
        ParasParser::params_r($opt);
        ParasParser::params_s($opt);
    }

    /**
     * 启动
     * @return void
     */
    public static function start()
    {
        if (file_exists(self::$pid_file)) {
            die("Pid文件已存在!---{".self::$pid_file."}\n");
        }
        //运行模式，是否使当前进程蜕变为一个守护进程。
        if (self::$daemon) {
            swoole_process::daemon();
        }
        swoole_set_process_name(self::$process_name);
        LogUtil::record("启动成功");
        self::start_tasks();
    }

    /**
     * 停止
     * @param string $output 打印信息
     * @return void
     */
    public static function stop($output)
    {
        $pid = @file_get_contents(self::$pid_file);
        if ($pid) {
            if (swoole_process::kill($pid, 0)) {
                swoole_process::kill($pid, SIGTERM);
                LogUtil::record("进程" . $pid . "已结束");
            } else {
                @unlink(self::$pid_file);
                LogUtil::record("进程" . $pid . "不存在,删除pid文件");
            }
        } else {
            $output && LogUtil::record("需要停止的进程未启动");
        }
    }

    /**
     * 重启
     * @return void
     */
    public static function restart()
    {
        self::stop(true);
        sleep(1);
        self::start();
    }


    /**
     * 启动任务
     * @return void
     */
    public static function start_tasks()
    {
        self::register_signal();
        self::get_pid();
        self::write_pid();
        (new DaemonWorker())->create_process();

    }

    /**
     * 过去当前进程的pid
     */
    static private function get_pid()
    {
        if (!function_exists("posix_getpid")) {
            self::exit2p("Please install posix extension.");
        }
        self::$pid = posix_getpid();
    }

    /**
     * 写入当前进程的pid到pid文件
     */
    static private function write_pid()
    {
        file_put_contents(self::$pid_file, self::$pid);
    }

    /**
     * 注册信号处理器
     * @return void
     */
    private static function register_signal()
    {
        //kill无参数命令会收到这个信号
        swoole_process::signal(SIGTERM, function ($signo) {
            self::exit2p("收到kill退出信号,退出主进程");
        });
        swoole_process::signal(SIGINT, function ($signo) {
            self::exit2p("收到ctrl+c退出信号,退出主进程");
        });
       
        // 监听信号，重启挂掉的子进程(kill，执行完都会收到)
        swoole_process::signal(SIGCHLD, function () {
            while($ret = swoole_process::wait()){
                $pid = $ret['pid'];
                if (isset(self::$tasks[$pid])) {
                    $task = self::$tasks[$pid];
                    $task['process']->close();
                    unset(self::$tasks[$pid]);
                    echo 'child process '.$pid.' has shoudown'.PHP_EOL;
                    if ($ret['code']) {
                        (new DaemonWorker())->create_child_process($task['name']);
                    }
                }
            }
        });
        
    }

    /**
     * 退出进程
     * @param string $msg 退出提示消息
     * @return void
     */
    private static function exit2p($msg)
    {
        @unlink(self::$pid_file);
        LogUtil::record($msg);
        exit();
    }

}

// 启动
Daemon::run();

























?>