          <ul class="secondary-nav g10 c8" id="home-selections">
            <li id="selection-login" class=""><a href="<?php $v( "SYS_CFG.site_url", false )?>login/">登录</a></li>
<?php if ($vr("HOME.allow_register")): ?>
            <li id="selection-register" class="single"><a href="<?php $v( "SYS_CFG.site_url", false )?>register/">注册</a></li>
<?php endif; ?>
            <li id="selection-retrieve" class=""><a href="<?php $v( "SYS_CFG.site_url", false )?>retrieve/">找回密码</a></li>
          </ul>
          <form class="register-main" id="register-form" method="POST">
            <label for="register-username" class="g10 c8">
              <input type="text" id="register-username" class="re-input re-input-l" tabindex="1" autocomplete="off" placeholder="用户名">
            </label>
<?php if ($vr("HOME.need_email")): ?>
            <label for="register-email" class="g10 c8">
              <input type="email" id="register-email" class="re-input re-input-l" tabindex="2" placeholder="邮箱地址">
            </label>
<?php endif; ?>
            <label for="register-password" class="g10 c8">
              <button type="button" id="show-password" class="re-button re-button-xs">显</button>
              <input type="password" id="register-password" class="re-input re-input-l" tabindex="3" placeholder="密码">
            </label>

            <label for="register-captcha" class="g10 c8">
              <a href="" id="change-captcha"><img src="<?php $v( "SYS_CFG.site_url", false ) ?>captcha" alt="" class="captcha"></a>
              <input type="text" id="register-captcha" class="re-input re-input-l" tabindex="4" autocomplete="off" placeholder="结果">
            </label>

            <div class="actions g10 c8">
              <button type="submit" class="re-button re-button-primary" id="register-action">注册</button>
            </div>
          </form>
          <!-- 注册 -->