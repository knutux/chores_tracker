<?php

require __DIR__ . '/vendor/autoload.php';

$db = \Chores\Database::GetInstance(true);
$model = new \Chores\Model ();

if ($db->isCreated () && is_numeric ($db->getVersion()))
    {
    header('Location: index.php');
    exit();
    }

$error = "";
if (!empty ($_POST[Chores\Views\Common::FIELD_USERNAME]) && !empty ($_POST[Chores\Views\Common::FIELD_PASSWORD]))
    {
    if ($db->initializeDB ($_POST[Chores\Views\Common::FIELD_USERNAME], $_POST[Chores\Views\Common::FIELD_PASSWORD], $error))
        {
        header('Location: index.php');
        exit();
        }
    }

Chores\Views\Common::writeHTMLHeader("Chores Tracker - Setup Database", "Track your chores, categorize them, set frequency and priorities.");
Chores\Views\Common::writeLoginForm("Define Admin User", $error);
Chores\Views\Common::writeHTMLFooter();
