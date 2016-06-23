<?php $t("Header") ?>

  <div class="setting">

<?php $t("Navbar") ?>
    <div class="container main">
      <div class="r">
        <ul class="secondary-nav">
          <li class="active"><a href="<?php $v("SYS_CFG.site_url", 0) ?>setting/personal/">个人设置<i class="big-right"></i></a></li>
<?php if ($vr("SETTING.show_global_setting_page_link")): ?>          <li><a href="<?php $v("SYS_CFG.site_url", 0) ?>setting/global/">全局设置</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="r">
        <div class="g10 h setting-bottom setting-personal-profile m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">资料</a></li>
          </ul>
          <form class="re-form">
            <div class="field r">
              <label class="field-prop g10 c2" for="user-gender">性别</label>
              <div class="field-values g10 c8">
                <label><input name="user-gender" type="radio" value="0" <?php $vr("USER.gender") === 0 ? $e("checked"):""; ?>>保密</label>
                <label><input name="user-gender" type="radio" value="1" <?php $vr("USER.gender") === 1 ? $e("checked"):""; ?>>男</label>
                <label><input name="user-gender" type="radio" value="2" <?php $vr("USER.gender") === 2 ? $e("checked"):""; ?>>女</label>
                <label><input name="user-gender" type="radio" value="3" <?php $vr("USER.gender") === 3 ? $e("checked"):""; ?>>其他</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="user-email">邮箱地址</label>
              <div class="field-values g10 c8">
                <span><?php $v("USER.email") ?></span>
                <label><input name="public-email" type="checkbox" value="1" <?php $vr("USER.public_email") === 1 ? $e("checked"):""; ?>>在个人主页中公开</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="user-email"></label>
              <div class="field-values g10 c8">
                <button type="submit" class="re-button re-button-primary">保存资料设置</button>
              </div>
            </div>
          </form>
        </div>
        <div class="g10 h setting-bottom setting-personal-common m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">通用</a></li>
          </ul>
          <form class="re-form">
            <div class="field r">
              <label class="field-prop g10 c2" for="user-gender">私信</label>
              <div class="field-values g10 c8">
                <label><input name="receive-message" type="checkbox" value="1" <?php $vr("USER.receive_message") === 0 && $e("checked"); ?>>禁止他人给我发私信</label>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="user-email"></label>
              <div class="field-values g10 c8">
                <button type="submit" class="re-button re-button-primary">保存通用设置</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <div class="r">
        <div class="g10 h setting-bottom setting-personal-security m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">安全</a></li>
          </ul>
          <form class="re-form">
            <div class="field r">
              <label class="field-prop g10 c2" for="new-password">修改密码</label>
              <div class="field-values g10 c8">
                <label><input name="new-password" id="new-password" type="password" class="re-input"></label>
                <button type="button" class="re-button show-new-password">显示密码</button>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="old-password">当前密码</label>
              <div class="field-values g10 c8">
                <label><input name="old-password" id="old-password" type="password" class="re-input"></label>
                <button type="button" class="re-button show-old-password">显示密码</button>
              </div>
            </div>
            <div class="field r">
              <label class="field-prop g10 c2" for="user-email"></label>
              <div class="field-values g10 c8">
                <button type="submit" class="re-button re-button-primary">保存安全设置</button>
              </div>
            </div>
          </form>
        </div>
        <div class="g10 h setting-bottom setting-personal-session m-w">
          <ul class="secondary-nav">
            <li class="single"><a href="#">已登录的会话</a></li>
          </ul>
          <table class="re-table g10 w">
            <thead>
              <tr class="r">
                <th class="g10 c5">会话标识</th>
                <th class="g10 c3">登录时间</th>
                <th class="g10 c2"></th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($vr("USER.sessions") as $session): ?>
              <tr class="r" data-session-id="<?php $e($session, "session_id") ?>">
                <td class="g10 c5 center" data-session-id="<?php $e($session, "session_id") ?>">
                  <span class="id"><?php $e($session, "session_id") ?>······</span>
                </td>
                <td class="g10 c3 center"><?php $e($session, "friendly_time") ?></td>
                <td class="g10 c2 center">
                  <button class="re-button re-button-warning re-button-xs remove"<?php $a($session, "current", " disabled") ?>>
                    <i class="delete"></i>
                    <span class="m-hidden"><?php $a($session, "current", "当前", "删除") ?></span>
                  </button>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

<?php $t("FooterNav") ?>

  </div>

<!-- 模板 + 其他 -->

<?php $t("CreateDialog") ?>

<?php $t("MessageDialog") ?>

<?php $t("Footer") ?>