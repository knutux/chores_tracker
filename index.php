<?php

require __DIR__ . '/vendor/autoload.php';

$error = NULL;

try {
    $db = \Chores\Database::GetInstance();
    $model = new \Chores\Model ();
    $isLogedIn = $model->isLoggedIn ($db, $_POST, $error);
    if ($isLogedIn)
        $model->prepare ($db);
} catch (Chores\SetupException $ex) {
    header('Location: setup.php');
    exit();
    }

Chores\Views\Common::writeHTMLHeader("Chores Tracker", "Track your chores, categorize them, set frequency and priorities.");

if (!$isLogedIn)
    Chores\Views\Common::writeLoginForm("Please enter your credentials", "Login", $error);
else
    \Chores\Views\ChoresView::write($model);

Chores\Views\Common::writeHTMLFooter();
