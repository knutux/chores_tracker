<?php

require __DIR__ . '/vendor/autoload.php';

$error = NULL;

try {
    $db = \Chores\Database::GetInstance(true);
    $model = new \Chores\Model ();
    $isLogedIn = $model->isLoggedIn ($db, $_POST, $error);
    if ($isLogedIn)
        $model->importData ($db, $_GET['file'] ?? "", !empty ($_GET['clear']));
} catch (Chores\SetupException $ex) {
    header('Location: setup.php');
    exit();
    }

Chores\Views\Common::writeHTMLHeader("Chores Tracker Import", "Track your chores, categorize them, set frequency and priorities.");

if (!$isLogedIn)
    Chores\Views\Common::writeLoginForm("Please enter your credentials", $error);
else
    \Chores\Views\ImportView::write($model);

Chores\Views\Common::writeHTMLFooter();
