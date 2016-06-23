<div class="re-dialog" style="display: none;" id="create-diag">
  <div class="dialog-inner">
    <div class="dialog-content r">
      <div class="dialog-header g10 c6 m-w">
        <ul class="secondary-nav">
<?php if ($v("ORG.organization_id", false, false)): ?>
          <li id="create-diag-navtsk"><a href="#" title="创建任务">任务</a></li>
<?php endif; ?>
          <li id="create-diag-navorg"><a href="#" title="创建组织">组织</a></li>
        </ul>
      </div>
      <div class="dialog-body g10 c6 m-w">
        <!-- 创建组 -->
        <form action="" class="re-form" id="create-organization-form">
          <div class="field r">
            <label class="field-prop g10 c2" for="organization-name">组织名称</label>
            <div class="field-values g10 c8">
              <input type="text" class="re-input re-input-xs" id="organization-name">
            </div>
          </div>
          <div class="field r">
            <label class="field-prop g10 c2" for="organization-description">描述</label>
            <div class="field-values g10 c8">
              <textarea name="" id="organization-description" rows="5" class="re-textarea"></textarea>
            </div>
          </div>
        </form>
        <!-- 创建任务 -->
        <form action="" class="re-form" id="create-task-form">
          <div class="field r">
            <label class="field-prop g10 c2" for="task-organization">所属组织</label>
            <div class="field-values g10 c8">
              <span><?php $v("ORG.name") ?></span>
              <input type="hidden" id="task-organization" value="<?php $v("ORG.organization_id") ?>">
            </div>
          </div>
          <div class="field r">
            <label class="field-prop g10 c2" for="task-name">任务名称</label>
            <div class="field-values g10 c8">
              <input type="text" class="re-input re-input-xs" id="task-name">
            </div>
          </div>
          <div class="field r">
            <label class="field-prop g10 c2" for="task-description">任务描述</label>
            <div class="field-values g10 c8">
              <textarea id="task-description" rows="5" class="re-textarea"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="dialog-footer r g10 c6 m-w">
        <button class="re-button re-button-primary" id="create-organization-action">创建组织</button>
        <button class="re-button re-button-primary" id="create-task-action">创建任务</button>
        <button class="re-button" id="close-diag">关闭</button>
      </div>
    </div>
  </div>
</div>