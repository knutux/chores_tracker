<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Chores\Views;

/**
 * Description of ImportView
 *
 * @author knutux
 */
class ImportView {
    public static function write (\Chores\Model $model)
        {
?>
<div class="content-fluid">
    <?= Common::writeBoundErrors()?>
    <?= Common::writeBoundMessages('messages')?>
</div>
<?=self::writeScripts($model)?>
<?php
        }

    public static function writeScripts (\Chores\Model $model)
        {
?>
<script>
    function initializeModel($model)
        {
        }
</script>
<?php
        Common::writeModelBindScripts($model->getJSON());
        }
        
    }
