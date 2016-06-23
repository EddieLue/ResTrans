<?php $myself = $vr("ORG.myself"); ?>
<?php $v->other("Header") ?>

  <div class="organization">

<?php $v->other("Navbar") ?>

    <div class="container main">
      <div class="r">

        <div class="g10 c7 organization-right m-w pc-pull-right m-pull-left">
          <div class="r padding-3">
            <ul class="secondary-nav">
              <li>
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>">讨论(<?php $v("ORG.discussion_total") ?>)</a>
              </li>
              <li>
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/task/">任务(<?php $v("ORG.task_total") ?>)</a>
              </li>
              <li class="active">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/user/">成员(<?php $v("ORG.member_total") ?>)<i class="big-right"></i></a>
              </li>
<?php if ($vr("ORG.access_setting_pages")): ?>              <li class="">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/setting/">选项</a>
              </li><?php endif; ?>
            </ul>
          </div>
          <div class="r padding-3">
            <table class="re-table g6 w organization-members">
              <thead>
                <tr>
                  <th class="g6 c4">成员列表</th>
                  <th class="g6 c2">选项</th>
                </tr>
              </thead>
              <tbody>
<?php foreach ($vr("ORG.users") as $user) : ?>
                <tr data-user-id="<?php $e($user, "user_id") ?>">
                  <td class="g8 c7 member-detail">
                    <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $e($user, "user_id") ?>" class="avatar-1x"><img src="<?php $avatar($user->email) ?>" alt="" class="avatar-img"><span class="username"><?php $e($user, "user_name") ?></span></a>
<?php 
  if (
  ($myself->is_global_admin || $myself->is_org_admin || $myself->is_org_manager) && // 有任何一项管理权限
  !$user->is_org_admin && // 非组织创建者（组织创建者无人可改）
  $vr("USER.user_id") !== $user->user_id // 非自身权限
  ):
?>
                    <a href="#" class="privilege common<?php $a($user, "translate", " on") ?>">翻译</a>
                    <a href="" class="privilege proofread<?php $a($user, "proofread", " on") ?>">校对</a>
                    <a href="" class="privilege upload<?php $a($user, "upload", " on") ?>">管理任务</a>
<?php endif; ?>
<?php if ($myself->is_org_admin && $vr("USER.user_id") !== $user->user_id): ?>
                    <a href="#" class="privilege manage<?php $a($user, "manage", " on") ?>">管理成员权限</a>
<?php endif; ?>
                  </td>
                  <td class="g8 c1 center member-privilege-options">
<?php if ($myself->weights > $user->weights && !$user->is_org_admin): ?>
                    <button class="re-button re-button-xs re-button-warning delete">移除</button>
<?php else: ?>
                    <p>∞无选项</p>
<?php endif; ?>
                  </td>
                </tr>
<?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="<?php if($vr("ORG.member_total") <= count($vr("ORG.users"))): $e("hidden"); endif; ?>">
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
  <div class="re-dialog delete-user hidden">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
            <p class="g10 c7">
                移除“<span class="name"></span>”？
            </p>
            <div class="g10 c3 center">
              <button type="submit" class="re-button re-button-warning delete">确定移除</button>
              <button type="button" class="re-button close-dialog close">取消</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script type="text/template" id="template-user">
    <tr data-user-id="<%= user_id %>">
      <td class="g8 c7 member-detail">
        <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= user_id %>" class="avatar-1x"><img src="<%= avatar_link %>" class="avatar-img"><span class="username"><%- user_name %></span></a>
        <% if (show_base) { %>
        <a href="" class="privilege common<% if (translate) print(" on") %>">翻译</a>
        <a href="" class="privilege proofread<% if (proofread) print(" on") %>">校对</a>
        <a href="" class="privilege upload<% if (upload) print(" on") %>">管理任务</a>
        <% } %>
        <% if (show_manage) { %>
        <a href="#" class="privilege manage<% if (manage) print(" on") %>">管理其他成员</a>
        <% } %>
      </td>
      <td class="g8 c1 center member-privilege-options">
      <% if (show_delete){ %>
        <button class="re-button re-button-xs re-button-warning delete">移除</button>
      <% }else{ %>
          <p>∞无选项</p>
      <% } %>
      </td>
    </tr>
  </script>
<?php $v->other("CreateDialog") ?>

<?php $v->other("MessageDialog") ?>

<?php $v->other("Footer") ?>
