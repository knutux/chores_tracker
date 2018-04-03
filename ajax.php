<?php

require __DIR__ . '/vendor/autoload.php';

$error = NULL;

try {
    $db = \Chores\Database::GetInstance();
    $model = new \Chores\Model ();
    $postArgs = filter_input_array(INPUT_POST);
    $isLogedIn = $model->isLoggedIn ($db, $postArgs, $error);
    if (!$isLogedIn)
        \Chores\Views\Ajax::writeError('Unauthorized', 403);
    else
        {
        \Chores\Views\Ajax::handleMessage($db, $model, $postArgs);
        }
} catch (Chores\SetupException $ex) {
    \Chores\Views\Ajax::writeError('Database not setup', 500);
    exit();
    }
