<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Chores\Views;

/**
 * Description of Ajax
 *
 * @author Andrius R. <knutux@gmail.com>
 */
class Ajax
    {
    public static function writeError (string $err, int $statusCode = 500)
        {
        $statusText = 403 == $statusCode ? "Unauthorized" : "Server error";
        header("HTTP/1.0 $statusCode $statusText");
        header('Content-Type: application/json');
        echo json_encode ((object)['errors' => [$err], 'success' => false, 'result' => null]);
        }
        
    public static function getStringParam (array $postArgs, string $name, bool $default = null) : ?string
        {
        return $postArgs[$name] ?? $default;
        }
        
    public static function getBooleanParam (array $postArgs, string $name, bool $default = false) : bool
        {
        return ($postArgs[$name] ?? $default) == true;
        }
        
    public static function getIntegerParam (array $postArgs, string $name, int $default = 0) : int
        {
        $val = $postArgs[$name] ?? null;
        return is_numeric ($val) ? $val : $default;
        }
        
    public static function handleMessage (\Chores\Database $db, \Chores\Model $model, array $postArgs = null)
        {        
        $message = $postArgs['fn'] ?? null;
        $message = preg_split ('/:/', $message, 2);
        
        switch ($message[0])
            {
            case "done":
                $id = self::getIntegerParam($postArgs, 'id');
                $result = $model->markTaskDone ($db, $id, self::getBooleanParam($postArgs, 'today', true));
                break;
            case "skip":
                $id = self::getIntegerParam($postArgs, 'id');
                $result = $model->skipTask ($db, $id);
                break;
            case "archive":
                $id = self::getIntegerParam($postArgs, 'id');
                $undo = self::getBooleanParam($postArgs, 'undo');
                $result = $model->archiveTask ($db, $id, !$undo);
                break;
            case "edit":
                $type = $message[1] ?? '??';
                $id = self::getIntegerParam($postArgs, 'id');
                $result = $model->editInstance ($db, $type, $id, $postArgs);
                break;
            case "create":
                $type = $message[1] ?? '??';
                $parentId = self::getIntegerParam($postArgs, 'id');
                $result = $model->createInstance ($db, $type, $parentId, $postArgs);
                break;
            default:
                self::writeError("Unrecognized action ($message[0]])", 500);
                exit();
            }
        
        header("HTTP/1.0 200 OK");
        header('Content-Type: application/json');
        echo json_encode ($result);
        }
    }
