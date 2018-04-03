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

    public function isLoggedIn (Database $db, $postData, &$error = null)
        {
        if (!$db->isCreated())
            return true; // this will lead to exception which will redirect to setup page

        if ($this->checkUserAuth ())
            return true;

        // not logged in, check if there is a post data
        if (empty ($postData[\Chores\Views\Common::FIELD_USERNAME]) || empty ($postData[\Chores\Views\Common::FIELD_PASSWORD]))
            return false;

        if ($db->checkPassword ($postData[\Chores\Views\Common::FIELD_USERNAME], $postData[\Chores\Views\Common::FIELD_PASSWORD]))
            {
            session_start();
            $_SESSION["auth_user"] = TRUE;
            session_commit();
            header('Location: index.php');
            exit();
            }

        return false;
        }

    public function checkUserAuth ()
        {
        session_start();
        if( isset($_SESSION["auth_user"]) && $_SESSION["auth_user"] === TRUE )
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

    public function prepare (Database $db)
        {
        $this->model = (object)
                [
                    'version' => $db->getVersion(),
                    'mode' => 'category',
                    'categories' => $this->collectCategories($db),
                    'tasks' => $this->collectTasks($db),
                    'selectedCategory' => 0
                ];
        $this->model->errors = $this->errors;
        }

    public function importData (Database $db, string $fileName, bool $clearBeforeImport)
        {
        $this->model = (object)
                [
                    'version' => $db->getVersion(),
                    'messages' => [],
                    'errors' => []
                ];

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
            $taskId = $this->createTask($db, $categoryId, $path, $row->task, $row->frequency, $row->cost, $row->nextDate, $error);
            if (false === $taskId)
                {
                $errors[] = $error;
                return false;
                }
            }
            
        $errors[] = "N/I";
        return false;
        }
    
    protected function createTask (Database $db, int $categoryId, string $path = null,
                                   string $label = null, int $frequency = null, int $cost = null,
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
            
        return [$mainCategoryId, $path.'_'];
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
                        'frequency' => $data[$header['Frequence']],
                        'task' => $data[$header['Task']],
                        'lastDate' => $data[$header['Last Date']],
                        'nextDate' => $data[$header['Next Date']],
                        'cost' => $data[$header['Cost']],
                    ];
            $rows[] = $row;
            }
        
        return $rows;
        }
        
    protected function collectCategories (Database $db)
        {
        $categories = $db->selectCategoriesWithStats($error);
        if (false === $categories)
            {
            $this->errors[] = $error;
            return [];
            }

        return array_map(function ($row)
            {
            $cat = new \stdClass();
            $cat->label = $row['Label'];
            $cat->pendingToday = $row['Pending'];
            $cat->tasks = $row['Tasks'];
            $cat->id = $row['Id'];
            $cat->parentId = $row['Parent Id'] ?? 0;
            return $cat;
            }, $categories ?? []);
        }
        
    protected function collectTasks (Database $db)
        {
        $rows = $db->selectTasks($error);
        if (false === $rows)
            {
            $this->errors[] = $error;
            return [];
            }

        return array_map(function ($row)
            {
            $cat = new \stdClass();
            $cat->categoryId = $row['Category Id'];
            $cat->label = $row['Label'];
            $cat->notes = $row['Notes'];
            $cat->id = $row['Id'];
            $cat->frequency = $row['Frequency'];
            $cat->nextDate = $row['Next Date'];
            $cat->diff = round((strtotime(date('Y-m-d')) - strtotime($row['Next Date'])) / (24 * 60 * 60));
            $cat->cost = $row['Cost'];
            $cat->lastDate = $row['Last Date'];
            return $cat;
            }, $rows ?? []);
        }
    }
