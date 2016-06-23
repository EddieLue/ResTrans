  <div class="re-dialog message-dialog" style="display:none">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-header g10 c6 m-w">
          <ul class="secondary-nav">
            <li class="msg-diag-allcnv"><a href="" title="私信: 全部会话">会话</a></li>
            <li class="msg-diag-newmsg"><a href="" title="私信: 撰写新私信">撰写新私信</a></li>
            <li class="msg-diag-dialogue"><a href="" title="私信: 正在对话"></a></li>
          </ul>
        </div>
        <div class="dialog-body g10 c6 m-w">
          <ul class="conversations hidden">
            <li class="loading-conversations"><i class="loading"></i>正在加载您的私信</li>
            <li class="more-conversations hidden"><i class="loading"></i><a href="">加载更多私信会话</a></li>
          </ul>
          <div class="write-message r hidden">
            <form action="" class="search-area g10 w">
              <a href="#" class="avatar-1x receiver"><img src="" class="avatar-img"><div class="username"></div></a>
              <button type="button" class="re-button re-button-xs show-search-input">修改</button>
              <input type="text" class="re-input search-receiver-keyword" placeholder="输入对方id或昵称并回车">
            </form>
            <div class="content g10 w">
              <form action="">
                <textarea name="" id="" rows="10" class="re-textarea content-input" placeholder="1000字符以内"></textarea>
              </form>
            </div>
          </div>
        </div>
        <div class="dialog-footer r g10 c6 m-w">
          <button class="re-button re-button-primary refresh-messages">刷新</button>
          <button class="re-button re-button-primary reply-message">回复</button>
          <button class="re-button re-button-primary send-message">发送私信</button>
          <button class="re-button close-message-dialog">关闭</button>
        </div>
      </div>
    </div>
  </div>
  <script type="text/template" id="template-dialogue">
    <div class="dialogue" data-conversation-between="<%= between %>">
      <ul class="diag-messages">
        <li class="more-messages"><a href="">加载更早的私信</a></li>
      </ul>
      <div class="message-writer">
        <textarea name="" id="" rows="3" class="re-textarea content-input" placeholder="1000字符以内"></textarea>
      </div>
    </div>
  </script>
  <script type="text/template" id="template-conversation">
    <li class="conversation" data-message-id="<%= message_id %>">
      <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= otherside_id %>" class="avatar-1x"><img src="<%= avatar_link %>" class="avatar-img"><div class="username"><%- otherside_name %></div></a>
      <span class="msg-last-datetime"><%- time %></span>
      <p class="msg-last-content"><%- content %></p>
      <div class="msg-meta">
        <span class="unread"><% if (unread) { %><span>[有未读私信]</span><% } %></span>
        <div class="options">
          <button class="re-button re-button-xs re-button-primary conversation-view-all">查看</button>
          <button class="re-button re-button-xs re-button-warning conversation-delete">删除会话</button>
        </div>
      </div>
    </li>
  </script>
  <script type="text/template" id="template-otherside">
    <li class="message otherside" data-message-id="<%= message_id %>">
      <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= otherside %>" class="avatar-1x sender-avatar"><img src="<%= otherside_avatar_link %>" class="avatar-img"><div class="username"><%- name %></div></a>
      <p class="msg-content<% if (unread) print(' unread') %>"><%- content %></p>
      <div class="msg-options">
        <span class="datetime"><%- friendly_time %></span>
        <span class="slash">/</span>
        <a class="delete" href="#">删除</a>
      </div>
    </li>
  </script>
  <script type="text/template" id="template-myself">
    <li class="message myself" data-message-id="<%= message_id %>">
      <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= owner %>" class="avatar-1x sender-avatar"><img src="<%= owner_avatar_link %>" class="avatar-img"><div class="username"><%- name %></div></a>
      <p class="msg-content"><%- content %></p>
      <div class="msg-options">
        <span class="datetime"><%- friendly_time %></span>
        <span class="slash">/</span>
        <a class="delete" href="">删除</a>
      </div>
    </li>
  </script>