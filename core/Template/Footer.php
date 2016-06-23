  <script type="text/template" id="template-sys-notification">
    <li class="notification r" data-sys-notification-id=<%= notification_id %>>
      <div class="g6 h">
        <span class="operation">
          <a class="sender" href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= sender %>"><%- sender_name %></a>
          <span class="operation-text">
          <% if (type === 0) {
            print("请求加入组织")
          } else if (type === 1) {
            print("已退出组织")
          }  else if (type === 2) {
            print("同意您加入组织")
          }  else if (type === 3) {
            print("拒绝您加入组织")
          } %>
          </span>
        </span>
        <a class="target" href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= target_id %>"><%- organization_name %></a>
      </div>
      <div class="g6 h actions">
      <% if (type === 0 && status === 1) { %>
        <button class="re-button re-button-xs operation-organization-accept" title="同意他(她)加入这个组织。">同意</button>
        <button class="re-button re-button-xs operation-ignore" title="忽略此请求，不会提醒对方。">忽略</button>
        <button class="re-button re-button-xs re-button-warning operation-reject" title="拒绝此请求，并提醒对方。">拒绝</button>
      <% } else { %>
        <% if (type === 0 && status === 2) { %>
        <span class="operation-status">已同意</span>
        <% } else if (type === 0 && status === 3) { %>
        <span class="operation-status">已拒绝</span>
        <% } else if (type === 0 && status === 4) { %>
        <span class="operation-status">已忽略</span>
        <% } %>
        <button class="re-button re-button-xs operation-delete">清除</button>
      <% } %>
      </div>
    </li>
  </script>
  <script type="text/template" id="template-draft">
    <li class="draft r" data-draft-hash="<%= hash %>">
      <div class="meta g8 h">
        <div class="task"><a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<%= task_id %>"><%- task_name %></a></div>
        <div class="source-version">
        <% if (!set_name && !file_name) { %>
          <span>未打开任何文件</span>
        <% } %>
        <% if (!set_name && file_name) { %>
          <span><%- set_name %> &raquo; 没有打开文件</span>
        <% } %>
        <% if (set_name && file_name) { %>
          <span><%- set_name %> &raquo; <% print(_s.xescape(file_name.length > 10 ? file_name.substr(0, 10) + "···" : file_name)) %></span>
        <% } %>
        </div>
      </div>
      <div class="options g8 h">
        <button class="re-button re-button-xs open">打开</button>
        <button class="re-button re-button-xs re-button-warning delete">删除</button>
      </div>
    </li>
  </script>
  <ul class="notifications"></ul>
  <script type="text/template" id="notification-template">
    <li class="notification" id="<%= notificationId %>"><%- notificationContent %></li>
  </script>
  <script>var _s = <?php $v( "FE.data", false ) ?>;</script>
  <script src="<?php $v( "SYS_CFG.assets_url", false ); ?>js/third-party/require.js?v2.1.18" data-main="<?php $v( "SYS_CFG.assets_url", false ); ?>js/main" data-modules="<?php $v( "FE.modules", false ) ?>" id="require"></script>
</body>
</html>