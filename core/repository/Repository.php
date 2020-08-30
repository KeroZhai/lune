<?php

namespace app\core\repository;

use app\core\exception\InvalidStateException;
use app\core\pagination\PageInfo;
use app\core\util\ReflectionUtils;

class Repository {

    private $conn;

    private $hasTransaction = false;

    public function __construct($conn=null) {
        $this->conn = $conn == null ? MysqlDB::getDefaultConn() : $conn;
    }

    public function setConn($conn) {
        if ($this->hasTransaction) {
            throw new InvalidStateException("Cannot reset connection with unfinished transaction");
        }
        return $this->conn = $conn;
    }

    public function getConn() {
        return $this->conn;
    }

    public function beginTransaction() {
        $conn = $this->getConn();
        $conn->beginTransaction();
        $this->hasTransaction = true;
    }
    
	public function commit() {
        $conn = $this->getConn();
        $conn->commit();
        $this->hasTransaction = false;
    }

    public function rollback() {
        $conn = $this->getConn();
        $conn->rollback();
        $this->hasTransaction = false;
    }

    public function save(Object $entity) {
        $tableName = $this->getTableName($entity);
        $properties = ReflectionUtils::getClassProperties($entity);
        $fieldNames = [];
        $fieldValues = [];
        $placeholders = [];
        foreach ($properties as $property) {
            if ($value = $property->getValue($entity)) {
                $fieldName = ReflectionUtils::getDocCommentTag($property->getDocComment(), "@column");
                $fieldNames[] = $fieldName ? $fieldName : $this->camelToUnderScore($property->getName());
                $fieldValues[] = $value;
                $placeholders[] = "?";
            }
        }
        $fieldNamesStr = implode(',', $fieldNames);
        $placeholdersStr = implode(',', $placeholders);
        $sql = "INSERT INTO $tableName($fieldNamesStr) VALUES ($placeholdersStr)";
        $this->execute($sql, $fieldValues);
    }

    public function update(Object $entity) {
        $properties = ReflectionUtils::getClassProperties($entity);
        $PK = null;
        $fieldValues = [];
        $tableName = $this->getTableName($entity);
        $sql = "UPDATE $tableName SET ";
        foreach ($properties as $property) {

            if ($value = $property->getValue($entity)) {
                if ($property->getName() === "id") {
                    $PK = $value;
                    continue;
                }
                $fieldName = ReflectionUtils::getDocCommentTag($property->getDocComment(), "@column");
                $fieldName = $fieldName ? $fieldName : $this->camelToUnderScore($property->getName());
                $fieldValues[] = $value;
                $sql .= "$fieldName = ?, ";
            }
        }
        if ($PK) {
            $sql = substr($sql, 0, -2) .  " WHERE id = ?";
            $fieldValues[] = $PK;
        } else {
            throw new \Exception("No primary key - id found");
        }
        $this->execute($sql, $fieldValues);
    }

    public function delete($objOrClassName, Int ...$ids) {
        $tableName = $this->getTableName($objOrClassName);
        $idsStr = implode(',', $ids);
        $sql = "DELETE FROM $tableName WHERE id IN ($idsStr)";
        $this->execute($sql);
    }

    public function execute(String $sql, Array $paramValues = null, Array $paramTypes = null) {
        if ($paramTypes != null && $paramValues != null && count($paramTypes) != count($paramValues)) {
            throw new \Exception("The number of parameter values and types mismatches");
        }
        $conn = $this->getConn();
        $conn->query("SET names utf8");
        $stmt = $conn->prepare($sql);
        if ($paramValues != null) {
            for ($i = 0; $i < count($paramValues); $i++) {
                $stmt->bindParam($i + 1, $paramValues[$i], $paramTypes[$i] ? $paramTypes[$i] : \PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        if ($errorMessage = $stmt->errorInfo()[2]) {
            throw new \Exception($errorMessage);
        }
        return $stmt;
    }

    /**
     * 
     *
     * @param $sql 
     * @param $page 
     * @param $rows 
     * @param $$objOrClassName
     *
     */
    public function queryForPage(String $sql, int $page, int $size, $objOrClassName, Array $paramValues = null, Array $paramTypes = null) {
        $totalElements = $this->queryForCount($sql);
        $totalPages = ceil($totalElements / $size);
        
        $start = ($page -  1) * $size;
        $sql = str_replace(";", "", $sql);
        $sql .= " LIMIT $start, $size;";
        $result = $this->queryForList($sql, $objOrClassName, $paramValues, $paramTypes);
        
        if ($result) {
            return PageInfo::builder()
                ->content($result)
                ->empty(empty($result))
                ->last($page == $totalPages)
                ->numberOfElements(count($result))
                ->page($page)
                ->size($size)
                ->totalElements($totalElements)
                ->totalPages($totalPages)
                ->build();
        } else {
            return null;
        }
        
    }

    /**
     * 
     *
     * @param $sql
     * @param $objOrClassName
     */
    public function queryForList(String $sql, $objOrClassName, Array $paramValues = null, Array $paramTypes = null) {
        $reflectClass = new \ReflectionClass($objOrClassName);
        $properties = [];
        foreach ($reflectClass->getProperties() as $property) {
            $properties[$this->simplifyStr($property->getName())] = $property;
        }
        $resultList = [];
        $result = $this->execute($sql, $paramValues, $paramTypes)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $instance = $reflectClass->newInstance();
            foreach ($row as $key => $value) {
                $_key = $this->simplifyStr($key);
                if (array_key_exists($_key, $properties)) {
                    $properties[$_key]->setValue($instance, $value);
                }
            }
            $resultList[] = $instance;
        }
        return $resultList;
    }

    /**
     * 
     *
     * @param $sql
     * @param $objOrClassName
     */
    public function queryForOne(String $sql, $objOrClassName, Array $paramValues = null, Array $paramTypes = null) {
        $resultList = $this->queryForList($sql, $objOrClassName, $paramValues, $paramTypes);
        return empty($resultList) ? null : $resultList[0];
    }

    /**
     * 
     *
     * @param $sql 
     * @return 
     */
    public function queryForCount(String $sql) {
        // 加上括号才能使短路生效
        if (($from = strrchr($sql, "FROM")) || ($from = strrchr($sql, "from"))) {
            $sql = "SELECT COUNT(*) AS count " . $from;
            foreach ($this->execute($sql) as $row) {
                return $row["count"];
            }
        } else {
            throw new \Exception("Missing FROM clause in SQL!");
        }
    }

    /**
     * 
     * 
     * @param $str 
     */
    private function simplifyStr(String $str)  {
        return strtolower(str_replace("_", "", $str));
    }

    private function camelToUnderScore(String $str) {
        $result = "";
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            if ($i != 0 && (ord($char) > 64 && ord($char) < 91)) {
                $result .= "_";
            }
            
            $result .= $char;
        }
        return $result;
    }

    /**
     * 
     *
     */
    private function getTableName($objOrClassName) {
        $tableName = ReflectionUtils::getDocCommentTag(ReflectionUtils::getClassDocComment($objOrClassName), "@table");
        if (!$tableName) {
            throw new \Exception("No @table found for class " . ReflectionUtils::getClass($objOrClassName)->getName());
        }
        return $tableName;
    }
}
