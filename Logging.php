<?php


namespace Logging;


class Logging
{
    public function log($log_filename, $level, $message, $file)
    {
        $log_time = date('Y-m-d h:i:sa');
        $error_filename = $log_filename .'.error';
        $info_filename = $log_filename .'.info';

        switch ($level){
            case 'info':
                $this->write_log(
                    $info_filename,
                    $log_time,
                    $file,
                    $level,
                    $message
                    );
            case 'error':
                $this->write_log(
                    $error_filename,
                    $log_time,
                    $file,
                    $level,
                    $message
                );
        }
    }

    public function write_log($filename, $log_time, $file, $level, $message)
    {
        if (!file_exists($filename)){
            mkdir($filename, 0777, true);
        }
        $log_file_data = '['.$log_time.'] ['.$file.'] ['.$level.'] '.$message.PHP_EOL;
        file_put_contents($filename, $log_file_data, FILE_APPEND);
    }
}