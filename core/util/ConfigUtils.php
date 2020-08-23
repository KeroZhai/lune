<?php

namespace app\core\util;

use app\core\exception\ConfigFileNotFoundException;

class ConfigUtils {

    public static $CONFIG_PATH = APP_ROOT."/conf/config.ini";
    public static $config;

    public static function getConfig() {
        if (!isset(ConfigUtils::$config)) {
            if (file_exists(ConfigUtils::$CONFIG_PATH)) {
                ConfigUtils::$config = parse_ini_file(ConfigUtils::$CONFIG_PATH, true);
            } else {
                throw new ConfigFileNotFoundException();
            }
        }
        return ConfigUtils::$config;
    }

    public static function getSection(String $sectionName) {
        $config = ConfigUtils::getConfig();
        if (isset($config[$sectionName])) {
            return $config[$sectionName];
        }
    }

    public static function getValue(String $sectionName, String $key, String $default=null) {
        $section = ConfigUtils::getSection($sectionName);
        if (isset($section[$key])) {
            return $section[$key];
        }
        return $default;
    }

    public static function getBool(String $sectionName, String $key, bool $default=null) {
        $value = ConfigUtils::getValue($sectionName, $key);
        if ($value === "1") {
            return true;
        } else if ($value === "") {
            return false;
        } else {
            throw new \Exception("Cannot parse string $value to bool");
        }
        return $default;
    }

    public static function getInt(String $sectionName, String $key, int $default=null) {
        if ($value = ConfigUtils::getValue($sectionName, $key)) {
           return intval($value);
        }
        return $default;
    }

    public static function getArray(string $sectionName, string $key, array $default=null) {
        if ($value = ConfigUtils::getValue($sectionName, $key)) {
            return StringUtils::split(",", $value);
        }
        return $default;
    }

}
