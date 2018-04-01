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
