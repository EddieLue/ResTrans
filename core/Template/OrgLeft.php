        <div class="g10 c3 organization-left m-w pc-pull-left m-pull-right m-text-center">
          <a href="" class="organization-name"><?php $v( "ORG.name" ) ?></a>
          <span class="organization-meta"><a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $v("ORG.user_id") ?>"><?php $v("ORG.user_name") ?></a> 创建于 <?php $v("ORG.organization_created" ) ?></span>
          <p class="organization-description"><?php $e($vr("ORG.description"), null, 1, 1) ?></p>
          <div class="goto r">
            <?php if ($vr("USER.is_member_of_org")): ?>
            <button class="re-button re-button-xs g10 w exit-organization">退出该组织</button>
            <?php else: ?>
              <?php if ($vr("ORG.join_request_sended")): ?>
            <button class="re-button re-button-xs g10 w join-organization" disabled>等待创建者决定</button>
              <?php else: ?>
            <button class="re-button re-button-xs g10 w join-organization">加入</button>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>