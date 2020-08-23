<?php

namespace app\core\repository;

use app\core\util\ConfigUtils;

class MysqlDB {
    
    /**
     * @var array
     */
    public static $conns = [];

    public static function getDefaultConn(string $database = null) {
        return MysqlDB::getNamedConn("default", "Datasource", $database);
    }

    public static function getNamedConn(string $connKey, string $configSectionName, string $database=null) {
        if(!MysqlDB::$conns[$connKey]) {
            $address = ConfigUtils::getValue($configSectionName, "address");
            if (!$address) {
                throw new \Exception("Database error: MySQL address is not provided in config.ini.");
            }
            $user = ConfigUtils::getValue($configSectionName, "user");
            $password = ConfigUtils::getValue($configSectionName, "password");
            if (!$database) {
                $database = ConfigUtils::getValue($configSectionName, "database");
                if (!$database) {
                    throw new \Exception("Database error: Database is not specified nor provided in config.ini.");
                }
            }
            MysqlDB::$conns[$connKey] = MysqlDB::newConn($address, $user, $password, $database);
        }
        return MysqlDB::$conns[$connKey];

    }

    private static function newConn(string $address, string $user, string $password, string $database) {
        try {
            $conn = new \PDO("mysql:host=$address;dbname=$database", $user, $password);
        } catch (\PDOException $e) {
            throw new \Exception("Database connection error: " . $e->getMessage());
        }
        return $conn;
    }

}
