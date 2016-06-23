<?php $v->other("Header") ?>

  <div class="organization">

<?php $v->other("Navbar") ?>

    <div class="container main">
      <div class="r">

        <div class="g10 c7 organization-right m-w pc-pull-right m-pull-left">
          <div class="r">
            <ul class="secondary-nav">
              <li>
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>">讨论(<?php $v("ORG.discussion_total") ?>)</a>
              </li>
              <li class="active">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/task/">任务(<?php $v("ORG.task_total") ?>)<i class="big-right"></i></a>
              </li>
              <li>
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/user/">成员(<?php $v("ORG.member_total") ?>)</a>
              </li>
<?php if ($vr("ORG.access_setting_pages")): ?>              <li class="">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/setting/">选项</a>
              </li><?php endif; ?>
            </ul>
          </div>
          <div class="r">
            <table class="re-table g6 w organization-tasks">
              <thead>
                <tr>
                  <th class="g10 c8">任务摘要</th>
                  <th class="g10 c2">工作进度</th>
                </tr>
              </thead>
              <tbody>
                <tr class="<?php if($v("ORG.task_total", false, false) && count($vr("ORG.tasks"))): $v->e("hidden"); endif; ?>">
                  <td class="g10 c8">
                    <p>这里没有任务。</p>
                  </td>
                  <td class="g10 c2"></td>
                </tr>
<?php foreach ($vr("ORG.tasks") as $task) :?>
                <tr>
                  <td class="g10 c8 task-summary" data-task-id="<?php $e($task, "task_id") ?>">
                    <div class="language-indicator">
                      <div class="original-language"><?php $e($task, "original_language_name") ?></div>
                      <div class="target-language"><?php $e($task, "target_language_name") ?></div>
                    </div>
                    <a href="<?php $v("SYS_CFG.site_url");?>task/<?php $e($task, "task_id") ?>" class="title"><?php $e($task, "name") ?></a>
                    <p class="description"><?php $e($task, "description", 1) ?></p>
                    <div class="meta">
                      <a href="<?php $v("SYS_CFG.site_url");?>profile/<?php $e($task, "user_id") ?>" class="publisher"><?php $e($task, "user_name") ?></a>
                      <span class="datetime">创建于 <?php $e($task, "friendly_time") ?></span>
                    </div>
                  </td>
                  <td class="g10 c2 task-percentage">
                    <div class="percent"><?php $v->e($task, "percentage") ?>%</div>
                    <div class="prop">已完成</div>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="<?php if($vr("ORG.task_total") <= count($vr("ORG.tasks")) || !count($vr("ORG.tasks"))): $e("hidden"); endif; ?>">
                  <td></td>
                  <td>
                    <div class="r">
                      <button class="re-button re-button-xs page-forward">更多···</button>
                    </div>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
<?php $v->other("OrgLeft") ?>
      </div>
    </div>

<?php $v->other("FooterNav") ?>
  </div>
<!-- 模板 + 其他 -->
<?php $v->other("OrgExit") ?>
  <script type="text/template" id="template-task">
    <tr>
      <td class="g10 c8 task-summary" data-task-id="<%= task_id %>">
        <div class="language-indicator">
          <div class="original-language"><%- original_language_name %></div>
          <div class="target-language"><%- target_language_name %></div>
        </div>
        <a href="<?php $v("SYS_CFG.site_url", 0);?>task/<%= task_id %>" class="title"><%- name %></a>
        <p class="description"><%- description %></p>
        <div class="meta">
          <a href="<?php $v("SYS_CFG.site_url", 0);?>profile/<%= user_id %>" class="publisher"><%- user_name %></a>
          <span class="datetime">创建于 <%- friendly_time %></span>
        </div>
      </td>
      <td class="g10 c2 task-percentage">
        <div class="percent"><%- percentage %>%</div>
        <div class="prop">已完成</div>
      </td>
    </tr>
  </script>

<?php $v->other("CreateDialog") ?>

<?php $v->other("MessageDialog") ?>

<?php $v->other("Footer") ?>