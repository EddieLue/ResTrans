<?php $v->other( "Header" ) ?>

  <div class="task">

<?php $v->other( "Navbar" ) ?>

    <div class="container main">
      <div class="r">

        <div class="task-right g6 c4 m-w pc-pull-right m-pull-left">
          <div class="secondary-nav padding-3">
            <li class="active"><a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("TASK.task_id") ?>">文件集<i class="big-right"></i></a></li>
<?php if ($vr("ORG.access_setting_pages")): ?>              <li class=""><a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("TASK.task_id") ?>/setting/">选项</a></li><?php endif; ?>
          </div>
          <div class="secondary-nav padding-3">
<?php $currentSet = $vr("TASK.current_set") ?>
<?php 

foreach ( $vr("TASK.sets") as $set ): ?>
            <li class="<?php $set->set_id === $currentSet->set_id && $e("single") ?>"><a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("TASK.task_id") ?>/set/<?php $e($set, "set_id") ?>"><?php $e($set, "name") ?>(<?php $e($set, "file_total") ?>)</a></li>
<?php endforeach; ?>
            <li class="create-set<?php $a("TASK.create_set", "", " hidden") ?>"><a href="">创建文件集···</a></li>
          </div>
          <div class="r padding-3">
<?php if ($currentSet): ?>
            <table class="re-table g6 w files">
              <thead>
                <tr class="r">
                  <td class="g8 h">
                    <button class="re-button re-button-xs re-button-primary open"<?php $a("TASK.open_wt", "", " disabled") ?>>在工作台打开<i class="right"></i></button>
                  </td>
                  <td></td>
                  <td colspan="2" class="g8 c1 dangerous-operation">
                    <button class="re-button re-button-xs re-button-warning delete-set<?php $a("TASK.create_set", "", " hidden") ?>"><i class="delete"></i>文件集</button>
                  </td>
                </tr>
              </thead>
              <tbody>
<?php if ( $currentSet->file_total ): ?>
<?php foreach ($vr("Set.files") as $file): ?>
                <tr class="task-file r">
                  <td class="g8 h file-title">
                    <div class="language-indicator" title="文件的语言设置。">
                      <span class="original-language"><?php $e($file, "original_language_name") ?></span>
                      <span class="target-language"><?php $e($file, "target_language_name") ?></span>
                    </div>
                    <a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("TASK.task_id") ?>/set/<?php $e($file, "set_id") ?>/file/<?php $e($file, "file_id") ?>/preview/" class="file-name" title="打开预览">
                      <?php $s($file->name, 12) ?>
                      <span class="ext">.<?php $e($file, "ext") ?></span>
                    </a>
                  </td>
                  <td class="g8 c2 file-desc">
                    <span class="file-info"><a class="author" href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $e($file, "last_contributor") ?>" title="最后更新者"><?php $e($file, "last_update_by") ?></a><span class="datetime" title="最后更新时间"><?php $e($file, "last_update") ?></span></span>
                  </td>
                  <td class="g8 c1 file-size">
                    <div class="size"><?php $e($file, "size_kb") ?>K</div>
                    <div class="prop">大小</div>
                  </td>
                  <td class="g8 c1 file-lines">
                    <div class="lines"><?php $e($file, "line") ?></div>
                    <div class="prop">行</div>
                  </td>
                </tr>
<?php endforeach; ?>
<?php else: ?>
                <tr class="task-file r">
                  <td class="g8 h file-title">
                    <p>这个集没有文件。<br>要上传文件，请先在工作台中打开。</p>
                  </td>
                  <td></td><td></td><td></td>
                </tr>
<?php endif; ?>
              </tbody>
            </table>
<?php else: ?>
  <?php $a(
    "TASK.create_set",
    "            <p class=\"no-set\">没有文件集，创建一个新的文件集来存放文件。</p>",
    "            <p class=\"no-set\">这个任务暂时没有文件集。</p>");
   ?>
<?php endif; ?>
          </div>
        </div>
<?php $v->other( "TaskLeft" ) ?>
      </div>
    </div>

<?php $v->other( "FooterNav" ) ?>

  </div>

<!-- 模板 + 其他 -->
  <div class="re-dialog create-set-dialog" style="display:none">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 r m-w">
          <form action="#" class="create-set">
            <div class="g10 c7">
              <input type="text" class="re-input width100 no-margin set-name" placeholder="文件集名称">
            </div>
            <div class="g10 c3 center">
              <button type="submit" class="re-button re-button-primary">创建集</button>
              <button type="button" class="re-button close-dialog">关闭</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="re-dialog delete-set-dialog hidden">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
          <p class="g10 c7">
              删除当前文件集？<br>文件集中的文件项目、翻译和原始文件也会被删除，且无法恢复。
          </p>
          <div class="g10 c3 center">
            <button type="submit" class="re-button re-button-warning delete">确定删除</button>
            <button type="button" class="re-button close-dialog close">取消</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="re-dialog open-worktable hidden">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
            <p class="g10 c7 center">
                正在准备工作台···
            </p>
            <div class="g10 c3 center">
              <i class="loading"></i>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php $v->other( "CreateDialog" ) ?>

<?php $v->other( "MessageDialog" ) ?>

<?php $v->other( "Footer" ) ?>