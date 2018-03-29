<?php

require __DIR__ . '/vendor/autoload.php';

    $db = \Chores\Database::GetInstance();
    $model = new \Chores\Model ();
    
    if ($db->isCreated ())
        {
        header('Location: index.php');
        exit();
        }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<HTML>
<HEAD>
    <TITLE>Chores Tracker - Setup Database</TITLE>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
    <!--meta name="viewport" content="width=device-width, initial-scale=1"-->
    <META NAME="Description" CONTENT="Track your chores, categorize them, set frequency and priorities.">
    <META NAME="Author" CONTENT="knutux@gmail.com">
    </HEAD>
<BODY>
    <h1>Chores Tracker</h1>
</BODY>
</HTML>