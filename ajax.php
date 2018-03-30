<?php

require __DIR__ . '/vendor/autoload.php';

$error = NULL;

try {
    $db = \Chores\Database::GetInstance();
    $model = new \Chores\Model ();
    $isLogedIn = $model->isLoggedIn ($db, $_POST, $error);
    if (!$isLogedIn)
        \Chores\Views\Ajax::writeError('Unauthorized', 403);
    else
        {

        }
} catch (Chores\SetupException $ex) {
    \Chores\Views\Ajax::writeError('Database not setup', 500);
    exit();
    }
