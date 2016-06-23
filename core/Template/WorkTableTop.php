<?php
  $lastSet = $v("WORKTABLE.last_set", false, false);
  $lastSetFiles = $v("WORKTABLE.last_set_files", false, false);
  $lastFile = $vr("WORKTABLE.last_file") ? $vr("WORKTABLE.last_file") : new stdClass();
  $allSets = $vr("WORKTABLE.sets");
  $current = $v("WORKTABLE.current", false, false);
?>
    <div class="top">
      <div class="nav">
        <span class="file-language m-hidden" href="" title="蓝：此文件的原始语言 / 灰：此文件的目标语言">
          <span class="original-language"><?php $v->e($lastFile, "original_language_name") ?></span>
          <span class="target-language"><?php $v->e($lastFile, "target_language_name") ?></span>
        </span>
        <input type="text" class="current-file" value="<?php $v->e($lastFile, "name") ?>" placeholder="文件名（无需后缀）" autocomplete="off" autosave="off">
        <div class="sidebar-main fm-main">
          <div class="main-top">
            <div class="task-belong">
              <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $e($current, "organization_id") ?>" class="group"><?php $v->e($current, "organization_name") ?></a>
              <span>&raquo;</span>
              <a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $e($current, "task_id") ?>" class="this-task"><?php $v->e($current, "task_name") ?></a>
            </div>
          </div>
          <div class="outside">
            <div class="single select-set">
              <a href="" class="current-set" data-current-set-id="<?php $v->e($lastSet, "set_id") ?>">
                <?php if ($lastSet): $v->e($lastSet->name); ?>
                <i class="down-arrow"></i>
                <?php else: $v->e("无文件集"); endif; ?>
              </a>
              <ul class="set-list">
<?php if ($allSets): foreach ($allSets as $set): ?>
                <li class="set" data-set-id="<?php $v->e($set, "set_id"); ?>"><a href=""><?php $v->e($set, "name"); ?></a></li>
<?php endforeach; endif; ?>
              </ul>
            </div>
            <div class="upload-files<?php $a("WORKTABLE.allow_manage_files", "", " hidden") ?>">
              <div class="select-files">选择文件</div>
              <input type="file" class="select-button" multiple accept=".txt" title="支持TXT 文本文档（目前）。">
            </div>
          </div>
          <div class="files-options r">
            <a href="" class="create-set g6 c2">新建集</a>
            <a href="" class="refresh-set-list g6 c2">更新集</a>
            <a href="" class="refresh-file-list g6 c2">更新文件</a>
          </div>
          <div class="file-list">
            <ul class="files">
              <li class="no-files hidden">
                <p class="tips">这个文件集没有任何文件。</p>
              </li>
<?php if ($lastSetFiles): foreach ($lastSetFiles as $file): ?>
              <li class="file" data-file-id="<?php $v->e($file,"file_id") ?>">
                <div class="file-meta" href="">
                  <a class="name" href=""><?php $s($file->name, 10) ?><span class="file-info">.<?php $v->e($file,"ext") ?> / <?php $v->e($file,"line") ?> (<?php $v->e($file,"percentage") ?>%)</span></a>
                  <span class="last-update m-hidden"><span class="time"><?php $v->e($file, "last_update") ?></span> <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $e($file, "last_contributor") ?>" class="user"><?php $v->e($file,"last_update_by") ?></a></span>
                </div>
                <div class="file-options">
                  <button class="re-button re-button-xs download"><i class="download"></i>下载</button>
                  <button class="re-button re-button-xs re-button-warning delete<?php if (!$vr("WORKTABLE.allow_manage_files") || $vr("TASK.frozen")) echo " hidden"; ?>"><i class="delete"></i>删除</button>
                </div>
              </li>
