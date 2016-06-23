<?php $t("Header") ?>

  <div class="task">

<?php $t("Navbar") ?>

    <div class="container main">
      <div class="r">

        <div class="task-right g6 c4 m-w pc-pull-right m-pull-left">
          <div class="secondary-nav padding-3">
            <li class=""><a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("TASK.task_id") ?>">文件集</a></li>
            <li class="active"><a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("TASK.task_id") ?>/setting/">选项<i class="big-right"></i></a></li>
          </div>
          <form action="" class="re-form general-setting">
            <div class="secondary-nav padding-3">
              <li class="active single"><a href="">通用</a></li>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="task-name">名称</label>
              <div class="field-values g10 c8">
                <input type="text" class="re-input re-input-xs" id="task-name" value="<?php $v("TASK.name") ?>">
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="task-desc">描述</label>
              <div class="field-values g10 c8">
                <textarea name="" id="task-desc" rows="5" class="re-textarea"><?php $v("TASK.description") ?></textarea>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2">原始语言（默认）</label>
              <div class="field-values g10 c8">
                <label><input name="task-original-language" type="radio" value="en-US"<?php if ($vr("TASK.original_language") === "en-US") $e(" checked") ?>>英语(美)</label>
                <label><input name="task-original-language" type="radio" value="en-UK"<?php if ($vr("TASK.original_language") === "en-UK") $e(" checked") ?>>英语(英)</label>
                <label><input name="task-original-language" type="radio" value="ja-JP"<?php if ($vr("TASK.original_language") === "ja-JP") $e(" checked") ?>>日语</label>
                <label><input name="task-original-language" type="radio" value="de-DE"<?php if ($vr("TASK.original_language") === "de-DE") $e(" checked") ?>>德语</label>
                <label><input name="task-original-language" type="radio" value="ru-RU"<?php if ($vr("TASK.original_language") === "ru-RU") $e(" checked") ?>>俄语</label>
                <label><input name="task-original-language" type="radio" value="fr-FR"<?php if ($vr("TASK.original_language") === "fr-FR") $e(" checked") ?>>法语</label>
                <label><input name="task-original-language" type="radio" value="ko-KR"<?php if ($vr("TASK.original_language") === "ko-KR") $e(" checked") ?>>韩语</label>
                <label><input name="task-original-language" type="radio" value="zh-CN"<?php if ($vr("TASK.original_language") === "zh-CN") $e(" checked") ?>>简体中文(大陆)</label>
                <label><input name="task-original-language" type="radio" value="zh-HK"<?php if ($vr("TASK.original_language") === "zh-HK") $e(" checked") ?>>繁体中文(港)</label>
                <label><input name="task-original-language" type="radio" value="zh-TW"<?php if ($vr("TASK.original_language") === "zh-TW") $e(" checked") ?>>繁体中文(台)</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2">目标语言（默认）</label>
              <div class="field-values g10 c8">
                <label><input name="task-target-language" type="radio" value="en-US"<?php if ($vr("TASK.target_language") === "en-US") $e(" checked") ?>>英语(美)</label>
                <label><input name="task-target-language" type="radio" value="en-UK"<?php if ($vr("TASK.target_language") === "en-UK") $e(" checked") ?>>英语(英)</label>
                <label><input name="task-target-language" type="radio" value="ja-JP"<?php if ($vr("TASK.target_language") === "ja-JP") $e(" checked") ?>>日语</label>
                <label><input name="task-target-language" type="radio" value="de-DE"<?php if ($vr("TASK.target_language") === "de-DE") $e(" checked") ?>>德语</label>
                <label><input name="task-target-language" type="radio" value="ru-RU"<?php if ($vr("TASK.target_language") === "ru-RU") $e(" checked") ?>>俄语</label>
                <label><input name="task-target-language" type="radio" value="fr-FR"<?php if ($vr("TASK.target_language") === "fr-FR") $e(" checked") ?>>法语</label>
                <label><input name="task-target-language" type="radio" value="ko-KR"<?php if ($vr("TASK.target_language") === "ko-KR") $e(" checked") ?>>韩语</label>
                <label><input name="task-target-language" type="radio" value="zh-CN"<?php if ($vr("TASK.target_language") === "zh-CN") $e(" checked") ?>>简体中文(大陆)</label>
                <label><input name="task-target-language" type="radio" value="zh-HK"<?php if ($vr("TASK.target_language") === "zh-HK") $e(" checked") ?>>繁体中文(港)</label>
                <label><input name="task-target-language" type="radio" value="zh-TW"<?php if ($vr("TASK.target_language") === "zh-TW") $e(" checked") ?>>繁体中文(台)</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="group-description"></label>
              <div class="field-values g10 c8">
                <button type="submit" class="re-button re-button-primary save-setting">保存通用设置</button>
              </div>
            </div>
          </form>
          <div>
            <ul class="secondary-nav padding-3">
              <li class="active single"><a href="">译文拉取接口密钥</a></li>
            </ul>
            <div class="api-key">
              <input type="text" class="re-input re-input-xs key" value="<?php $v("TASK.api_key") ?>" readonly>
              <button class="re-button regenerate">重新生成</button>
              <span class="request-count">已拉取 <?php $v("TASK.api_request") ?> 次</span>
              <p class="tips">一旦重新生成，你之前的密钥将会失效。<br>此密钥可用于从其他服务器上拉取此任务文件中的译文。</p>
            </div>
          </div>
          <div>
            <ul class="secondary-nav padding-3">
              <li class="active single"><a href="">删除</a></li>
            </ul>
            <div class="delete-task">
              <button class="re-button re-button-warning open-delete-dialog">删除此任务</button>
              <p class="tips">一旦删除将无法被恢复。</p>
            </div>
          </div>
          <div>
            <ul class="secondary-nav padding-3">
              <li class="active single"><a href="">冻结</a></li>
            </ul>
            <div class="freeze-task">
              <button class="re-button<?php if (!$vr("TASK.frozen")) $e(" re-button-primary") ?> freeze"><?php if ($vr("TASK.frozen")) $e("解除冻结"); else $e("冻结此任务") ?></button>
              <p class="tips">冻结之后，你和任何人都无法通过在工作台打开功能修改文件、上传文件及提交翻译。</p>
            </div>
          </div>
        </div>
<?php $t("TaskLeft") ?>
      </div>
    </div>

<?php $t("FooterNav") ?>

  </div>

<!-- 模板 + 其他 -->
  <div class="re-dialog delete-task-dialog hidden">
    <div class="dialog-inner">
      <div class="dialog-content r">
        <div class="dialog-body g10 c6 m-w">
            <p class="g10 c7">
              正准备删除此任务，<br>
              一旦确定，任务中的所有数据都将被删除且无法恢复。
            </p>
            <div class="g10 c3 center">
              <button class="re-button re-button-warning delete">确定删除</button>
              <button class="re-button close-dialog cancel">取消</button>
            </div>
        </div>
      </div>
    </div>
  </div>
<?php $t("CreateDialog") ?>

<?php $t("MessageDialog") ?>

<?php $t("Footer") ?>