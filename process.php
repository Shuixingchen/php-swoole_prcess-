<?php

class TaskWorker
{
    public $mpid = 0;
    public $works = [];
    public $max_precess = 4;
    public $new_index = 0;

    public function __construct()
    {
        try {
        	//设置进程名称
            swoole_set_process_name(sprintf('php-ps:%s', 'master'));
            //获取当前进程id,即主进程ID
            $this->mpid = posix_getpid();
            //创建进程
            $this->run();

            //回收僵尸进程，或者处理退出的进程
            $this->processWait();

        }catch (\Exception $e){
            die('ALL ERROR: '.$e->getMessage());
        }
    }

    public function run()
    {
        for ($i = 0; $i < $this->max_precess; $i++) {
            $this->new_index = $i;
            //创建进程
            $this->CreateProcess();
        }
    }

    public function CreateProcess($index = null)
    {
        if(is_null($index)) {
            $index = $this->new_index;
        }

        $process = new swoole_process(function(swoole_process $worker) use ($index) {
        	//创建子进程后执行的回调函数
        	//设置子进程的名称
            swoole_set_process_name(sprintf('php-ps:%s', $index));
            for ($j = 0; $j < 50; $j++) {
                $this->checkMpid($worker);
                $data = 'msg:'.$worker->read()."\r\n";
                $worker->write($data);
                sleep(1);
            }

        }, true);
        //执行创建子进程，并获取子进程id,放到进程池中
        $pid = $process->start();
        $process->write('parent message');//向这个子进程写管道
        $msg = $process->read();//读取管道内容
        echo $msg;
        $this->works[$index] = $pid;

        return $pid;
    }

    /**
     * 判断主进程是否关闭，如果关闭，则子进程也关闭
     * @param  [type] &$worker [description]
     * @return [type]          [description]
     */
    public function checkMpid(&$worker)
    {
    	//获取父进程id
    	$ppid = posix_getppid();
    	$pid = posix_getpid();
    	// 检测父进程是否kill
        if(!\swoole\process::kill($this->mpid, 0)){
        	 //这句提示,实际是看不到的.需要写到日志中
            echo "Master process {$ppid} exited,{$this->mpid} I also quit,current pid:{$pid}\n";
            $worker->exit(0);
        }
    }

    //重启退出的子进程
    public function rebootProcess($ret)
    {
        $pid = $ret['pid'];
        echo "ExitProcess: pid:".$pid.'exitcode:'.$ret['code'].PHP_EOL;
        $index = array_search($pid, $this->works);
        if(false !== $index){
            $index = intval($index);
            $new_pid = $this->CreateProcess($index);
            echo "rebootProcess: {$index} = {$new_pid} Done" . PHP_EOL;
            return;
        }
        throw new \Exception('rebootProcess Error: no pid' . PHP_EOL);
    }

    /**
     * 异步监听信号
     * @return [type] [description]
     */
    public function processWait()
    {
        //监听主进程信息
        swoole_process::signal(SIGTERM, function ($signo) {
            echo "收到kill退出信号,退出主进程";
        });
        swoole_process::signal(SIGINT, function ($signo) {
            echo "收到ctrl+c退出信号,退出主进程";
        });

        //监听子进程结束信号
        swoole_process::signal(SIGCHILD, function ($signo) {
            $ret = \swoole\process::wait();
                if ($ret) {
                    $this->rebootProcess($ret);
                }
        });
    }
}

new TaskWorker();




?>