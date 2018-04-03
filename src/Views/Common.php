<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Chores\Views;

/**
 * Description of Common
 *
 * @author Andrius R. <knutux@gmail.com>
 */
class Common
    {
    const FIELD_USERNAME = "ch_user";
    const FIELD_PASSWORD = "ch_pwd";

    public static function writeHTMLHeader (string $title = null, string $description = null)
        {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<HTML>
<HEAD>
    <TITLE><?=$title?></TITLE>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
    <!--meta name="viewport" content="width=device-width, initial-scale=1"-->
    <META NAME="Description" CONTENT="<?=$description?>">
    <META NAME="Author" CONTENT="knutux@gmail.com">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.4.2/knockout-min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.4.1/knockout.mapping.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.5/lodash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/URI.js/1.19.1/URI.min.js"></script>
    <script src="js/URI.fragmentQuery.js"></script>
    <script src="js/ko.extenders.urlSync.js"></script>
</HEAD>
<BODY>
    <h1><?=$title?></h1>
<?php
        }

    public static function writeHTMLFooter ()
        {
?>
</BODY>
</HTML>
<?php
        }

    public static function writeErrorAsRequired (string $error = null)
        {
        if (!empty ($error))
            {
?>
        <div class="alert alert-danger">
          <strong>Error!</strong> <?=$error?>
        </div>
<?php
            }
        }

    public static function writeBoundErrors (string $param = "errors")
        {
?>
    <div data-bind="foreach: <?=$param?>">
        <div class="alert alert-danger">
            <strong>Error!</strong> <span data-bind="text:$data"></span>
        </div>
    </div>
<?php
        }

    public static function writeBoundMessages (string $param = "messages", string $status = "info")
        {
?>
    <div data-bind="foreach: <?=$param?>">
        <div class="alert alert-<?=$status?>">
            <strong>Error!</strong> <span data-bind="text:$data"></span>
        </div>
    </div>
<?php
        }

    public static function writeLoginForm (string $title, $error)
        {
?>
    <!-- LOGIN FORM -->
    <div class="text-center" style="margin:50px auto; width:400px">
	<div class="logo"><?=$title?></div>
        <?=self::writeErrorAsRequired($error)?>
	<!-- Main Form -->
	<div class="login-form-1">
		<form id="login-form" class="text-left" method="POST">
			<div class="login-form-main-message"></div>
			<div class="main-login-form text-center">
				<div class="login-group">
					<div class="form-group">
						<label for="lg_username" class="sr-only">Username</label>
                                                <input type="text" class="form-control" id="<?=self::FIELD_USERNAME?>" name="<?=self::FIELD_USERNAME?>" placeholder="username">
					</div>
					<div class="form-group">
						<label for="lg_password" class="sr-only">Password</label>
						<input type="password" class="form-control" id="<?=self::FIELD_PASSWORD?>" name="<?=self::FIELD_PASSWORD?>" placeholder="password">
					</div>
				</div>
				<button type="submit" class="login-button"><i class="fa fa-chevron-right"></i> Create</button>
			</div>
		</form>
	</div>
	<!-- end:Main Form -->
</div>
<?php
        }

    public static function writeModelBindScripts ($json, string $initializeFn = "initializeModel")
        {
?>
<script>
    $(function () {
        var viewModel = <?=$json?>;
        viewModel = ko.mapping.fromJS(viewModel);
        <?=empty ($initializeFn) ? "(function(){})" : $initializeFn?> (viewModel);
        try
            {
            ko.applyBindings(viewModel);
            }
        catch (err)
            {
            $('body').html (err.toString());
            }
    });
</script>
<?php
        }

    }
