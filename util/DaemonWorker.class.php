<?php

class DaemonWorker
{

    /**
     * 根据配置文件创建进程
     * @return void
     */
    public function create_process()
    {
        $tasks = include DAEMON_ROOT_PATH . './conf/tasks.php';
        foreach ($tasks as $taskName => $conf) {
            if (!isset($conf['process_num']) || !$conf['enable']) {
                continue;
            }
            $worker_num = $conf['process_num'];
            for ($i = 0; $i < $worker_num; $i++) {
                $process = new swoole_process(array($this, "run"), true);
                $pid = $process->start();
                if ($pid) {
                    $data = array(
                        'taskName' => $taskName,
                        'config' => $conf['config']
                    );
                    $process->write(json_encode($data));
                    Daemon::$tasks[$pid] = array(
                        'name' => $taskName,
                        'process' => $process
                    );
                }
                echo 'child output:'.$process->read();
            }
        }
    }

    /**
     * 子进程退出后调用该方法重新创建
     * @param string $taskName 任务名
     * @return void
     */
    public function create_child_process($taskName)
    {
        $tasks = include DAEMON_ROOT_PATH . './conf/tasks.php';
        $process = new swoole_process(array($this, "run"), true);
        $pid = $process->start();
        if ($pid) {
            $data = array(
                'taskName' => $taskName,
                'config' => $tasks[$taskName]['config']
            );
            $process->write(json_encode($data));
            Daemon::$tasks[$pid] = array(
                'name' => $taskName,
                'process' => $process
            );
        }
    }

    //子进程
    public function run($worker)
    {

        $data = $worker->read();
        $data = json_decode($data, true);
        $config = $data['config'];
        $sleep_time = $config['sleep'] ? : 1;
        $taskName = ucfirst($data['taskName']);
        $worker->name(Daemon::$process_name . "_worker_" . $data['taskName']);

        require_once(DAEMON_ROOT_PATH . '/tasks/' . $taskName . 'Task.class.php');
        $class_name = $taskName . 'Task';

        $task = new $class_name();
        //每个子进程都一直执行这个task任务
        while(true) {
            //每秒钟执行一下任务，并且检测父进程是否停止
            $this->check_pid($worker);
            $task->invoke();
            sleep($sleep_time);
        }

        

        // define('IN_TPC', true);
        // define('APP_DEBUG_ENABLE', false);
        // define('APP_NAME', 'Front');
        // define('BIND_MODULE', 'Home');
        // define('BIND_CONTROLLER', 'Task');
        // define('BIND_ACTION', 'invoke');

        // define('DAEMON_TASK_NAME', $data['taskName']);

        // $GLOBALS['DAEMON_TASK_CONFIG'] = $data['config'];
        // $GLOBALS['DAEMON_TASK_WORKER'] = $worker;

        // require DAEMON_ROOT_PATH . '../Public/global.php';
    }

    public function check_pid(&$worker)
    {
        // 检测父进程是否kill
        if(!\swoole\process::kill(Daemon::$pid, 0)){
            //打印不出来，因为主进程已退出，读取不到这个管道的内容
            echo "Master process {$ppid} exited,{$this->mpid} I also quit,current pid:{$pid}\n";
            $worker->exit(0);
        }
    }



}












?>