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
    <?=self::writeTasks($model)?>
    </div>
</div>
<?=self::writeScripts($model)?>
<?php
        }

    public static function writeCategories (\Chores\Model $model)
        {
?>
    <table class="table table-hover">
        <thead>
            <tr>
                <td width="50%">Label</td>
                <td width="1%" colspan="3">Overdue</td>
            </tr>
        </thead>
        <tbody data-bind="foreach: selectedCategories">
            <tr>
                <td>
                  <div class="btn btn-link" data-bind="text: label, click: navigateTo">
                  </div>
                </td>
                <td class="text-right">
                    <span data-bind="text: pendingToday"></span>
                </td>
                <td class="text-center">
                    of
                </td>
                <td>
                    <span data-bind="text: tasks"></span>
                </td>
            </tr>
        </tbody>
    </table>
<?php
        }

    public static function writeTasks (\Chores\Model $model)
        {
?>
    <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <td width="50%">Label</td>
                <td width="10%">Next Date</td>
                <td width="10%">Frequency</td>
                <td width="10%">Cost</td>
                <td width="10%">Actions</td>
            </tr>
        </thead>
        <tbody data-bind="foreach: selectedTasks">
            <tr>
                <td data-bind="text: label">

                </td>
                <td data-bind="css: { success : diff() < -1, danger: diff() > 3, warning: diff() > 0, info: diff()==0}">
                    <div data-bind="text: nextDate">
                    </div>
                    (<span data-bind="if: diff() > 0">overdue <span data-bind="text: diff"></span> days</span
                    ><span data-bind="if: diff() < 0">due in <span data-bind="text: diff"></span> days</span>)
                </td>
                <td>
                    every <span data-bind="text: frequency"></span> day(s)
                </td>
                <td>
                    <span data-bind="text: cost"></span>
                </td>
                <td>
                    Edit / Mark
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
    function cacheHierarchy (model)
        {
        var parents = [];
        model.hierarchyCache = {};
        model.hierarchyCache[0] = [];
        for (var i = 0; i < model.categories().length; i++)
            {
            var row = model.categories()[i];
            var rowId = ko.unwrap(row.id);
            parents[rowId] = ko.unwrap(row.parentId);
            model.hierarchyCache[rowId] = [rowId];
            }
        
        Object.keys(parents).forEach(function(key, index)
            {
            var parentId = this[key];
            while (0 !== parentId)
                {
                model.hierarchyCache[parentId].push (key);
                parentId = this[parentId];
                }
            }, parents);
        }
    function isInHierarchy (model, categoryId)
        {
        var flatCategories = model.hierarchyCache[model.selectedCategory()];
        return -1 !== flatCategories.indexOf (categoryId);
        }
    function adjustCategory (model, row)
        {
        row.navigateTo = function ()
            {
            model.selectedCategory(row.id());
            }
        }
    function adjustCategories (model)
        {
        for (var i = 0; i < model.categories().length; i++)
            {
            var row = model.categories()[i];
            adjustCategory (model, row);
            }
        }
    function initializeModel($model)
        {
        cacheHierarchy($model);
        $model.selectedCategories = ko.computed (function ()
            {
            return $.grep ($model.categories(), function (el) { return ko.unwrap(ko.unwrap(el).parentId) == $model.selectedCategory(); });
            });
        $model.selectedTasks = ko.computed (function ()
            {
            return $.grep ($model.tasks(), function (el) { return isInHierarchy($model, ko.unwrap(ko.unwrap(el).categoryId)); });
            });
        adjustCategories($model);
        $model.selectedCategory.extend({ urlSync: "s_c" });
        }
</script>
<?php
        Common::writeModelBindScripts($model->getJSON());
        }

    }
