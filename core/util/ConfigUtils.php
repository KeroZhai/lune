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

    public static function getSection(string $sectionName) {
        $config = ConfigUtils::getConfig();
        if (isset($config[$sectionName])) {
            return $config[$sectionName];
        }
    }

    public static function getValue(string $sectionName, string $key, $default=null) {
        $section = ConfigUtils::getSection($sectionName);
        if (isset($section[$key])) {
            return $section[$key];
        }
        return $default;
    }

    public static function getBool(string $sectionName, string $key, bool $default=null) {
        $value = ConfigUtils::getValue($sectionName, $key, $default);
        if ($value === "1" || $value === true) {
            return true;
        } else if ($value === "" || $value === false) {
            return false;
        } else {
            throw new \Exception("Cannot parse string \"$value\" to bool");
        }
    }

    public static function getInt(string $sectionName, string $key, int $default=null) {
        $value = ConfigUtils::getValue($sectionName, $key, $default);
        return intval($value);
    }

    public static function getArray(string $sectionName, string $key, array $default=null) {
        $value = ConfigUtils::getValue($sectionName, $key, $default);
        return StringUtils::split(",", $value);
    }

}
