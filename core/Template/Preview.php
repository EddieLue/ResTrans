<?php $v->other( "Header" ) ?>
  <div class="preview">
    <div class="nav">
      <div class="container">
        <a class="return" href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("PREVIEW.task_id") ?>/set/<?php $v("PREVIEW.set_id") ?>">返回文件列表 ···</a>
        <span class="title" title="<?php $v("PREVIEW.file_name") ?>"><?php $s($vr("PREVIEW.file_name"), 10) ?></span>
        <span class="tip">这里仅显示最佳译文。</span>
      </div>
    </div>
    <div class="page">
      <div class="rows">
      </div>
      <span class="no-lines hidden">此文件打开失败或没有行</span>
      <span class="loading-lines">正在加载行···</span>
      <a href="" class="load-lines hidden">加载更多行···</a>
    </div>
  </div>
  <script type="text/template" id="template-preview-line">
    <div class="row" data-row-id="<%= line_number %>">
      <p class="source-text"><% print(_s.xescape(text)) %></p>
      <% if (translate) { %>
      <span class="no-best-translation<% if(this.model.get('translations')) print(' hidden') %>">此行没有最佳译文</span>
      <% } %>
      <% if (this.model.get('translations')) { %>
      <p class="best-translation-text" title="译文贡献者: <%- this.model.get('translations')[0].contributor_name %>">
        <% print(_s.xescape(this.model.get('translations')[0].text)) %>
      </p>
      <% } %>
    </div>
  </script>
<?php $v->other( "Footer" ) ?>