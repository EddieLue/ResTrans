          <ul class="secondary-nav g10 c8" id="home-selections">
            <li id="verify-success" class="single"><a href="#">邮箱验证未完成</a></li>
          </ul>
          <form class="resend-email" method="POST">
            <label for="email" class="g10 c8">
              <input type="email" id="email" class="re-input re-input-l email-addr" tabindex="2" value="<?php $v("RESEND.email") ?>" placeholder="邮箱地址">
            </label>

            <label for="resend-captcha" class="g10 c8">
              <a href="" id="change-captcha"><img src="<?php $v( "SYS_CFG.site_url", false ) ?>captcha" class="captcha"></a>
              <input type="text" id="resend-captcha" class="re-input re-input-l" tabindex="4" autocomplete="off" placeholder="结果">
            </label>

            <div class="actions g10 c8">
              <button type="submit" class="re-button re-button-primary resend"<?php $a("RESEND.try_again_later", " disabled") ?>><?php $a("RESEND.try_again_later", "1 小时后再试", "重新发送验证邮件") ?></button>
            </div>
          </form>
          <!-- 验证邮箱 -->