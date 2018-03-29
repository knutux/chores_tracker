<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Chores;

class Database
    {
    const TABLE_CHORES = "Chores";
    const TABLE_VERSION = "Version";

    private $db = false;
    private function  __construct ()
        {

        }

    private static $s_instance = false;
    public static function GetInstance()
        {
        if (false === self::$s_instance)
            self::$s_instance = new Database();

        return self::$s_instance;
        }

    protected function ensureInitialized (bool $checkIsValid)
        {
        if (false === $this->db)
            {
            $this->db = new \SQLite3  ("chores.db");

            if ($checkIsValid)
                {
                $isSetUp = $this->_tableExists(self::TABLE_VERSION);
                if (!$isSetUp)
                    throw new SetupException("Database not set up");
                }
            }
        }

    public function getVersion ()
        {
        $this->ensureInitialized();
        $isSetUp = $this->db->querySingle("SELECT MAX(`version`) FROM `".self::TABLE_VERSION."`");
        }

    protected function _tableExists ($tableName) : bool
        {
        $exists = $this->db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName';");
        return !empty($exists);
        }

    public function tableExists ($tableName) : bool
        {
        $this->ensureInitialized();
        return $this->_tableExists($tableName);
        }

    public function isCreated () : bool
        {
        $this->ensureInitialized(false);
        return $this->_tableExists(self::TABLE_VERSION);
        }
    }