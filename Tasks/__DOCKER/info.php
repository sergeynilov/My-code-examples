
nano /var/www/html/info.php

<table>
    <tr>
        <td>
            <?php
            echo calcDiskSize("/");
            ?>
        </td>
    </tr>

    <tr>
        <td>
            <?php
            $errLvl = error_reporting();
            echo 'error_reporting::' . error_reporting() . "<br>\n<hr>";
            for ($i = 0; $i < 15;  $i++) {
                print FriendlyErrorType($errLvl & pow(2, $i)) . "<br>\n";
            }

            function FriendlyErrorType($type)
            {
                switch($type) {
                    case E_ERROR: // 1 //
                        return 'E_ERROR';
                    case E_WARNING: // 2 //
                        return 'E_WARNING';
                    case E_PARSE: // 4 //
                        return 'E_PARSE';
                    case E_NOTICE: // 8 //
                        return 'E_NOTICE';
                    case E_CORE_ERROR: // 16 //
                        return 'E_CORE_ERROR';
                    case E_CORE_WARNING: // 32 //
                        return 'E_CORE_WARNING';
                    case E_COMPILE_ERROR: // 64 //
                        return 'E_COMPILE_ERROR';
                    case E_COMPILE_WARNING: // 128 //
                        return 'E_COMPILE_WARNING';
                    case E_USER_ERROR: // 256 //
                        return 'E_USER_ERROR';
                    case E_USER_WARNING: // 512 //
                        return 'E_USER_WARNING';
                    case E_USER_NOTICE: // 1024 //
                        return 'E_USER_NOTICE';
                    case E_STRICT: // 2048 //
                        return 'E_STRICT';
                    case E_RECOVERABLE_ERROR: // 4096 //
                        return 'E_RECOVERABLE_ERROR';
                    case E_DEPRECATED: // 8192 //
                        return 'E_DEPRECATED';
                    case E_USER_DEPRECATED: // 16384 //
                        return 'E_USER_DEPRECATED';
                }
                return "";
            }

            echo '<a href="https://www.php.net/manual/ru/errorfunc.constants.php">errorfunc.constants.php<a>' . "<br>\n";

            ?>
    <tr>
        <td>

            <?php
            phpinfo();
            function calcDiskSize($disk = "/"): string
            {
                $diskTotalSpace = disk_total_space($disk);
                $info = 'Disk space at "'.$disk.'" : ' . getNiceFileSize($diskTotalSpace);
                $diskFreeSpace = disk_free_space("/"); // 300 10 = 10 /300 *100
                $info .= ', ' . getNiceFileSize($diskFreeSpace) . ' free, ';

                $free_percent = $diskFreeSpace / $diskTotalSpace * 100;
                //        echo '<pre>base_path()::'.print_r(base_path(),true).'</pre>';
                //        $ds = folderSize( base_path() );
                //        $info.= '. Application takes : ' . $this->getNiceFileSize($ds);
                return $info . '(' . round($free_percent, 2) . ' % free )';
            }

            function getNiceFileSize($bytes, $binaryPrefix = true)
            {
                if ($binaryPrefix) {
                    $unit = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
                    if ($bytes == 0) {
                        return '0 ' . $unit[0];
                    }

                    return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2) . ' ' . (isset($unit[$i]) ? $unit[$i] : 'B');
                } else {
                    $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
                    if ($bytes == 0) {
                        return '0 ' . $unit[0];
                    }

                    return @round($bytes / pow(1000, ($i = floor(log($bytes, 1000)))), 2) . ' ' . (isset($unit[$i]) ? $unit[$i] : 'B');
                }
            }

            ?>

        </td>
    </tr>

</table>
