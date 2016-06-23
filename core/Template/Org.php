<?php 
  $v->other( "Header" );
  $organizations = $vr("HOME.organizations");
?>

  <div class="organizations">
<?php $v->other( "Navbar" ) ?>

    <div class="container main">
      <div class="r">
        <div class="g8 c1 organizations-left m-hidden">
        </div>
        <div class="g8 c6 m-w organizations-right">
          <div class="r padding-3">
            <div class="secondary-nav">
              <li class="active"><a href="#">所有<i class="big-right"></i></a></li>
            </div>
          </div>
          <div class="r padding-3">
            <table class="re-table g6 w organizations">
              <thead>
                <tr>
                  <th class="g10 c6">组织摘要</th>
                  <th class="g10 c2">当前 / 最大成员数</th>
                  <th class="g10 c2">任务总计</th>
                </tr>
              </thead>
              <tbody>
<?php if (!empty($organizations)): foreach($organizations as $organization): ?>
                <tr data-organization-id="<?php $e($organization, "organization_id") ?>">
                  <td class="g10 c6 organization-describe">
                    <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $e($organization, "organization_id") ?>" class="name"><?php $e($organization, "name") ?></a>
                    <p class="description"><?php $e($organization, "description", 1) ?></p>
                  </td>
                  <td class="g10 c2 organization-member">
                    <div class="value"><?php $e($organization, "member_total") ?> / <?php $e($organization, "maximum") ?></div>
                  </td>
                  <td class="g10 c2 organization-task">
                    <div class="value"><?php $e($organization, "task_total") ?></div>
                  </td>
                </tr>
<?php endforeach; else: ?><tr>
                <tr>
                  <td><p>没有任何组织。<br>点击“新的工作” - “创建组织”来开始。</p></td>
                </tr>
<?php endif; ?>
              </tbody>
              <tfoot>
                <tr>
                <td>
                  <span class="tips">列表中不会显示访问权限设置为“不可见”的组织（包括你加入的）。</span>
                </td>
                <td></td>
                  <td>
                    <div class="r">
                      <button class="re-button re-button-xs page-forward<?php if (count($organizations) < 20) $e(" hidden", null, 0) ?>">加载更多···</button>
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

  <script type="text/template" id="template-organization">
    <tr data-organization-id="<%= organization_id %>">
      <td class="g10 c6 organization-describe">
        <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= organization_id %>" class="name"><%- name %></a>
        <p class="description"><%- description %></p>
      </td>
      <td class="g10 c2 organization-member">
        <div class="value"><%= member_total %> / <%= maximum %></div>
      </td>
      <td class="g10 c2 organization-task">
        <div class="value"><%= task_total %></div>
      </td>
    </tr>
  </script>

<?php $v->other( "CreateDialog" ) ?>

<?php $v->other( "MessageDialog" ) ?>

<?php $v->other( "Footer" ) ?>