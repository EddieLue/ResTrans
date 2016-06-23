<?php $v->other( "Header" ) ?>

  <div class="organization">

<?php $v->other( "Navbar" ) ?>

    <div class="container main">
      <div class="r">
        <div class="g10 c7 organization-right m-w pc-pull-right m-pull-left">
          <div class="r">
            <ul class="secondary-nav">
<?php if ($vr("ORG.real_accessibility")): ?>
              <li class="active">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>">讨论(<?php $v("ORG.discussion_total") ?>)<i class="big-right"></i></a>
              </li>
              <li class="">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/task/">任务(<?php $v("ORG.task_total") ?>)</a>
              </li>
              <li>
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/user/">成员(<?php $v("ORG.member_total") ?>)</a>
              </li>
<?php if ($vr("ORG.access_setting_pages")): ?>              <li class="">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/setting/">选项</a>
              </li><?php endif; ?>

<?php elseif (!$vr("ORG.real_accessibility")): ?>
              <li class="single"><a href="">组织已禁止访问</a></li>
<?php endif; ?>
            </ul>
          </div>
          <div class="g10 w organization-discussion">
            <form class="send-discuss<?php $a("ORG.real_accessibility", "", " hidden") ?>" method="POST" action="">
              <textarea rows="4" class="discuss-input re-textarea" placeholder="写下要讨论的事"></textarea>
              <span class="char-count"><span class="current">0</span>/<span class="total">500</span></span>
              <span class="action">
                <button type="submit" class="re-button re-button-primary discuss-send">发表<i class="right"></i></button>
              </span>
            </form>
            <ul class="discussions" data-discussion-only="<?php $v("ORG.discussion_only") ?>">
<?php if ($vr("ORG.discussions")): foreach ( $vr("ORG.discussions") as $discussion ): ?>
              <li class="discussion" data-discussion-id="<?php $v->e($discussion->discussion_id) ?>" data-comment-sum="<?php $v->e($discussion->comment_total) ?>">
                <div class="disn-main">
                  <a href="<?php $v('SYS_CFG.site_url', false) ?>user/<?php $e($discussion, "user_id") ?>" class="avatar-1x"><img src="<?php $e($discussion, "avatar_link") ?>" alt="" class="avatar-img"><div class="username"><?php $e( $discussion, "user_name" ) ?></div></a>
                  <p class="detail"><?php $e( $discussion, "content", 1, 1 ) ?></p>
                  <a class="send-datetime" href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>?discussion_id=<?php $e($discussion, "discussion_id") ?>"><?php $v->fTime( $discussion->created ) ?></a>
                  <span class="action">
                  <?php if ( $discussion->can_delete ): ?><button class="re-button re-button-xs re-button-warning delete-this"><i class="delete"></i>删除</button><?php endif; ?>
                  <button class="re-button re-button-xs expand-comments"><i class="comment"></i><?php $e($discussion, "comment_total") ?>条回复</button>
                  </span>
                  <div class="comment-outside">
                    <ul class="comments"></ul>
                    <a class="more-comments" href="">加载更多评论</a>
                    <form action="" class="re-form send-comment" method="POST" data-comment-parent-id="0">
                      <textarea rows="1" class="re-textarea comment-writer" placeholder="回复此讨论···"></textarea>
                      <button type="submit" class="re-button re-button-xs re-button-primary comment-send">回复</button>
                    </form>
                  </div>
                </div>
              </li>
<?php endforeach; endif; ?>
            </ul>
            <a href="" class="more-discussions<?php $a("ORG.real_accessibility", "", " hidden") ?>">加载更多讨论</a>
          </div>
        </div>
<?php $v->other( "OrgLeft" ) ?>
      </div>
    </div>

<?php $v->other( "FooterNav" ) ?>

  </div>

<!-- 模板 + 其他 -->
  <div class="re-dialog dialog-delete-discussion hidden">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
            <p class="g10 c7">
                删除此条讨论？<br>讨论下的回复也会被删除。
            </p>
            <div class="g10 c3 center">
              <button class="re-button re-button-warning delete">删除</button>
              <button class="re-button close-dialog cancel">取消</button>
            </div>
        </div>
      </div>
    </div>
  </div>
<?php $v->other("OrgExit") ?>

<?php $v->other( "CreateDialog" ) ?>

<?php $v->other( "MessageDialog" ) ?>
  <script type="text/template" id="template-discussion">
    <li class="discussion" data-discussion-id="<%= discussion_id %>" data-comment-sum="<%= comment_total %>">
      <div class="disn-main">
        <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= user_id %>" class="avatar-1x"><img src="<%= avatar_link %>" class="avatar-img"><div class="username"><%- user_name %></div></a>
        <p class="detail"><% print(_s.xescape(content)) %></p>
        <a class="send-datetime" href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<%= organization_id %> ?>?discussion_id=<%= discussion_id %>"><%= friendly_time %></a>
        <span class="action">
          <% if (can_delete) { print('<button class="re-button re-button-xs re-button-warning delete-this">删除</button>') } %>
          <button class="re-button re-button-xs expand-comments"><%= comment_total %>条回复</button>
        </span>
        <div class="comment-outside">
          <ul class="comments"></ul>
          <a class="more-comments" href="">加载更多评论</a>
          <form action="" class="re-form send-comment" method="POST" data-comment-parent-id="0">
            <textarea rows="1" class="re-textarea comment-writer" placeholder="回复此讨论…"></textarea>
            <button type="submit" class="re-button re-button-xs re-button-primary comment-send">回复</button>
          </form>
        </div>
      </div>
    </li>
  </script>
  <script type="text/template" id="template-discussion-comment">
    <li class="comment" data-comment-id="<%= comment_id %>">
      <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= user_id %>" class="avatar-1x"><img src="<%= avatar_link %>" alt="" class="avatar-img"><div class="username"><%- user_name %></div></a>
      <p class="content"><%- content %></p>
      <span class="send-datetime"><%= friendly_time %></span>
      <% if (parent_id > 0 && parent_user_name) print('<span class="reply"><a href="" data-parent-id='+parent_id+'>'+parent_user_name+'</a></span>') %>
      <span class="action">
        <button class="re-button re-button-xs reply-comment">回复</button>
        <% if (can_delete) { %>
        <button class="re-button re-button-warning re-button-xs delete-comment">删除</button>
        <% } %>
      </span>
    </li>
  </script>
  <script type="text/template" id="template-more-comments">
  </script>
<?php $v->other( "Footer" ) ?>