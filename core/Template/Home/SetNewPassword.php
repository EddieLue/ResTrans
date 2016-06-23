          <ul class="secondary-nav g10 c8" id="home-selections">
            <li class="single"><a href="#">设置你的新密码</a></li>
          </ul>
          <form class="set-new-password" method="POST">
            <label for="new-password" class="g10 c8">
              <button id="show-password" class="re-button re-button-xs">显示密码</button>
              <input type="password" id="new-password" class="re-input re-input-l new-password-input" tabindex="1" placeholder="新密码">
            </label>
            <label for="captcha" class="g10 c8">
              <a href="" id="change-captcha"><img src="<?php $v( "SYS_CFG.site_url", false ) ?>captcha" alt="" class="captcha"></a>
              <input type="text" id="captcha" class="re-input re-input-l" tabindex="2" autocomplete="off" placeholder="结果">
            </label>
            <div class="actions g10 c8">
              <button type="submit" class="re-button re-button-primary save-new-password">完成</button>
            </div>
          </form>
          <!-- 找回密码 -->