<?php endforeach; endif; ?>
            </ul>
          </div>
          <a href="" class="toggle toggle-sidebar">文件管理器</a>
        </div>
        <div class="sidebar-main glossary-main hidden">
          <span class="title">术语表</span>
          <span class="intro">管理一组原文和译文的对照，这将帮助你在工作中快速插入对应的翻译。</span>
          <ul class="glossary-list">
            <li class="glossary">
              <form action="#" method="POST" class="r">
                <span class="original g10 c4"><input type="text" class="re-input re-input-xs" placeholder="原文"></span>
                <span class="target g10 c4"><input type="text" class="re-input re-input-xs" placeholder="对应的翻译"></span>
                <span class="options g10 c2"><button class="re-button re-button-xs re-button-success">保存</button></span>
              </form>
            </li>
            <li class="glossary r">
              <span class="original g10 c4">text</span>
              <span class="target g10 c4">文本</span>
              <span class="options g10 c2"><button class="re-button re-button-xs re-button-warning">删除</button></span>
            </li>
          </ul>
          <a href="" class="toggle toggle-sidebar">术语表</a>
        </div>
        <div class="sidebar-main cm-main hidden">
          <span class="title">修改管理器</span>
          <span class="intro">管理被增加或删除的原文，你可以在稍后将其保存在另一文件中。</span>
          <div class="change-options">
             <button class="re-button re-button-warning re-button-xs change-clear">清空列表</button>
            <button class="re-button re-button-success re-button-xs change-save">保存为…</button>
          </div>
          <table class="re-table g6 w change-list">
            <thead>
              <tr>
                <th class="g10 c6">原文</th>
                <th class="g10 c2">位置</th>
                <th class="g10 c2"></th>
              </tr>
            </thead>
            <tbody>
              <tr class="change">
                <td class="original g10 c6"></td>
                <td class="pos g10 c2">116和n1之间</td>
                <td class="options g10 c2">
                  <button class="re-button re-button-xs re-button-warning">撤销</button>
                </td>
              </tr>
              <tr class="change">
                <td class="g10 c6"><span class="delete">删除461行</span></td>
                <td class="g10 c2"></td>
                <td class="g10 c2 options">
                  <button class="re-button re-button-xs re-button-warning">撤销</button>
                </td>
              </tr>
            </tbody>
          </table>
          <table class="re-table g6 w change-list">
            <thead>
              <tr>
                <th class="g10 c6">增删操作面板</th>
                <th class="g10 c2"></th>
                <th class="g10 c2"></th>
              </tr>
            </thead>
            <tbody>
              <tr class="change">
                <td class="g10 c6 original">
                  <textarea class="re-textarea" rows="3" placeholder="原文（自动分行）"></textarea>
                </td>
                <td class="pos g10 c2">
                  <input type="text" class="re-input re-input-xs" placeholder="在某行后" title="如你要在第一行之前插入新行，请输入“0”，你也可以输入“n”+虚拟行号。">
                </td>
                <td class="options g10 c2">
                  <button class="re-button re-button-xs re-button-primary">应用</button>
                </td>
              </tr>
              <tr class="change">
                <td class="g10 c6 original">
                  <span>右侧输入你要删除的行。</span>
                </td>
                <td class="pos g10 c2">
                  <input type="text" class="re-input re-input-xs" placeholder="要删除的行" title="输入要删除的真实行号，你也可以输入“n”+虚拟行号。">
                </td>
                <td class="options g10 c2">
                  <button class="re-button re-button-xs re-button-primary">应用</button>
                </td>
              </tr>
            </tbody>
          </table>
          <a href="" class="toggle toggle-sidebar">修改管理器</a>
        </div>
      </div>
      <div class="tools">
        <ul class="toolkit">
          <li class="tool dropdown tool-translate">
            <a href="" class="toggle">
              <i class="menu"></i>
              <span class="m-hidden">工具</span>
              <i class="down-arrow m-hidden"></i>
            </a>
            <ul class="subtools">
              <li class="subtool refresh-line"><a href="">刷新</a></li>
              <li class="subtool goto-line"><a href="">转到行</a></li>
            </ul>
          </li>
          <li class="tool dropdown tool-global">
            <a href="" class="toggle">
              <i class="setting"></i>
              <span class="m-hidden">通用</span>
              <i class="red-point<?php $a("MESSAGE.point", "", " hidden") ?>"></i>
              <i class="down-arrow m-hidden"></i>
            </a>
            <ul class="subtools">
              <li class="subtool message">
                <a href="">私信<i class="red-point<?php $a("MESSAGE.point", "", " hidden") ?>"></i></a>
              </li>
              <li class="subtool"><a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $v("USER.user_id") ?>">个人资料</a></li>
              <li class="subtool"><a href="<?php $v("SYS_CFG.site_url", 0) ?>setting/personal/">设置</a></li>
              <li class="subtool"><a href="#logout" class="logout">退出</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>