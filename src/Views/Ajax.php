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
    }
