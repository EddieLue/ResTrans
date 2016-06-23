<?php $t("Header") ?>

  <div class="setting">

<?php $t("Navbar") ?>

    <div class="container main">
      <div class="r">
        <ul class="secondary-nav">
          <li><a href="<?php $v("SYS_CFG.site_url", 0) ?>setting/personal/" title="设置: 个人">个人设置</a></li>
          <li class="active"><a href="<?php $v("SYS_CFG.site_url", 0) ?>setting/global/" title="设置: 全局">全局设置<i class="big-right"></i></a></li>
        </ul>
      </div>
      <div class="r">
        <div class="g10 h setting-bottom setting-global-user m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">成员</a></li>
          </ul>
          <button class="re-button re-button-xs load-user">加载成员列表</button>
          <table class="re-table g10 w hidden">
            <thead>
              <tr class="r">
                <th class="g10 c4">用户名</th>
                <th class="g10 c3">注册时间</th>
                <th class="g10 c3">邮箱</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
              <td class="g10 c4"></td>
              <td class="g10 c3"></td>
              <td class="g10 c3 right">
                <button class="re-button re-button-xs load-more">更多···</button>
              </td>
            </tfoot>
          </table>
        </div>
        <div class="g10 h setting-bottom setting-global-blocked-user m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">被禁用的成员</a></li>
          </ul>
          <button class="re-button re-button-xs load-user">加载被禁用的成员列表</button>
          <table class="re-table g10 w hidden">
            <thead>
              <tr class="r">
                <th class="g10 c4">用户名</th>
                <th class="g10 c3">注册时间</th>
                <th class="g10 c3">邮箱</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
              <td class="g10 c4"></td>
              <td class="g10 c3"></td>
              <td class="g10 c3 right">
                <button class="re-button re-button-xs load-more">更多···</button>
              </td>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="r">
        <div class="g10 h setting-bottom setting-global-organization m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">组织</a></li>
          </ul>
          <button class="re-button re-button-xs load-organization">加载组织列表</button>
          <table class="re-table g10 w hidden">
            <thead>
              <tr class="r">
                <th class="g10 c4">组织名称</th>
                <th class="g10 c2 m-hidden">任务总计</th>
                <th class="g10 c2 m-hidden">成员总计</th>
                <th class="g10 c2 m-hidden">讨论总计</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
              <td class="g10 c4 m-hidden"></td><td class="g10 c2 m-hidden"></td><td class="g10 c2 m-hidden"></td>
              <td class="g10 c2 right m-w">
                <button class="re-button re-button-xs load-more">更多···</button>
              </td>
            </tfoot>
          </table>
        </div>
        <div class="g10 h setting-bottom setting-global-task m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">任务</a></li>
          </ul>
          <button class="re-button re-button-xs load-task">加载任务列表</button>
          <table class="re-table g10 w hidden">
            <thead>
              <tr class="r">
                <th class="g10 c4">任务名称</th>
                <th class="g10 c3">所属组织</th>
                <th class="g10 c3">文件集总计</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
              <td class="g10 c4"></td><td class="g10 c3"></td>
              <td class="g10 c3 right">
                <button class="re-button re-button-xs load-more">更多···</button>
              </td>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="r">
        <div class="g10 h setting-bottom setting-global-common m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="">通用</a></li>
          </ul>
          <form class="re-form g10 w">
            <div class="field r">
              <label class="field-prop g10 c2" for="login-captcha">登录验证码</label>
              <div class="field-values g10 c8">
                <label><input name="login-captcha" type="checkbox"<?php $a("OPTIONS.login_captcha", " checked") ?>>登录需要输入验证码</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="anonymous-access">匿名访问</label>
              <div class="field-values g10 c8">
                <label><input name="anonymous-access" type="checkbox"<?php $a("OPTIONS.anonymous_access", " checked") ?>>允许匿名访问</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="member-create-organization">创建组织</label>
              <div class="field-values g10 c8">
                <label><input name="member-create-organization" type="checkbox"<?php $a("OPTIONS.member_create_organization", " checked") ?>>允许普通用户创建组织</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for=""></label>
              <div class="field-values g10 c8">
                <button type="submit" class="re-button re-button-primary">保存通用设置</button>
              </div>
            </div>
          </form>
        </div>
        <div class="g10 h setting-bottom setting-global-register m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="">注册</a></li>
          </ul>
          <form class="re-form g10 w">
            <div class="field r">
              <label class="field-prop g10 c2" for="register">注册</label>
              <div class="field-values g10 c8">
                <label><input name="register" type="checkbox"<?php $a("OPTIONS.register", " checked") ?>>允许注册</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="send-enail">邮箱地址</label>
              <div class="field-values g10 c8">
                <label><input name="send-email" type="checkbox"<?php $a("OPTIONS.send_email", " checked") ?>>需要验证邮箱地址</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for=""></label>
              <div class="field-values g10 c8">
                <button class="re-button re-button-primary">保存注册设置</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

<?php $t("FooterNav") ?>

  </div>

<!-- 模板 + 其他 -->
<script type="text/template" id="template-user">
  <tr class="r" data-user-id="<%= user_id %>">
    <td class="g10 c4 center">
      <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= user_id %>"><%- name %></a>
    </td>
    <td class="g10 c3 center">
      <%- friendly_time %>
    </td>
    <td class="g10 c3 center">
      <%- email %>
    </td>
  </tr>
</script>

<script type="text/template" id="template-organization">
  <tr class="r" data-organization-id="<%= organization_id %>">
    <td class="g10 c4 center">
      <a class="organization-name" href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= organization_id %>"><%- name %></a>
    </td>
    <td class="g10 c2 center m-hidden"><%= task_total %></td>
    <td class="g10 c2 center m-hidden"><%= member_total %></td>
    <td class="g10 c2 center m-hidden"><%= discussion_total %></td>
  </tr>
</script>

<script type="text/template" id="template-task">
  <tr class="r" data-task-id="<%= task_id %>">
    <td class="g10 c4 center">
      <a class="task-name" href="<?php $v("SYS_CFG.site_url", 0) ?>task/<%= task_id %>"><%- task_name %></a>
    </td>
    <td class="g10 c3 center">
      <a class="task-name" href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= organization_id %>"><%- organization_name %></a>
    </td>
    <td class="g10 c3 center">
      <%= set_total %>
    </td>
  </tr>
</script>

<?php $t("CreateDialog") ?>

<?php $t("MessageDialog") ?>

<?php $t("Footer") ?>