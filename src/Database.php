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

    protected function  __construct (bool $debug = false)
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
                ["`Id` INTEGER PRIMARY KEY", "`Label` STRING", "`Archived` SMALLINT DEFAULT 0", "`Category Id` INTEGER", "`Path` STRING", "`Notes` STRING NULL", "`Frequency` INTEGER", "`Next Date` DATE", "`Cost` REAL NULL", "`Permission Group` INTEGER"],
                'unique' => array ("`Category Id`, `Label`"), 'index' => array ("`Path`, `Permission Group`", "`Category Id`, `Next Date`", "`Next Date`"), 'initialize' => true);
        $tables[self::TABLE_CHORES_COMPLETED] = (object)array ('columns' =>
                ["`Chores Id` INTEGER", "`User Id` INTEGER", "`Date` DATE", "`Notes` STRING NULL"],
                'unique' => array ("`Chores Id`, `User Id`, `Date`"), 'index' => array ("`Date`", "`User Id`"), 'initialize' => false);
        $tables[self::TABLE_CHORES_SCHEDULE] = (object)array ('columns' =>
                ["`Chores Id` INTEGER", "`Start Date` DATE", "`End Date` DATE", "`Notes` STRING NULL"],
                'unique' => array ("`Chores Id`, `Start Date`"), 'index' => NULL, 'initialize' => false);
        return $tables;
        }

    public function clearDatabase(string &$error = null) : bool
        {
        foreach (array (self::TABLE_CHORES, self::TABLE_CHORES_CATEGORY, self::TABLE_CHORES_COMPLETED, self::TABLE_CHORES_SCHEDULE) as $tableName)
            {
            if (!$this->executeTruncateTable($tableName, $error))
                return false;
            }
        return true;
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
SELECT cat.`Id`, cat.`Label`, (CASE WHEN cat.`Parent Id` IS NULL THEN 0 ELSE cat.`Parent Id` END) `Parent Id`,
       COUNT(ch.`Id`) `Tasks`, COUNT(CASE WHEN ch.`Archived`<1 AND ch.`Next Date` <= DATE('now') THEN 1 ELSE NULL END) `Pending`
 FROM `$tableName` cat
 LEFT OUTER JOIN `$tableChores` ch ON ch.`Path` LIKE cat.`Path` || '\_' || cat.`Id` || '\_%' ESCAPE '\'
WHERE $permissionFilter
GROUP BY cat.`Id`, cat.`Label`, cat.`Parent Id`
ORDER BY `Priority` ASC
EOT;

        $rows = $this->executeSelect ($tableName, $sql, $error);
        return $rows;
        }
        
    public function selectCategories(string &$error = null)
        {
        $tableName = self::TABLE_CHORES_CATEGORY;
        $sql = <<<EOT
SELECT cat.`Id`, cat.`Label`, (CASE WHEN cat.`Parent Id` IS NULL THEN 0 ELSE cat.`Parent Id` END) `Parent Id`
 FROM `$tableName` cat
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        return $rows;
        }
        
    public function selectTasksWhere(string $where = null, string &$error = null)
        {
        $tableName = self::TABLE_CHORES;
        $tableCompleted = self::TABLE_CHORES_COMPLETED;
        $permissionFilter = $this->createPermissionFilter ('Permission Group');
        $where = empty ($where) ? "" : "($where) AND ";
        $sql = <<<EOT
SELECT ch.`Id`, ch.`Label`, ch.`Category Id`, ch.`Notes`, ch.`Frequency`, ch.`Next Date`, ch.`Cost`,
       MAX(lt.`Date`) `Last Date`, `Archived`
 FROM `$tableName` ch
 LEFT OUTER JOIN `$tableCompleted` lt ON lt.`Chores Id` = ch.`Id`
WHERE $where $permissionFilter
GROUP BY ch.`Id`, ch.`Label`, ch.`Category Id`, ch.`Notes`, ch.`Frequency`, ch.`Next Date`, ch.`Cost`
ORDER BY (CASE WHEN date('now')>ch.`Next Date` THEN -1 * (julianday('now')-julianday(ch.`Next Date`)) / ch.`Frequency` ELSE ch.`Next Date` END) ASC,
         ch.`Frequency`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        return $rows;
        }

    public function selectTasks(string &$error = null)
        {
        return $this->selectTasksWhere(null, $error);
        }

    public function selectSingleTasks(int $id, string &$error = null)
        {
        $rows = $this->selectTasksWhere("ch.`Id`=$id", $error);
        if (empty ($rows))
            {
            $error = $error ?? "Not found";
            return false;
            }
        return $rows[0];
        }
    }