<?php $v->other( "Header" ) ?>

  <div class="worktable">

<?php $v->other( "WorkTableTop" ) ?>

    <div class="wt-main">
      <span class="wt-nav-loading"><span class="loading-text">正在加载</span><pre class="ing">·  </pre></span>
      <button class="re-button re-button-xs save hidden" title="这将自动运行。">保存</button>
      <div class="page">
        <div class="no-row">正在加载行···</div>
        <div class="load-row load-before hidden"><a href="">加载之前的行</a></div>
        <div class="load-row load-after hidden"><a href="">加载之后的行</a></div>
      </div>
    </div>
  </div>
<!--模板-->
  <div class="re-dialog create-set-dialog <?php echo $v("WORKTABLE.no_sets", false, false) ? "" : "hidden"?>">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 r m-w">
          <form action="#" class="create-set">
            <div class="g10 c7">
              <input type="text" class="re-input width100 no-margin set-name" placeholder="创建新的文件集">
            </div>
            <div class="g10 c3 center">
              <button type="submit" class="re-button re-button-primary">创建集</button>
              <button type="button" class="re-button close-dialog">关闭</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="re-dialog choose-file-language" style="display: none">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-header g10 c6 m-w">
          <ul class="secondary-nav">
            <li class="active"><a href="">语言设置</a></li>
          </ul>
        </div>
        <div class="dialog-body g10 c6 m-w">
          <ul class="language-selector">
          </ul>
        </div>
        <div class="dialog-footer r g10 c6 m-w">
          <button class="re-button re-button-primary continue">开始上传</button>
          <button class="re-button cancel">取消</button>
        </div>
      </div>
    </div>
  </div>
  <div class="re-dialog before-switch-file" style="display: none">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
          <form action="#" class="create-set">
            <p class="g10 h tip">变更尚未保存。<br>如何继续？</p>
            <div class="g10 h center options">
              <button type="button" class="re-button re-button-primary save-now">现在保存</button>
              <button type="submit" class="re-button re-button-warning discard">抛弃变更</button>
              <button type="button" class="re-button cancel">取消</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="re-dialog goto-line-dialog" style="display: none">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
          <form action="#" class="go">
            <div class="g10 c7">
              <input type="number" class="re-input width100 no-margin ln" placeholder="输入行数">
            </div>
            <div class="g10 c3 center">
              <button type="submit" class="re-button re-button-primary">→</button>
              <button type="button" class="re-button close-dialog close">关闭</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="re-dialog delete-file-dialog hidden">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
            <p class="g10 c7">
                你正在尝试删除文件“<span class="fn"></span>”。<br>此操作无法恢复。
            </p>
            <div class="g10 c3 center">
              <button class="re-button re-button-warning delete">确定删除</button>
              <button class="re-button close-dialog close">取消</button>
            </div>
        </div>
      </div>
    </div>
  </div>
  <div class="re-dialog download-fallback-setting-dialog" style="display: none">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-header g10 c8 m-w">
          <ul class="secondary-nav">
            <li class="active"><a href="#">下载</a></li>
          </ul>
          <ul class="secondary-nav">
            <li class="single"><a href="#">自动回退设置</a></li>
          </ul>
        </div>
        <div class="dialog-body g10 c8 r m-w">
          <form class="re-form">
            <div class="field g8 c2">
              <label class="field-prop g6 w center" for="best-translation">最佳译文</label>
              <div class="field-values g6 w center">
                <label class="show"><input name="best-translation" type="radio" value="1" checked>应用</label>
                <label class="show"><input name="best-translation" type="radio" value="0">跳过</label>
              </div>
            </div>
            <div class="field g8 c2">
              <label class="field-prop g6 w center" for="newest-translation">最新译文</label>
              <div class="field-values g6 w center">
                <label class="show"><input name="newest-translation" type="radio" value="1" checked>应用</label>
                <label class="show"><input name="newest-translation" type="radio" value="0">跳过</label>
              </div>
            </div>
            <div class="field g8 c2">
              <label class="field-prop g6 w center" for="machine-translation">机械翻译</label>
              <div class="field-values g6 w center">
                <label class="show"><input name="machine-translation" type="radio" value="1" checked>应用</label>
                <label class="show"><input name="machine-translation" type="radio" value="0">跳过</label>
              </div>
            </div>
            <div class="field g8 c2">
              <label class="field-prop g6 w center" for="source-translation">原文</label>
              <div class="field-values g6 w center">
                <label class="show"><input name="source" type="radio" value="1" checked>应用</label>
                <label class="show"><input name="source" type="radio" value="0">跳过（可能引起会空行）</label>
              </div>
            </div>
          </form>
        </div>
        <div class="dialog-footer g10 c8 r m-w">
          <button class="re-button re-button-primary start-download">下载 0 个文件</button>
          <button class="re-button cancel">取消</button>
        </div>
      </div>
    </div>
  </div>
  <script id="template-line-only" type="text/template">
    <div class="row" data-line=<%= line %>>
      <div class="original-text">
        <p class="content<%= need %>"><% print(_s.xescape(content)) %></p>
      </div>
      <%= addition %>
      <ul class="candidate-translations<%= display_candidates %>">
        <%= candidate_translations %>
      </ul>
    </div>
  </script>
  <script id="template-translated-text" type="text/template">
    <div class="translated-text">
      <textarea rows="1" class="re-worktable-textarea" placeholder="<%= machine_translation %>"><% print(_s.xescape(text)) %></textarea>
    </div>
    <div class="para-tools">
      <span class="pleft">
        <a href="" class="pre-row" title="上一行（Ctrl ↑）"><i class="up-arrow"></i></a>
        <a href="" class="next-row" title="下一行（Ctrl ↓）"><i class="down-arrow"></i></a>
        <span class="line-number"><%= line %></span>
      </span>
      <span class="pright">
        <a class="insert-machine-translation" href="#" title="Ctrl + →">使用机械翻译</a>
        <a class="use-original-text" href="#">使用原文</a>
      </span>
    </div>
  </script>
  <script id="template-best-translation" type="text/template">
    <div class="best-translation<%= show_best_translation %>" data-best-translation-id="<%= translation_id %>">
      <p class="content"><% print(_s.xescape(best_translation_text)) %></p>
      <a href="" class="revoke" data-disabled="false">撤销</a>
      <a href="" class="show-candidates">展开候选译文</a>
      <span class="contributor-list">贡献者：<%= contributor %> / <%= proofreader %></span>
    </div>
  </script>
  <script type="text/template" id="template-translation">
    <li class="translation" data-translation-id="<%= translation_id %>">
      <p class="content"><% print(_s.xescape(content)) %></p>
      <div class="panel">
        <span class="options">
          <a href="" class="best <% if (has_best || not_save) print("hidden") %>" data-disabled="false">设为最佳译文</a>
          <a href="" class="delete <% if (is_best) print("hidden") %><?php if ($vr("TASK.frozen")) echo " hidden"; ?>">删除候选</a>
        </span>
        <span class="meta"><a href="<?php $v("SYS_CFG.site_url") ?>profile/<%= contributor %>" class="contributor" target="_blank"><%- contributor_name %></a> <span class="date"><%= contribute_time %></span></span>
      </div>
    </li>
  </script>
  <script type="text/template" id="template-file-manager-upload">
    <li class="temporary-file" data-file-cid="<%= cid %>">
      <span class="meta">
        <span class="name"><% if (name.length > 15) print(_.escape(name.substr(0, 15))+"···"); else print(_.escape(name)); %></span>
      </span>
      <span class="status"><span><%= status %></span></span>
    </li>
  </script>
  <script type="text/template" id="template-file-manager-file">
    <li class="file" data-file-id="<%= id %>">
      <div class="file-meta">
        <a class="name" href="#" title="<%- name %>"><% if (name.length > 10) print(_.escape(name.substr(0, 10))+"···"); else print(_.escape(name)); %>
          <span class="file-info"><%= ext %> / <%= line %> (<%= percentage %>%)</span>
        </a>
        <span class="last-update m-hidden"><span class="time"><%= last_update %></span> <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<%= last_contributor %>" class="user"><%- last_update_by %></a></span>
      </div>
      <div class="file-options">
        <button class="re-button re-button-xs download">下载</button>
        <button class="re-button re-button-xs re-button-warning delete<?php if (!$vr("WORKTABLE.allow_manage_files") || $vr("TASK.frozen")) echo " hidden"; ?>">删除</button>
      </div>
    </li>
  </script>
  <script type="text/template" id="template-file-language-settings">
    <li class="selector r">
      <span class="file-name g10 c4"><% if (file_name.length > 5) print(_.escape(file_name.substr(0, 5))+"···"); else print(_.escape(file_name)); %></span>
      <span class="select-original-language g10 c3">
        <select class="re-select">
          <option value="en-US">英语（美国）</option>
          <option value="en-UK">英语（英国）</option>
          <option value="ja-JP">日语</option>
          <option value="de-DE">德语</option>
          <option value="ru-RU">俄语</option>
          <option value="fr-FR">法语</option>
          <option value="ko-KR">韩语</option>
          <option value="zh-CN">简体中文（中国大陆）</option>
          <option value="zh-HK">繁体中文（中国香港）</option>
          <option value="zh-TW">繁体中文（中国台湾）</option>
        </select>
      </span>
      <span class="select-target-language g10 c3">
        <select class="re-select">
          <option value="en-US">英语（美国）</option>
          <option value="en-UK">英语（英国）</option>
          <option value="ja-JP">日语</option>
          <option value="de-DE">德语</option>
          <option value="ru-RU">俄语</option>
          <option value="fr-FR">法语</option>
          <option value="ko-KR">韩语</option>
          <option value="zh-CN">简体中文（中国大陆）</option>
          <option value="zh-HK">繁体中文（中国香港）</option>
          <option value="zh-TW">繁体中文（中国台湾）</option>
        </select>
      </span>
    </li>
  </script>
<?php $v->other( "MessageDialog" ) ?>
<?php $v->other( "Footer" ) ?>