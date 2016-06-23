<?php $t("Header") ?>

  <div class="profile">

<?php $t("Navbar") ?>

    <div class="container main">
<?php $t("UserProfileTop") ?>
      <div class="g10 c2 m-hidden"></div>
      <div class="g10 c6 profile-bottom m-w">
<?php if ($vr("OTHERSIDE.organizations")): ?>
        <table class="re-table g10 w profile-organizations">
          <thead>
            <tr>
              <th class="g8 c5">组织摘要</th>
              <th class="g8 c2"></th>
              <th class="g8 c2"></th>
            </tr>
          </thead>
          <tbody>
<?php foreach ($vr("OTHERSIDE.organizations") as $organization): ?>
            <tr data-organization-id="<?php $e($organization, "organization_id") ?>">
              <td class="g8 c5 org-detail">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $e($organization, "organization_id") ?>" class="org-name"><?php $e($organization, "name") ?></a>
                <p class="org-desc"><?php $e($organization, "description", 1, 1) ?></p>
              </td>
              <td class="g8 c2 org-members">
                <div class="value"><?php $e($organization, "member_total") ?></div>
                <div class="prop">成员</div>
              </td>
              <td class="g8 c2 org-tasks">
                <div class="value"><?php $e($organization, "task_total") ?></div>
                <div class="prop">任务</div>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
          <tfoot class="<?php if (count($vr("OTHERSIDE.organizations")) >= $vr("OTHERSIDE.organization_total")): $e("hidden"); endif; ?>">
            <tr>
              <td></td><td></td>
              <td class="g10 c2 load-more">
                <button class="re-button re-button-xs">更多···</button>
              </td>
            </tr>
          </tfoot>
        </table>
<?php endif; ?>
      </div>
    </div>

<?php $t("FooterNav") ?>

  </div>

<!-- 模板 + 其他 -->

  <div class="re-dialog user-control hidden">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <form class="re-form user-control-options">
          <div class="dialog-header g10 c6 m-w">
            <ul class="secondary-nav">
              <li class="active"><a href="#">爱情格式化</a></li>
            </ul>
            <ul class="secondary-nav">
              <li class="single"><a href="#">帐户控制</a></li>
            </ul>
          </div>
          <div class="dialog-body g10 c6 m-w">
            <div class="field r">
              <label class="field-prop g10 c2" for="user-status">状态</label>
              <div class="field-values g10 c8">
                <label><input name="user-status" type="checkbox" <?php $a("OTHERSIDE.blocked", " checked") ?>>禁用帐户</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="send-message">发送私信</label>
              <div class="field-values g10 c8">
                <label><input name="send-message" type="checkbox" <?php $a("OTHERSIDE.send_message", " checked") ?>>允许发送私信</label>
              </div>
            </div>
          </div>
          <div class="dialog-footer g10 c6 m-w">
            <button class="re-button re-button-primary save">保存</button>
            <button class="re-button close">关闭</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script type="text/template" id="template-profile-organization">
    <tr data-organization-id="<%= organization_id %>">
      <td class="g8 c5 org-detail">
        <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= organization_id %>" class="org-name"><%- name %></a>
        <p class="org-desc"><% print(_s.xescape(description)) %></p>
      </td>
      <td class="g8 c2 org-members">
        <div class="value"><%= member_total %></div>
        <div class="prop">成员</div>
      </td>
      <td class="g8 c2 org-tasks">
        <div class="value"><%= task_total %></div>
        <div class="prop">任务</div>
      </td>
    </tr>
  </script>

<?php $t("CreateDialog") ?>

<?php $t("MessageDialog") ?>

<?php $t("Footer") ?>