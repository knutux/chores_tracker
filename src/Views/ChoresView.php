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
    const ID_EDIT_TASKS_DLG = 'edit-task-dialog';
    
    public static function write (\Chores\Model $model)
        {
?>
<div class="content-fluid">
    <?= Common::writeBoundErrors()?>
    <div data-bind="if:'category'==mode()">
    <?=self::writeTabsHeader($model)?>
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
    <div data-bind="if: 'categories'==selectedTab()">
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
    </div>
<?php
        }

    public static function writeTabsHeader (\Chores\Model $model)
        {
?>
    <div data-bind="if: selectedCategory() != 0" class="clearfix">
        <div class="pull-right"><button class="btn btn-light" data-bind="click: function () { selectedCategory(0); selectedTab('categories'); }">Top Categories</button></div>
    </div>
    <ul class="nav nav-tabs">
      <li data-bind="css: { active: 'categories'==selectedTab() }, click: function () { selectedTab('categories'); }" class="nav-link">
          <a href="#">
              <i class="fa fa-tags"></i>
              Subcategories
              (<span data-bind="text: selectedCategories().length"></span>)</a>
      </li>
      <li data-bind="css: { active: 'tasks'==selectedTab() }, click: function () { selectedTab('tasks'); }" class="nav-link">
          <a href="#">
              <i class="fa fa-th-list"></i>
              Tasks
              (<span data-bind="text: selectedTasks().length"></span>)</a>
      </li>
    </ul>
<?php
        }

    public static function writeTasks (\Chores\Model $model)
        {
?>
    <div data-bind="if: 'tasks'==selectedTab()">
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
                <td data-bind="css: { 'bg-success' : diff() < -1, 'bg-danger': diff() > 3, 'bg-warning': diff() > 0, 'bg-info': diff()==0}"  class="text-center">
                    <div data-bind="text: nextDate">
                    </div>
                    (<span data-bind="if: diff() > 0">overdue <span data-bind="text: diff"></span> days</span
                    ><span data-bind="if: diff() < 0">due in <span data-bind="text: diff"></span> days</span
                    ><span data-bind="if: diff() == 0">due today</span>)
                </td>
                <td class="text-right">
                    every <span data-bind="text: frequency"></span> day(s)
                </td>
                <td class="text-right">
                    <div data-bind="if: cost() > 0"><span data-bind="text: cost"></span> min.</div>
                    <div data-bind="if: cost() == 0" class="text-muted">N/A</div>
                </td>
                <td class="btn-group" role="group" aria-label="Actions">
                    <button role="button" data-bind="click:markDone, disabled: executing, css: {disabled: executing}" class="btn btn-success">
                        <span data-bind="ifnot:executing"><i class="fa fa-check"></i></span></span>
                        <span data-bind="if:executing"><i class="fa fa-spinner fa-spin"></i></span>
                        Done
                    </button>
                    <div class="btn-group" role="group">
                        <button id="btnGroupDrop1" data-bind="disabled: executing, css: {disabled: executing}" type="button" class="btn btn-light dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <span data-bind="ifnot:executing"><i class="fa fa-ellipsis-h"></i></span></span>
                          <span data-bind="if:executing"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="btnGroupDrop1">
                          <a class="dropdown-item" href="#" data-toggle="modal" data-target="#<?=self::ID_EDIT_TASKS_DLG?>" data-bind="click: edit">
                              <span data-bind="ifnot:executing"><i class="fa fa-pencil"></i></span></span>
                              <span data-bind="if:executing"><i class="fa fa-spinner fa-spin"></i></span>
                              Edit
                          </a>
                          <a class="dropdown-item" href="#" data-bind="click:markDoneYesterday">
                              <span data-bind="ifnot:executing"><i class="fa fa-check"></i></span></span>
                              <span data-bind="if:executing"><i class="fa fa-spinner fa-spin"></i></span>
                              yesterday
                          </a>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    </div>
<?php
        }

    public static function writeEditTaskDialog (\Chores\Model $model) : \stdClass
        {
        return Common::writeEditForm(self::ID_EDIT_TASKS_DLG, $model->getTaskPropertyMap());
        }

    public static function writeScripts (\Chores\Model $model)
        {
        Common::writeCommonScripts();
        $initTasksDialog = self::writeEditTaskDialog($model);
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
        var sel = model.selectedCategory();
        if (!(sel in model.hierarchyCache))
            return false;
        var flatCategories = model.hierarchyCache[sel];
        return -1 !== flatCategories.indexOf (categoryId);
        }
    function adjustCategory (model, row)
        {
        row.navigateTo = function ()
            {
            model.selectedCategory(row.id());
            if (0 == row.id())
                model.selectedTab('categories');
            else
                model.selectedTab('tasks');
            }
        }
    function adjustTask (model, row)
        {
        row.executing = ko.observable(false);
        var updateRow = function (newData)
            {
            var newRow = newData.row;
            for (var i = 0; i < newData.props.length; i++)
                {
                var prop = newData.props[i];
                row[prop](newRow[prop]);
                }
            } 
        row.edit = function ()
            {
            <?=$initTasksDialog->editFn?>(model, 'Edit task ' + row.label(), 'edit:task', 'Update', row, updateRow);
            }
        row.markDone = function ()
            {
            ajaxCall(model.baseUrl, 'done', { id: row.id(), today: 1 }, model, row.executing, updateRow);
            }
        row.markDoneYesterday = function ()
            {
            ajaxCall(model.baseUrl, 'done', { id: row.id(), today: 0 }, model, row.executing, updateRow);
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
    function adjustTasks (model)
        {
        for (var i = 0; i < model.tasks().length; i++)
            {
            var row = model.tasks()[i];
            adjustTask (model, row);
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
            var filter = function (el) { return isInHierarchy($model, ko.unwrap(ko.unwrap(el).categoryId)); };
            return $.grep ($model.tasks(), filter).sort(function (a, b) { return b.diff() - a.diff(); });
            });
        adjustCategories($model);
        adjustTasks($model);
        $model.selectedCategory.extend({ urlSync: "s_c" });
        $model.selectedTab = ko.observable('categories').extend({ urlSync: "t_b" });
        <?=$initTasksDialog->initFn?>($model);
        }
</script>
<?php
        Common::writeModelBindScripts($model->getJSON());
        }

    }
