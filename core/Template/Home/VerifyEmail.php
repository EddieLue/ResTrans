          <ul class="secondary-nav g10 c8" id="home-selections">
<?php if ($vr("USER.verification_succeed")): ?>            <li id="verify-success" class="single"><a href="#">邮箱已成功验证</a></li><?php endif; ?>
<?php if (!$vr("USER.verification_succeed")): ?>            <li id="verify-failed" class="single"><a href="#">邮箱验证失败</a></li><?php endif; ?>
          </ul>
          <form class="verification-main" method="POST">
            <div class="actions g10 c8">
<?php if ($vr("USER.verification_succeed")): ?>              <button type="button" class="re-button re-button-primary goto-login">转到登录</button><?php endif; ?>
<?php if (!$vr("USER.verification_succeed")): ?>              <button type="button" class="re-button re-button-primary goto-register">使用另一邮箱注册</button><?php endif; ?>
            </div>
          </form>
          <!-- 验证邮箱 -->