<?php
/**
 * 任务配置文件
 */
return array(
    
    'email' => array(
        'enable' => true,//进程是否开启
        'process_num' => 3,//进程数
        'config' => array(
        	'sleep'=>5, //多久执行任务一次
        )
    ),
    
);











?>