<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Chores\Views;

/**
 * Description of ChoresView
 *
 * @author Andrius R. <knutux@gmail.com>
 */
class ChoresView
    {
    public static function write (\Chores\Model $model)
        {
?>
<div class="content-fluid">
    <?= Common::writeBoundErrors()?>
    <div data-bind="if:'category'==mode()">
    <?=self::writeCategories($model)?>
    </div>
</div>
<?=self::writeScripts($model)?>
<?php
        }

    public static function writeCategories (\Chores\Model $model)
        {
?>
    <table class="table table-hover">
        <tbody data-bind="foreach: rootCategories">
            <tr>
                <td data-bind="text: label">

                </td>
                <td data-bind="text: pendingToday">

                </td>
            </tr>
        </tbody>
    </table>
<?php
        }

    public static function writeScripts (\Chores\Model $model)
        {
?>
<script>
    function initializeModel($model)
        {
        $model.rootCategories = ko.computed (function ()
            {
            return $.grep ($model.categories(), function (el) { return ko.unwrap(ko.unwrap(el).parentId) == 0; });
            });
        }
</script>
<?php
        Common::writeModelBindScripts($model->getJSON());
        }

    }
