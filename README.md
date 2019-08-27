# php-swoole_prcess-
php基于swoole_prcess的多进程任务管理工具

只能在cli环境中执行，启动 php daemon.php -s -start 

添加task任务，在配置文件conf.php设置任务名称，进程数量，在tasks创建一个task类即可。

可以加到tp框架中进行改造，每个子进程都会加载框架，执行对应的task控制器。
