<?php $v->other( "Header" ) ?>

  <div class="search">
<?php $v->other( "Navbar" ) ?>

    <div class="container main">
      <div class="r">
        <div class="g8 c1 m-hidden">
        </div>
        <div class="g8 c6 search-main m-w">
          <div class="r padding-3">
            <ul class="secondary-nav">
              <li class="active"><a href="#">搜索<i class="big-right"></i></a></li>
            </ul>
          </div>
          <form class="search-box g6 w" action="./">
            <div class="g10 c8">
              <input type="text" name="keyword" class="re-input re-input-l" placeholder="任务或组织名称" value="<?php $v("SEARCH.kw") ?>">
            </div>
            <div class="g10 c2 center">
              <button type="submit" class="re-button re-button-l search-button"><i class="search"></i> <span class="m-hidden">搜索</span></button>
            </div>
          </form>
          <div class="r">
            <div class="g6 h result">
              <ul class="secondary-nav">
                <li class="active single"><a href="#">任务</a></li>
              </ul>
<?php if (!empty($vr("SEARCH.tasks"))): ?>
              <ul class="task-result">
<?php foreach ($vr("SEARCH.tasks") as $task): ?>
                <li class="task" data-task-id="<?php $e($task, "task_id") ?>">
                  <a href="<?php $v("SYS_CFG.site_url") ?>task/<?php $e($task, "task_id") ?>" class="name"><?php $e($task, "name") ?></a>
                  <p class="description"><?php $e($task, "description", 1, 1) ?></p>
                </li>
<?php endforeach; ?>
              </ul>
<?php else: ?>
              <span class="no-result">没有找到结果。</span>
<?php endif; ?>
              <a href="" class="load-more load-tasks<?php if (count($vr("SEARCH.tasks")) < 20) $e(" hidden") ?>">加载更多“任务”的搜索结果···</a>
            </div>
            <div class="g6 h result">
              <ul class="secondary-nav">
                <li class="active single"><a href="#">组织</a></li>
              </ul>
<?php if (!empty($vr("SEARCH.organizations"))): ?>
              <ul class="organization-result">
<?php foreach ($vr("SEARCH.organizations") as $organization): ?>
                <li class="organization" data-organization-id="<?php $e($organization, "organization_id") ?>">
                  <a href="<?php $v("SYS_CFG.site_url") ?>organization/<?php $e($organization, "organization_id") ?>" class="name"><?php $e($organization, "name") ?></a>
                  <p class="description"><?php $e($organization, "description", 1, 1) ?></p>
                </li>
<?php endforeach; ?>
              </ul>
<?php else: ?>
              <span class="no-result">没有找到结果。</span>
<?php endif; ?>
              <a href="" class="load-more load-organizations<?php if (count($vr("SEARCH.organizations")) < 20) $e(" hidden") ?>">加载更多“组织”的搜索结果···</a>
            </div>
          </div>
        </div>
      </div>
    </div>


<?php $v->other("FooterNav") ?>
  </div>

<!-- 模板 + 其他 -->

  <script type="text/template" id="template-search-item-o">
    <li class="organization" data-organization-id="<%= organization_id %>">
      <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= organization_id %>" class="name"><%- name %></a>
      <p class="description"><% print(_s.xescape(description)) %></p>
    </li>
  </script>

  <script type="text/template" id="template-search-item-t">
    <li class="task" data-task-id="<%= task_id %>">
      <a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<%= task_id %>" class="name"><%- name %></a>
      <p class="description"><% print(_s.xescape(description)) %></p>
    </li>
  </script>

<?php $t("CreateDialog") ?>

<?php $t("MessageDialog") ?>

<?php $t("Footer") ?>