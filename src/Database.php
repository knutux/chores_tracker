<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Chores;

class Database extends DatabaseCore
    {
    const TABLE_CHORES = "Chores";
    const TABLE_CHORES_CATEGORY = "Chores Categories";
    const TABLE_CHORES_COMPLETED = "Chores Completion";
    const TABLE_CHORES_SCHEDULE = "Chores Schedule";

    private function  __construct (bool $debug = false)
        {
        parent::__construct ($debug);
        }

    private static $s_instance = false;
    public static function GetInstance(bool $debug = false)
        {
        if (false === self::$s_instance)
            self::$s_instance = new Database($debug);

        return self::$s_instance;
        }

    protected function getDBName () : string
        {
        return "chores.db";
        }

    protected function getLatestVersionId () : int
        {
        return 1;
        }

    protected function getTableDefinitions () : array
        {
        $tables = parent::getTableDefinitions();
        $tables[self::TABLE_CHORES_CATEGORY] = (object)array ('columns' =>
                ["`Id` INTEGER PRIMARY KEY", "`Label` STRING", "`Parent Id` INTEGER NULL", "`Path` STRING", "`Notes` STRING NULL", "`Priority` INTEGER", "`Permission Group` INTEGER"],
                'unique' => array ("`Parent Id`, `Label`"), 'index' => array ("`Permission Group`, `Path`"), 'initialize' => true);
        $tables[self::TABLE_CHORES] = (object)array ('columns' =>
                ["`Id` INTEGER PRIMARY KEY", "`Label` STRING", "`Category Id` INTEGER", "`Path` STRING", "`Notes` STRING NULL", "`Frequency` INTEGER", "`Next Date` DATE", "`Cost` REAL NULL", "`Permission Group` INTEGER"],
                'unique' => array ("`Category Id`, `Label`"), 'index' => array ("`Path`, `Permission Group`", "`Category Id`, `Next Date`", "`Next Date`"), 'initialize' => true);
        $tables[self::TABLE_CHORES_COMPLETED] = (object)array ('columns' =>
                ["`Chores Id` INTEGER", "`User Id` INTEGER", "`Date` DATE", "`Notes` STRING NULL"],
                'unique' => array ("`Chores Id`, `User Id`, `Date`"), 'index' => array ("`Date`", "`User Id`"), 'initialize' => false);
        $tables[self::TABLE_CHORES_SCHEDULE] = (object)array ('columns' =>
                ["`Chores Id` INTEGER", "`Start Date` DATE", "`End Date` DATE", "`Notes` STRING NULL"],
                'unique' => array ("`Chores Id`, `Start Date`"), 'index' => NULL, 'initialize' => false);
        return $tables;
        }

    protected function initializeTable (string $tableName, string &$error = null) : bool
        {
        switch ($tableName)
            {
            case self::TABLE_CHORES_CATEGORY:
                if (false === $this->executeInsert($tableName, "(`Label`, `Notes`, `Priority`, `Path`, `Permission Group`) VALUES ('Chores', 'Sample chores category', 10, '', 1)", $error))
                    return false;
                if (false === $this->executeInsert($tableName, "(`Label`, `Notes`, `Priority`, `Path`, `Permission Group`) VALUES ('Health', 'Mental and physical excercise', 8, '', 1)", $error))
                    return false;

                break;
            case self::TABLE_CHORES:
                if (false === $this->executeInsert($tableName, "(`Label`, `Notes`, `Category Id`, `Permission Group`, `Next Date`, `Frequency`, `Path`) VALUES ('Dinner', 'Cook something for dinner', 1, 1, '2000-01-01', 1, '_1_')", $error))
                    return false;

            default:
                break;
            }
        return parent::initializeTable($tableName, $error);
        }

    public function selectCategoriesWithStats(string &$error = null)
        {
        $tableName = self::TABLE_CHORES_CATEGORY;
        $tableChores = self::TABLE_CHORES;
        $permissionFilter = $this->createPermissionFilter ('Permission Group');
        $sql = <<<EOT
SELECT cat.`Id`, cat.`Label`, cat.`Parent Id`,
       COUNT(ch.`Id`) `Tasks`, COUNT(CASE WHEN ch.`Next Date` <= DATE('now') THEN 1 ELSE NULL END) `Pending`
 FROM `$tableName` cat
 LEFT OUTER JOIN `$tableChores` ch ON ch.`Path` LIKE cat.`Path` || '_' || cat.`Id` || '_%'
WHERE $permissionFilter
GROUP BY cat.`Id`, cat.`Label`, cat.`Parent Id`
ORDER BY `Priority` ASC
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        return $rows;
        }
    }