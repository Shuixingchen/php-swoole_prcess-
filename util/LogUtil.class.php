<?php


class LogUtil
{
    private static $log_file;

    public static function set_log_file($log_file)
    {
        self::$log_file = $log_file;
    }

    public static function record($message)
    {
        if (empty(self::$log_file)) {
            self::$log_file = DAEMON_ROOT_PATH . Daemon::$process_name . '/';
        }
        if (!is_dir(self::$log_file)) {
            mkdir(self::$log_file, 777, true);
        }
        $now = date("H:i:s");
        if (Daemon::$daemon) {
            $destination = self::$log_file . "log_" . date('Y-m-d') . ".log";
            error_log("{$now} : {$message}\r\n", 3, $destination, '');
        }
        echo "{$now} : {$message}\r\n";
    }
}


?>