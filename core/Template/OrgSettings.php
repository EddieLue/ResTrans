<?php $t("Header") ?>

  <div class="organization">

<?php $t("Navbar") ?>

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
              <li>
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/user/">成员(<?php $v("ORG.member_total") ?>)</a>
              </li>
              <li class="active">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("ORG.organization_id") ?>/setting/">选项<i class="big-right"></i></a>
              </li>
            </ul>
          </div>
          <form action="" class="re-form organization-settings">
            <div class="secondary-nav padding-3">
              <li class="active single"><a href="">通用</a></li>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-name">组织名称</label>
              <div class="field-values g10 c8">
                <input type="text" class="re-input re-input-xs" id="organization-name" value="<?php $v("ORG.name") ?>">
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-desc">描述</label>
              <div class="field-values g10 c8">
                <textarea name="" id="organization-desc" rows="5" class="re-textarea"><?php $v("ORG.description") ?></textarea>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-member-limit">成员数限制</label>
              <div class="field-values g10 c8">
                <label><input name="organization-member-limit" type="radio" value="100"<?php echo $vr("ORG.maximum") === 100 ? " checked": "" ?>>100</label>
                <label><input name="organization-member-limit" type="radio" value="200"<?php echo $vr("ORG.maximum") === 200 ? " checked": "" ?>>200</label>
                <label><input name="organization-member-limit" type="radio" value="500"<?php echo $vr("ORG.maximum") === 500 ? " checked": "" ?>>500</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-join-mode">加入方式</label>
              <div class="field-values g10 c8">
                <label><input name="organization-join-mode" type="radio" value="1"<?php echo $vr("ORG.join_mode") === 1 ? " checked" : "" ?>>由创建者决定</label>
                <label><input name="organization-join-mode" type="radio" value="0"<?php echo $vr("ORG.join_mode") === 0 ? " checked" : "" ?>>无验证</label>
                <label><input name="organization-join-mode" type="radio" value="2"<?php echo $vr("ORG.join_mode") === 2 ? " checked" : "" ?>>不允许加入</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-accessibility">可访问性</label>
              <div class="field-values g10 c8">
                <label><input name="organization-accessibility" type="checkbox"<?php $a("ORG.accessibility", " checked") ?>>允许所有人访问</label>
              </div>
              <p class="tips">只有此组织的成员方能访问组织的讨论、任务和成员（不包含组织名、描述、组织创建者等必要信息并且此组织只能通过 URL 来访问）。</p>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-default-privileges">默认权限</label>
              <div class="field-values g10 c8">
                <label><input name="organization-default-privileges-translate" type="checkbox"<?php echo ($vr("ORG.default_privileges") === 1 || $vr("ORG.default_privileges") === 3) ? " checked" : "" ?>>翻译</label>
                <label><input name="organization-default-privileges-proofread" type="checkbox"<?php echo ($vr("ORG.default_privileges") === 2 || $vr("ORG.default_privileges") === 3) ? " checked" : "" ?>>校对</label>
              </div>
              <p class="tips">「管理」权限无法自动设置，请转到「成员」页面手动设置。</p>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-create-task">创建任务权限</label>
              <div class="field-values g10 c8">
                <label><input name="organization-create-task" type="checkbox"<?php $a("ORG.member_create_task", " checked") ?>>允许所有成员创建任务</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="organization-save-setting"></label>
              <div class="field-values g10 c8">
                <button type="submit" class="re-button re-button-primary save-setting">保存通用设置</button>
              </div>
            </div>
          </form>
        </div>
<?php $t("OrgLeft") ?>

      </div>
    </div>

<?php $t("FooterNav") ?>

  </div>

<!-- 模板 + 其他 -->
<?php
  $t("OrgExit");
  $t("CreateDialog");
  $t("MessageDialog");
  $t("Footer");
?>