          <ul class="secondary-nav g10 c8" id="home-selections">
            <li id="selection-login" class=""><a href="<?php $v( "SYS_CFG.site_url", false ) ?>login/">登录</a></li>
<?php if ($vr("HOME.allow_register")): ?>
            <li id="selection-register" class=""><a href="<?php $v( "SYS_CFG.site_url", false )?>register/">注册</a></li>
<?php endif; ?>
            <li id="selection-retrieve" class="single"><a href="<?php $v( "SYS_CFG.site_url", false ) ?>retrieve/">找回密码</a></li>
          </ul>
          <form class="register-main" id="retrieve-form" method="POST">

            <label for="retrieve-email" class="g10 c8">
              <input type="email" id="retrieve-email" class="re-input re-input-l" tabindex="1" autocomplete="off" placeholder="邮箱地址">
            </label>

            <label for="retrieve-captcha" class="g10 c8">
              <a href="" id="change-captcha"><img src="<?php $v( "SYS_CFG.site_url", false ) ?>captcha" alt="" class="captcha"></a>
              <input type="text" id="retrieve-captcha" class="re-input re-input-l" tabindex="2" autocomplete="off" placeholder="结果">
            </label>

            <div class="actions g10 c8">
              <button type="submit" class="re-button re-button-primary" id="retrieve-action">发送找回密码邮件</button>
            </div>
          </form>
          <!-- 找回密码 -->