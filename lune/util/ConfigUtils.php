<?php

namespace app\lune\util;

use app\lune\exception\ConfigFileNotFoundException;
use app\lune\exception\NoSuchSectionException;

class ConfigUtils {

    public static $CONFIG_PATH = APP_ROOT."/conf/config.ini";
    public static $config;

    public static function getConfig() {
        if (ConfigUtils::$config === null) {
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
        if ($config[$sectionName] !== null) {
            return $config[$sectionName];
        } else {
            throw new NoSuchSectionException($sectionName);
        }
    }

    public static function getValue(string $sectionName, string $key, $default=null) {
        $section = ConfigUtils::getSection($sectionName);
        if ($section[$key] !== null) {
            return $section[$key];
        }
        return $default;
    }

    public static function getBool(string $sectionName, string $key, bool $default=null) {
        $value = ConfigUtils::getValue($sectionName, $key);
        if ($value === null) {
            return $default;
        } else if ($value === "1" || $value === true) {
            return true;
        } else if ($value === "" || $value === false) {
            return false;
        } else {
            throw new \Exception("Cannot parse string \"$value\" to bool");
        }
    }

    public static function getInt(string $sectionName, string $key, int $default=null) {
        $value = ConfigUtils::getValue($sectionName, $key, $default);
        return $value === null ? $default : intval($value);
    }

    public static function getArray(string $sectionName, string $key, array $default=null) {
        $value = ConfigUtils::getValue($sectionName, $key);
        return $value === null ? $default : StringUtils::split(",", $value);
    }

}
