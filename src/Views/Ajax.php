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
        
        switch ($message)
            {
            case "done":
                $id = self::getIntegerParam($postArgs, 'id');
                $result = $model->markTaskDone ($db, $id, self::getBooleanParam($postArgs, 'today', true));
                break;
            default:
                self::writeError("Unrecognized action ($message)", 500);
                exit();
            }
        
        header("HTTP/1.0 200 OK");
        header('Content-Type: application/json');
        echo json_encode ($result);
        }
    }
