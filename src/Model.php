<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Chores;

/**
 * Description of Model
 *
 * @author Andrius R. <knutux@gmail.com>
 */
class Model
    {
    private $model = false;
    private $errors = [];
    
    const SESSION_VERSION = 2;
    const PREFIX_OLD_ID = "old_";

    public function isLoggedIn (Database $db, $postData, &$error = null)
        {
        if (!$db->isCreated())
            return true; // this will lead to exception which will redirect to setup page

        if ($this->checkUserAuth ())
            return true;

        // not logged in, check if there is a post data
        if (empty ($postData[\Chores\Views\Common::FIELD_USERNAME]) || empty ($postData[\Chores\Views\Common::FIELD_PASSWORD]))
            return false;

        if ($db->checkPassword ($postData[\Chores\Views\Common::FIELD_USERNAME], $postData[\Chores\Views\Common::FIELD_PASSWORD], $id))
            {
            session_start();
            $_SESSION["auth_version"] = self::SESSION_VERSION;
            $_SESSION["auth_id"] = $id;
            session_commit();
            header('Location: index.php');
            exit();
            }

        return false;
        }

    public function getUserId (\Chores\Database $db) : ?int
        {
        return $_SESSION["auth_id"] ?? null;
        }

    public function checkUserAuth ()
        {
        session_start();
        if( isset($_SESSION["auth_version"]) && $_SESSION["auth_version"] === self::SESSION_VERSION )
            {
            return TRUE;
            }
        else
            {
            session_destroy();
            return FALSE;
            }
        }

    public function getJson ()
        {
        return json_encode ($this->model);
        }

    protected function createModelObject (Database $db)
        {
        return (object)
                [
                    'version' => $db->getVersion(),
                    'errors' => [],
                    'baseUrl' => 'ajax.php'
                ];
        }

    public static function createFieldDefinition (string $label, string $type = "text", string $placeholder = null) : \stdClass
        {
        return (object)['dbName' => $label, 'label' => $label, 'type' => $type, 'placeholder' => $placeholder, 'readonly' => false, 'isId' => false];
        }

    public static function createCalculatedFieldDefinition (string $id, callable $fn) : \stdClass
        {
        $field = self::createFieldDefinition ($id);
        $field->readonly = true;
        $field->dbName = $fn;
        return $field;
        }

    public static function markFieldReadonly (\stdClass $field) : \stdClass
        {
        $field->readonly = true;
        return $field;
        }

    public static function markIdField (\stdClass $field) : \stdClass
        {
        $field->readonly = true;
        $field->isId = true;
        return $field;
        }

    public static function getCategoryPropertyMap ()
        {
        return ['label' => self::createFieldDefinition('Label'),
                'pendingToday' => self::markFieldReadonly(self::createFieldDefinition('Pending')),
                'tasks' => self::markFieldReadonly(self::createFieldDefinition('Tasks')),
                'id' => self::markIdField(self::createFieldDefinition('Id')),
                'parentId' => self::markFieldReadonly(self::createFieldDefinition('Parent Id')),
            ];
        }
        
    public static function getTaskPropertyMap ()
        {
        return
            [
            'label' => self::createFieldDefinition('Label'),
            'categoryId' => self::markFieldReadonly(self::createFieldDefinition('Category Id')),
            'notes' => self::createFieldDefinition('Notes'),
            'frequency' => self::createFieldDefinition('Frequency', 'number'),
            'nextDate' => self::createFieldDefinition('Next Date', 'date'),
            'cost' => self::createFieldDefinition('Cost', 'number'),
            'archived' => self::createFieldDefinition('Archived', 'number'),
            'id' => self::markIdField(self::createFieldDefinition('Id')),
            'lastDate' => self::markFieldReadonly(self::createFieldDefinition('Last Date', 'date')),
            'diff' => self::createCalculatedFieldDefinition('Diff', function ($row)
                    {
                    return round((strtotime(date('Y-m-d')) - strtotime($row['Next Date'])) / (24 * 60 * 60));
                    }),
                
            ];
        }
        
    private function recognizeClassForAction (string $type, string &$tableName = null, array &$props = null, bool &$requiresParent = null, string &$parentColumn = null) : bool
        {
        $tableName = false;
        $props = false;
        $requiresParent = false;
        
        switch ($type)
            {
            case 'task':
                $tableName = \Chores\Database::TABLE_CHORES;
                $props = self::getTaskPropertyMap();
                $requiresParent = true;
                $parentColumn = "Category Id";
                break;
            default:
                break;
            }
        
        return !empty ($tableName);  
        }
        
    public function createInstance (Database $db, string $type, int $parentId = null, array $postArgs) : \stdClass
        {
        $model = $this->createModelObject ($db);
        $model->success = false;
        $recognized = $this->recognizeClassForAction ($type, $tableName, $props, $requiresParent, $parentColumn);
        
        if (($requiresParent && (!is_numeric($parentId) || $parentId <= 0)) || !$recognized)
            {
            $model->errors[] = "Invalid arguments - ($type/$parentId)";
            return $model;
            }
            
        $cols = $vals = [];
        if ($parentColumn && !empty ($parentId))
            {
            $cols[] = "`$parentColumn`";
            $vals[] = $parentId;
            }
        foreach ($props as $propId => $prop)
            {
            if ($prop->readonly)
                continue;
            
            if (!isset($postArgs[$propId]))
                continue;
            
            $val = $db->escapeString($postArgs[$propId], true);
            $cols[] = "`{$prop->dbName}`";
            $vals[] = "$val";
            }
            
        $cols = implode (", ", $cols);
        $vals = implode (", ", $vals);
        $sql = "($cols) VALUES ($vals)";
        $id = $db->executeInsert ($tableName, $sql, $error);

        if (false === $id)
            return $model;
        
        switch ($type)
            {
            case 'task':
                $row = $db->selectSingleTasks ($id, $error);
                break;
            default:
                $model->errors[] = "This object type is not fully supported - updated successfully, but cannot refresh the view";
                return $model;
            }
            
        if (false === $row)
            {
            $model->errors[] = $error;
            return $model;
            }
        
        $model->props = array_keys($props);
        $instance = $this->mapDBPropertiesToModelSingle ($row, $props);

        $model->row = $instance;
        $model->success = true;
        return $model;
        }
        
    public function editInstance (Database $db, string $type, int $id, array $postArgs) : \stdClass
        {
        $model = $this->createModelObject ($db);
        $model->success = false;
        $recognized = $this->recognizeClassForAction ($type, $tableName, $props);
        
        if (!is_numeric($id) || $id <= 0 || !$recognized)
            {
            $model->errors[] = "Invalid arguments - ($type/$id)";
            return $model;
            }

        foreach ($props as $propId => $prop)
            {
            if ($prop->readonly)
                continue;
            
            $oldValueId = self::PREFIX_OLD_ID.$propId;
            $newValueId = $propId;
            if (!isset ($postArgs[$oldValueId]) || !isset ($postArgs[$newValueId]) || $postArgs[$newValueId] == $postArgs[$oldValueId])
                continue;
            
            $val = $db->escapeString($postArgs[$newValueId], true);
            $updateValues[] = "`{$prop->dbName}`=$val";
            
            if (empty ($postArgs[$oldValueId]))
                $whereOld[] = "(`{$prop->dbName}` IS NULL OR `{$prop->dbName}`='')";
            else
                {
                $oldVal = $db->escapeString($postArgs[$oldValueId], true);
                $whereOld[] = "`{$prop->dbName}`=$oldVal";
                }
            }
            
        if (empty ($updateValues))
            {
            $model->errors[] = "Nothing to update";
            return $model;
            }

        $updateValues = implode (", ", $updateValues);
        $whereOld = implode (" AND ", $whereOld);
        $sql = "SET $updateValues WHERE `Id`=$id AND $whereOld";
        if (false === $db->executeUpdate ($tableName, $sql, $error))
            return $model;
        
        $affected = $db->executeSelectSingle ($tableName, "SELECT changes()", $error);
        if (false === $db->executeUpdate ($tableName, $sql, $error))
            return $model;
        
        if (0 == $affected)
            {
            $model->errors[] = "Error changing the instance - someone might have already changesd part of the same instance recently. Please reload and retry";
            return $model;
            }
            
        switch ($type)
            {
            case 'task':
                $row = $db->selectSingleTasks ($id, $error);
                break;
            default:
                $model->errors[] = "This object type is not fully supported - updated successfully, but cannot refresh the view";
                return $model;
            }
            
        if (false === $row)
            {
            $model->errors[] = $error;
            return $model;
            }
        
        $model->props = array_keys($props);
        $instance = $this->mapDBPropertiesToModelSingle ($row, $props);

        $model->row = $instance;
        $model->success = true;
        return $model;
        }
        
    public function prepare (Database $db)
        {
        $this->model = $this->createModelObject ($db);
        $this->model->mode = 'category';
        $this->model->categories = $this->collectCategories($db);
        $this->model->tasks = $this->collectTasks($db);
        $this->model->selectedCategory = 0;
        $this->model->errors = $this->errors;
        }

    private function getTaskForUpdate (Database $db, int $id, string $today, string &$error = null)
        {
        $tableName = Database::TABLE_CHORES;
        $tableCompleted = Database::TABLE_CHORES_COMPLETED;
        $sql = <<<EOT
SELECT `Frequency`, `Next Date`, `Date` `Last Date`
  FROM `$tableName` c
  LEFT OUTER JOIN `$tableCompleted` o ON c.`Id`=o.`Chores Id` AND `Date` = '$today'
  WHERE c.`Id`=$id
EOT;
        $ret = $db->executeSelect ($tableName, $sql, $error);
        if (empty ($ret))
            {
            $error = false === $ret ? $error : "Object not found - $id";
            return false;
            }

        $nextDate = $this->calculateNextDate ($db, $id, $today, $ret[0]['Frequency'], $error);
        if (null === $nextDate)
            return false;
        
        $ret[0]['New Next Date'] = $nextDate;
        if ($ret[0]['Next Date'] == $ret[0]['New Next Date'] && $today == $ret[0]['Last Date'])
            {
            $error = "Nothing to update - same date is already recorded";
            return false;
            }
            
        return $ret[0];
        }

    private function calculateNextDate (Database $db, int $id, string $today, int $frequency, string &$error = null) : ?string
        {
        // TODO: take the schedule into account
        if ($frequency <= 0)
            $frequency = 1;
        
        return date('Y-m-d', strtotime ("+ $frequency day", strtotime ($today)));
        }

    private function updateTaskCompletedDate (Database $db, int $id, string $today, array $row, string &$error = null) : bool
        {
        $nextDate = $row['New Next Date'];
        $recordedNextDate = $row['Next Date'];
        if ($recordedNextDate != $nextDate)
            {
            $tableName = Database::TABLE_CHORES;
            $sql = "SET `Next Date`='$nextDate' WHERE `Id`=$id";
            if (false === $db->executeUpdate ($tableName, $sql, $error))
                return false;
            }

        $recordedLastDate = $row['Last Date'];
        if ($today != $recordedLastDate)
            {
            $tableName = Database::TABLE_CHORES_COMPLETED;
            $userId = $this->getUserId ($db);
            $sql = "(`Chores Id`, `Date`, `User Id`) VALUEs ($id, '$today', $userId)";
            if (false === $db->executeInsert ($tableName, $sql, $error))
                return false;
            }
            
        return true;
        }

    public function archiveTask (Database $db, int $id, bool $archive = true) : \stdClass
        {
        $model = $this->createModelObject ($db);
        $model->success = false;
        if (!is_numeric($id) || $id <= 0)
            {
            $model->errors[] = "Invalid id - $id";
            return $model;
            }

        $tableName = Database::TABLE_CHORES;
        $val = $archive ? 1 : 0;
        $sql = "SET `Archived`=$val WHERE `Id`=$id";
        if (false === $db->executeUpdate ($tableName, $sql, $error))
            {
            // ensure column "Archived" exists
            $sqlAlter = "ALTER TABLE `$tableName` ADD COLUMN `Archived` TINYINT DEFAULT 0";
            if (false === $db->executeSQL ($tableName, $sqlAlter, $error) ||
                false === $db->executeUpdate ($tableName, $sql, $error))
                {
                $model->errors[] = $error;
                return $model;
                }
            }
        
        $model->row = $this->getChangedRow ($model, $db, $id);
        $model->success = true;
        return $model;
        }

    public function markTaskDone (Database $db, int $id = null, bool $markToday = null) : \stdClass
        {
        $model = $this->createModelObject ($db);
        $model->success = false;
        if (!is_numeric($id) || $id <= 0)
            {
            $model->errors[] = "Invalid id - $id";
            return $model;
            }
            
        $today = date('Y-m-d', $markToday ? time() : strtotime('-1 day'));
        $row = $this->getTaskForUpdate($db, $id, $today, $error);
        if (false === $row || false === $this->updateTaskCompletedDate($db, $id, $today, $row, $error))
            {
            $model->errors[] = $error;
            return $model;
            }
                    
        $model->row = $this->getChangedRow ($model, $db, $id);
        $model->success = true;
        return $model;
        }

    public function getChangedRow (\stdClass $model, Database $db, int $id)
        {
        $row = $db->selectSingleTasks ($id, $error);
        if (false === $row)
            {
            $model->errors[] = $error;
            return $model;
            }

        $props = $this->getTaskPropertyMap ();
        $model->props = array_keys($props);

        $instance = $this->mapDBPropertiesToModelSingle ($row, $props);
        return $instance;
        }
        
    public function importData (Database $db, string $fileName, bool $clearBeforeImport)
        {
        $this->model = $this->createModelObject ($db);
        $this->model->messages = [];

        if (!file_exists($fileName))
            $this->model->errors[] = "Invalid file specified";
        else
            $this->processImport ($db, $fileName, $clearBeforeImport, $this->model->messages, $this->model->errors);
        
        return $this->model;
        }
    
    protected function processImport (Database $db, string $fileName, bool $clearBeforeImport, array &$messages, array &$errors) : bool
        {
        $file = fopen ($fileName, "r");
        if (false == $file)
            {
            $errors[] = "Could not open the file";
            return false;
            }
        
        $messages[] = "Reading $fileName";
        $contents = $this->readImportFile ($file, $errors);
        fclose ($file);

        if (false === $contents)
            {
            return false;
            }

// Warning: do not commit the next line
// define ("ENABLE_CLEAR_DURING_IMPORT", true);
        if ($clearBeforeImport)
            {
            if (!defined ('ENABLE_CLEAR_DURING_IMPORT'))
                die ("Error: Please set <b>ENABLE_CLEAR_DURING_IMPORT</b> constant to enable clearing");
            $db->clearDatabase();
            }
            
        $cnt = count($contents);
        $messages[] = "{$cnt} rows found";
        
        $existingCategories = $db->selectCategories($error);
        if (false === $existingCategories)
            {
            $errors[] = $error;
            return false;
            }
            
        $cnt = count($existingCategories);
        $messages[] = "$cnt categories found";
        return $this->importRows ($db, $existingCategories, $contents, $messages, $errors);
        }
    
    protected function importRows (Database $db, array $existingCategories, array $contents, array &$messages, array &$errors) : bool
        {
        foreach ($contents as $row)
            {
            // 'area', 'category', 'task', 'frequence', 'lastDate', 'nextDate', 'cost'
            $categoryId = $this->findOrCreateCategory ($db, $existingCategories, $row->area, $row->category, $error);
            if (false === $categoryId)
                {
                $errors[] = $error;
                return false;
                }
            
            list ($categoryId, $path) = $categoryId;
            $row->cost = (int)$row->cost;
            $cost = empty ($row->cost) ? null : ($row->cost >= 5 ? $row->cost : 60 * $row->cost); // convert hours to minutes

            $taskId = $this->createTask($db, $categoryId, $path, $row->task, $row->frequency, $cost, $row->nextDate, $error);
            if (false === $taskId)
                {
                $errors[] = $error;
                return false;
                }
            }
            
        $messages[] = "Import has completed successfully";
        return true;
        }
    
    protected function createTask (Database $db, int $categoryId, string $path = null,
                                   string $label = null, int $frequency = null, float $cost = null,
                                   string $nextDate = null, string &$error = null)
        {
        $cost = NULL === $cost ? 'NULL' : $cost;
        $label = $db->escapeString ($label, true);
        $path = "{$path}{$categoryId}_";
        $sql = <<<EOT
(`Label`, `Notes`, `Category Id`, `Permission Group`, `Next Date`, `Frequency`, `Cost`, `Path`)
    VALUES
($label, '-', $categoryId, 1, '$nextDate', $frequency, $cost, '$path')
EOT;

        return $db->executeInsert(Database::TABLE_CHORES, $sql, $error);
        }
    
    protected function findCategory (array $existingCategories, string $category, int $parentId = null)
        {
        foreach ($existingCategories as $cat)
            {
            if ($cat['Parent Id'] == $parentId && 0 == strcasecmp($category, $cat['Label']))
                return $cat['Id'];
            }
        
        return false;
        }
    
    protected function findOrCreateCategory (Database $db, array &$existingCategories, string $mainCategory, string $subCategory = null, string &$error = null)
        {
        $mainCategoryId = $this->findCategory ($existingCategories, $mainCategory, null);
        if (false === $mainCategoryId)
            {
            $mainCategoryId = $this->createCategory ($db, $mainCategory, null, '', $error);
            if (false === $mainCategoryId)
                return false;
            $existingCategories[] = ['Id' => $mainCategoryId, 'Label' => $mainCategory, 'Parent Id' => null, 'Path' => ''];
            }
        
        if (empty ($subCategory))
            return [$mainCategoryId, '_'];
        
        $path = "_$mainCategoryId";
        $categoryId = $this->findCategory ($existingCategories, $subCategory, $mainCategoryId);
        if (false === $categoryId)
            {
            $categoryId = $this->createCategory ($db, $subCategory, $mainCategoryId, $path, $error);
            if (false === $categoryId)
                return false;
            $existingCategories[] = ['Id' => $categoryId, 'Label' => $subCategory, 'Parent Id' => $mainCategoryId, 'Path' => $path];
            }
            
        return [$categoryId, $path.'_'];
        }
    
    protected function createCategory (Database $db, string $category, int $parentId = null, string $path = null, string &$error = null)
        {
        $parentId = NULL === $parentId ? 'NULL' : $parentId;
        $category = $db->escapeString ($category, true);
        $sql = <<<EOT
(`Label`, `Notes`, `Priority`, `Path`, `Permission Group`, `Parent Id`)
    VALUES
($category, '-', 10, '$path', 1, $parentId)
EOT;
        return $db->executeInsert(Database::TABLE_CHORES_CATEGORY, $sql, $error);
        }
    
    protected function readImportFile ($file, &$errors)
        {
        $header = false;
        $rows = [];
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE)
            {
            if (false === $header)
                {
                $header = array_combine(array_values($data), array_keys($data));
                if (!isset ($header['Area']) || !isset ($header['Task']) || !isset ($header['Frequence']) ||
                    !isset ($header['Last Date']) || !isset ($header['Next Date']) || !isset ($header['Cost']))
                    {
                    $errors[] = "Invalid columns found in header";
                    var_dump ($header);
                    return false;
                    }
                continue;
                }
                
            $row = (object)
                    [
                        'area' => $data[$header['Area']],
                        'category' => empty ($header['Category']) ? null : $data[$header['Category']],
                        'frequency' => max(round($data[$header['Frequence']]), 1),
                        'task' => $data[$header['Task']],
                        'lastDate' => $data[$header['Last Date']],
                        'nextDate' => $data[$header['Next Date']],
                        'cost' => $data[$header['Cost']],
                    ];

            $rows[] = $row;
            }
        
        return $rows;
        }
        
    protected function mapDBPropertiesToModelSingle (array $row, array $props = null) : \stdClass
        {
        $cat = new \stdClass();

        foreach ($props as $modelProp => $dbProp)
            {
            if (is_callable($dbProp->dbName))
                $cat->{$modelProp} = call_user_func($dbProp->dbName, $row);
            else
                $cat->{$modelProp} = $row[$dbProp->dbName];
            }
        return $cat;
        }
        
    protected function mapDBPropertiesToModel (array $rows = null, array $props = null) : array
        {
        //var_dump ($rows, $props);
        return array_map(function ($row) use ($props)
            {
            return $this->mapDBPropertiesToModelSingle ($row, $props);
            }, $rows ?? []);
        }
        
    protected function collectCategories (Database $db)
        {
        $categories = $db->selectCategoriesWithStats($error);
        //var_dump ($categories, $error);
        if (false === $categories)
            {
            $this->errors[] = $error;
            return [];
            }

        return $this->mapDBPropertiesToModel ($categories, $this->getCategoryPropertyMap ());
        }
        
    protected function collectTasks (Database $db)
        {
        $rows = $db->selectTasks($error);
        if (false === $rows)
            {
            $this->errors[] = $error;
            return [];
            }

        return $this->mapDBPropertiesToModel ($rows, $this->getTaskPropertyMap ());
        }
    }
