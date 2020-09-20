<?php

namespace app\lune\util;

class CommonUtils {
    /**
     * 
     */
    public static function getRootURI() {
        if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
            $uri = 'https://';
        } else {
            $uri = 'http://';
        }
        $uri .= $_SERVER['HTTP_HOST'];
        return $uri;
    }

    /**
     *
     * 
     */
    public static function getRootPath() {
        return $_SERVER["DOCUMENT_ROOT"];
    }

    /*
     * 
     * 
     */
    public static function redirect($url) {
        header("Location: " . CommonUtils::getRootURI() . $url);
        exit;
    }

    public static function getFileContent($filePath) {
        if (file_exists($filePath)) {
            $fp = fopen($filePath, "r");
            $str = fread($fp, filesize($filePath));
            fclose($fp);
            return $str;
        }
    }

    public static function getCurrentFileContent() {
        $currentFilePath = $_SERVER["PHP_SELF"];
        return CommonUtils::getFileContent($currentFilePath);
    }

}
