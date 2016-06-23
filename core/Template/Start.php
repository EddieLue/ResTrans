<?php
  $v->other( "Header" );
  $tasks = $vr("HOME.tasks");
?>

  <div class="list">
<?php $v->other( "Navbar" ) ?>

    <div class="container main">
      <div class="r">
        <div class="g8 c1 start-left m-hidden">
        </div>
        <div class="g8 c6 m-w start-right">
          <div class="r">
            <ul class="secondary-nav">
              <li class="active"><a href="#">任务动态<i class="big-right"></i></a></li>
            </ul>
          </div>
          <div class="r">
            <table class="re-table g6 w tasks">
              <thead>
                <tr>
                  <th class="g10 c6">任务摘要</th>
                  <th class="g10 c2 m-hidden">默认语言</th>
                  <th class="g10 c2">完成率</th>
                </tr>
              </thead>
              <tbody>
<?php if (!empty($tasks)): foreach($tasks as $task): ?>
                <tr data-task-id="<?php $e($task, "task_id") ?>">
                  <td class="g10 c6 task-summary">
                    <a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $e($task, "task_id") ?>" class="title"><?php $e($task, "name") ?></a>
                    <div class="description"><?php $e($task, "description", 1) ?></div>
                    <div class="publish-info">
                      <div class="info">
                        <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $e($task, "organization_id") ?>" class="m-hidden"><?php $e($task, "organization_name") ?></a>
                        <span class="m-hidden">&raquo;</span>
                        <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $e($task, "user_id") ?>"><?php $e($task, "user_name") ?></a>
                      </div>
                      <div class="datetime">创建于 <?php $e($task, "friendly_time") ?></div>
                    </div>
                  </td>
                  <td class="g10 c2 task-language m-hidden">
                    <span class="original-language" title="原始语言（默认）"><?php $e($task, "original_language_name") ?></span>
                    <span title="目标语言（默认）"><?php $e($task, "target_language_name") ?></span>
                  </td>
                  <td class="g10 c2 task-percentage">
                    <div class="percent"><?php $e($task, "percentage") ?>%</div>
                    <div class="prop">已完成</div>
                  </td>
                </tr>
                <?php endforeach; else: ?><tr>
                  <td class="g10 c7 no-data"><p>没有任务。<br>在组织中点击“新的工作” - “新建任务”来开始。</p></td>
                  <td></td>
                </tr><?php endif; ?>
              </tbody>
              <tfoot<?php if (!$tasks || count($tasks) < 20) $e(" class=\"hidden\"", null, 0) ?>><tr>
                  <td></td><td class="m-hidden"></td>
                  <td>
                    <div class="r">
                      <button class="re-button re-button-xs page-forward">加载更多···</button>
                    </div>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

<?php $v->other( "FooterNav" ) ?>
  </div>

<!-- 模板 + 其他 -->

  <script type="text/template" id="template-start-task">
    <tr data-task-id="<%= task_id %>">
      <td class="g10 c6 task-summary">
        <a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<%= task_id %>" class="title"><%- name %></a>
        <div class="description"><%- description %></div>
        <div class="publish-info">
          <div class="info">
            <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= organization_id %>"><%- organization_name %></a>
            <span>&raquo;</span>
            <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= user_id %>"><%- user_name %></a>
          </div>
          <div class="datetime">创建于 <%- friendly_time %></div>
        </div>
      </td>
      <td class="g10 c2 task-language m-hidden">
        <span class="original-language" title="原始语言（默认）"><%- original_language_name %></span>
        <span title="目标语言（默认）"><%- target_language_name %></span>
      </td>
      <td class="g10 c2 task-percentage">
        <div class="percent"><%- percentage %>%</div>
        <div class="prop">已完成</div>
      </td>
    </tr>
  </script>

<?php $v->other( "CreateDialog" ) ?>

<?php $v->other( "MessageDialog" ) ?>

<?php $v->other( "Footer" ) ?>