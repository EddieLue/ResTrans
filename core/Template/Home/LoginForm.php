          <ul class="secondary-nav g10 c8" id="home-selections">
            <li id="selection-login" class="single"><a href="<?php $v( "SYS_CFG.site_url", false )?>login/">登录</a></li>
<?php if ($vr("HOME.allow_register")): ?>
            <li id="selection-register" class=""><a href="<?php $v( "SYS_CFG.site_url", false )?>register/">注册</a></li>
<?php endif; ?>
            <li id="selection-retrieve" class=""><a href="<?php $v( "SYS_CFG.site_url", false )?>retrieve/">找回密码</a></li>
          </ul>
          <form class="login-main" id="login-form" method="POST">
            <label for="login-user-ident" class="g10 c8">
              <input type="text" id="login-user-ident" class="re-input re-input-l" autocomplete="off" spellcheck="false" tabindex="1" placeholder="用户名或邮箱">
            </label>

            <label for="login-password" class="g10 c8">
              <input type="password" id="login-password" class="re-input re-input-l" tabindex="2" placeholder="密码">
            </label>
<?php if ($vr("HOME.login_captcha")): ?>
            <label for="login-captcha" class="g10 c8">
              <a href="" id="change-captcha"><img src="<?php $v( "SYS_CFG.site_url", false ) ?>captcha" alt="" class="captcha"></a>
              <input type="text" id="login-captcha" class="re-input re-input-l" tabindex="3" autocomplete="off" placeholder="结果">
            </label>
<?php endif; ?>
            <div class="actions g10 c8">
              <button type="submit" class="re-button re-button-primary" id="login-action">登录<i class="right"></i></button>
            </div>
          </form>
          <!-- 登录 -->