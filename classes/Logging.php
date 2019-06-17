<?php


namespace Logging;

use App\App;

class Logging
{
    protected $info_filename;
    protected $error_filename;

    public function __construct()
    {
        $log_filename = App::get('config')['log_filename'];
        $this->info_filename = "{$log_filename}log.info";
        $this->error_filename = "{$log_filename}log.error";

        if (!file_exists($log_filename)) {
            mkdir($log_filename);
        }
    }

    public function log($level, $message, $file)
    {
        $log_time = date('Y-m-d h:i:sa');

        if ($level == 'INFO') {
            $this->writeLog(
                $this->info_filename,
                $log_time,
                $file,
                $level,
                $message
            );
        }

        if ($level == 'ERROR') {
            $this->writeLog(
                $this->error_filename,
                $log_time,
                $file,
                $level,
                $message
            );
        }
    }

    protected function writeLog($filename, $log_time, $file, $level, $message)
    {
        $log_file_data = "[{$log_time}] [{$file}] [{$level}] {$message}\n";
        file_put_contents($filename, $log_file_data, FILE_APPEND);
    }
}
