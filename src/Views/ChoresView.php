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
        <div id="categories-with-tasks" class="row">
            <div class="col-sm-3 col-md-2">
                <?=self::writeCategories($model)?>
            </div>
            <div class="col-sm-9 col-md-10">
                <?=self::writeTasks($model)?>
            </div>
        </div>
    </div>
</div>
<?=self::writeScripts($model)?>
<?=self::writeTemplates($model)?>

<?php
        }

    public static function writeCategories (\Chores\Model $model)
        {
?>
    <div class="btn-group btn-group-sm d-flex justify-content-center" role="group" aria-label="Category actions">
        <button role="button" disabled class="btn btn-secondary"><i class="fa fa-plus"></i> Add new</button>
        <button role="button" data-bind="attr: { disabled: selectedCategory() == 0 }, click: function () { selectedCategory(0); }" class="btn btn-secondary"><i class="fa fa-home"></i> Home</button>
    </div>
    <hr/>
    <ul class="list-group" data-bind="template: { name: 'category-tree', data: { list: topLevelCategories, root: $data, indent: 0 } }" ></ul>
    
<?php
        }

    public static function writeTasks (\Chores\Model $model)
        {
?>
    <div class="clearfix">
      <div class="btn-group btn-group-sm d-flex justify-content-end float-right" role="group" aria-label="Task actions">
        <input type="text" class="form-control form-control-sm" id="search-input" placeholder="Search..." data-bind="value: textFilter">
        <button role="button" data-bind="attr: { disabled: selectedCategory() == 0 }, click: createTask" class="btn btn-secondary"><i class="fa fa-plus"></i> Add new</button>
      </div>
    </div>
    <hr/>
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
                <td data-bind="css: { 'bg-success' : diff() < -1, 'bg-danger': diff() > 3, 'bg-warning': diff() > 0, 'bg-secondary': diff()==0, 'bg-info': diff()==-1}"  class="text-center">
                    <div data-bind="text: nextDate">
                    </div>
                    (<span data-bind="if: diff() > 0">overdue <span data-bind="text: diff"></span> days</span
                    ><span data-bind="if: diff() < -1">due in <span data-bind="text: Math.abs(diff())"></span> days</span
                    ><span data-bind="if: diff() == -1">due tomorrow</span
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
<?php
        }

    public static function writeEditTaskDialog (\Chores\Model $model) : \stdClass
        {
        return Common::writeEditForm(self::ID_EDIT_TASKS_DLG, $model->getTaskPropertyMap());
        }

    public static function writeTemplates (\Chores\Model $model)
        {
?>
    <script type="text/html" id="category-tree">
        <!-- ko foreach: list -->
         <li menuid="" role="button" data-bind="click: navigateTo, css: {active: id()==$parent.root.selectedCategory()}" class="list-group-item list-group-item-action d-flex justify-content-between">
            <span>
              <span data-bind="style: { 'margin-left' : ($parent.indent * 20)+'px'}, click: toggle">
                <span  data-bind="if: subcategories().length == 0"><i class="fa fa-tag"></i></span>
                <span  data-bind="if: subcategories().length > 0 && isExpanded()"><i class="fa fa-minus"></i></span>
                <span  data-bind="if: subcategories().length > 0 && !isExpanded()"><i class="fa fa-plus"></i></span>
              </span>
              <span data-bind="text: label"></span>
            </span>
            <span class="badge" data-bind="css: { 'badge-danger': pendingToday() > 0, 'badge-primary' : pendingToday() == 0}">
                <span data-bind="text: pendingToday"></span>
            </span>
         </li>
         <div data-bind="if: isExpanded">
            <div class="list-group" data-bind="template: { name: 'category-tree', data: { list: subcategories, root: $parent.root, indent: $parent.indent + 1 } }" />
         </div>
         <!-- /ko -->
     </script>
<?php
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
                model.hierarchyCache[parentId].push (parseInt(key));
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
            }
        row.subcategories = ko.computed (function ()
            {
            var ret = $.grep (model.categories(), function (el) { return ko.unwrap(ko.unwrap(el).parentId) == row.id(); });
            return ret;
            });
        row.isExpanded = ko.observable(model.selectedCategory() == row.id());
        row.toggle = function ()
            {
            if (row.subcategories().length)
                row.isExpanded(!row.isExpanded());
            };
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
        $model.textFilter = ko.observable("");
        var addTask = function (newData)
            {
            var newRow = ko.mapping.fromJS(newData.row);
            adjustTask ($model, newRow);
            $model.tasks().push (newRow)
            };

        $model.createTask = function ()
            {
            if ($model.selectedCategory() == 0)
                return;
            
            <?=$initTasksDialog->editFn?>($model, 'Add new task', 'create:task', 'Create', { id: $model.selectedCategory() }, addTask);
            };
        $model.selectedCategories = ko.computed (function ()
            {
            return $.grep ($model.categories(), function (el) { return ko.unwrap(ko.unwrap(el).parentId) == $model.selectedCategory(); });
            });
        $model.selectedTasks = ko.computed (function ()
            {
            var filter = function (el)
                {
                var cat = ko.unwrap(ko.unwrap(el).categoryId);
                if ($model.selectedCategory() == cat)
                    return true;
                if ($model.textFilter().length == 0 && el.diff() < -1)
                    return false;
                var inHierarchy = 0 == $model.selectedCategory() || isInHierarchy($model, cat);
                if (!inHierarchy)
                    return false;
                var label = el.label();
                if (null === label)
                    return false;
                return label.toLowerCase().indexOf ($model.textFilter().toLowerCase()) != -1;
                }
            return $.grep ($model.tasks(), filter); //.sort(function (a, b) { return b.diff() - a.diff(); });
            });
        adjustCategories($model);
        adjustTasks($model);
        $model.topLevelCategories = ko.computed (function ()
            {
            return $.grep ($model.categories(), function (el) { return ko.unwrap(ko.unwrap(el).parentId) == 0; });
            });
        $model.selectedCategory.extend({ urlSync: "s_c" });
        <?=$initTasksDialog->initFn?>($model);
        }
</script>
<?php
        Common::writeModelBindScripts($model->getJSON());
        }

    }
