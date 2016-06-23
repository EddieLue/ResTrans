<?php
  $gender = $vr("OTHERSIDE.gender");
 ?>
        <div class="g10 w">
          <div class="g10 c2 m-hidden"></div>
          <div class="g10 c6 profile-top m-w">
            <div class="user-info">
              <div class="pleft">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $v("OTHERSIDE.user_id") ?>" class="avatar-1x profile-avatar"><img src="<?php $avatar($vr("OTHERSIDE.email")) ?>" alt="" class="avatar-img"><div class="username"><?php $v("OTHERSIDE.name") ?></div></a>
                <span class="gender"><?php if (!$gender) $e("保密"); elseif ($gender === 1) $e("男"); elseif ($gender === 2) $e("女"); elseif ($gender === 3) $e("其他") ?></span>
                <span class="email">(<?php $a("OTHERSIDE.public_email", $vr("OTHERSIDE.email"), "邮箱地址不可见"); ?>)</span>
              </div>
              <div class="pright options">
<?php if ($vr("OTHERSIDE.display_account_control")): ?>
                <button class="re-button re-button-xs open-user-control">帐户控制</button>
<?php endif; ?>
              </div>
            </div>
            <ul class="profile-status">
              <li class="status-field r">
                <div class="field-name g6 c2">最后登录时间</div>
                <div class="field-value g6 c4"><?php $v("OTHERSIDE.last_login_time_friendly") ?></div>
              </li>
              <li class="status-field r">
                <div class="field-name g6 c2">注册时间</div>
                <div class="field-value g6 c4"><?php $v("OTHERSIDE.signup_time_friendly") ?></div>
              </li>
            </ul>
            <div class="r padding-3">
              <ul class="secondary-nav">
<?php if ($vr("OTHERSIDE.organizations")): ?>
                <li class="active single"><a href="#"><?php $a("OTHERSIDE.third", "他（她）的", "我的") ?>组织</a></li>
<?php else: ?>
                <li class="active single"><a href="#">未加入任何组织</a></li>
<?php endif; ?>
              </ul>
            </div>
          </div>
        </div>