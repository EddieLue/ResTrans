    <div class="nav-bar">
      <div class="container">
        <div class="pleft">
          <div class="nav-logo m-hidden"><a href="<?php $v("SYS_CFG.site_url", false) ?>start/" title="ResTrans: 首页 / 任务动态"></a></div>
          <div class="nav">
            <ul class="left-nav">
              <li class="<?php if ( $v("NAV.highlight", false, false) === "start" ) { echo "nav-active"; } ?>">
                <a href="<?php $v("SYS_CFG.site_url", false) ?>start/" title="ResTrans: 首页 / 任务动态"><i class="icon-home"></i><span class="m-hidden">首页</span></a>
              </li>
              <li class="<?php if ( $v("NAV.highlight", false, false) === "org" ) { echo "nav-active"; } ?>">
                <a href="<?php $v("SYS_CFG.site_url", false)?>organization/" title="ResTrans: 全部组织列表"><i class="group"></i><span class="m-hidden">组织</span></a>
              </li>
            </ul>
          </div>
        </div>
        <div class="pright">
          <div class="nav-search m-hidden t-hidden">
            <i class="search"></i>
            <form action="<?php $v("SYS_CFG.site_url", 0) ?>search/">
              <input type="text" class="nav-search-input" placeholder="搜索任务或组织···" name="keyword" title="输入搜索关键词">
            </form>
          </div>
          <div class="nav">
            <ul class="right-nav">
              <li class="hidden m-display-block t-display-block">
                <a href="<?php $v("SYS_CFG.site_url", 0) ?>search/"><i class="search"></i></a>
              </li>
<?php if ($vr( "USER.is_login" )): ?>
              <li class="dropdown">
                <a href="#" title="创建新的任务或组织。">
                  <i class="add hidden m-display-inline"></i>
                  <span class="m-hidden">新的工作</span>
                  <i class="down-arrow m-hidden"></i>
                </a>
                <ul class="dropdown-list fixed">
<?php if ($vr("ORG.organization_id")): ?>
                  <li><a href="" id="create-task">新建任务</a></li>
<?php endif; ?>
                  <li><a href="" id="create-organization">创建组织</a></li>
                </ul>
              </li>
              <li class="dropdown-container dropdown-workingset">
                <a href="#drafts" title="返回您最近的工作。">
                  <i class="text hidden m-display-inline"></i>
                  <span class="m-hidden">工作集</span>
                  <i class="down-arrow m-hidden"></i>
                </a>
                <div class="container-inner">
                  <ul class="secondary-nav">
                    <li class="active single"><a href="">所有记录</a></li>
                  </ul>
                  <ul class="draft-list">
                  </ul>
                  <span href="" class="load-drafts hidden">正在加载工作···</span>
                  <span href="" class="no-drafts hidden">没有正在工作的任务</span>
                </div>
              </li>
              <li class="dropdown-container dropdown-notifications">
                <a href="#notifications" title="提醒">
                  <i class="notification hidden m-display-inline"></i>
                  <span class="m-hidden">提醒</span>
                  <i class="red-point<?php $a("NOTICE.point", "", " hidden") ?>"></i>
                  <i class="down-arrow m-hidden"></i>
                </a>
                <div class="container-inner">
                  <div class="organization-notifications">
                    <ul class="secondary-nav">
                      <li class="active single"><a href="">组织</a></li>
                    </ul>
                    <ul class="system-notifications">
                    </ul>
                    <a href="" class="load-notifications">正在加载提醒···</a>
                    <span class="no-notifications hidden">没有提醒</span>
                  </div>
                </div>
              </li>
              <li class="dropdown">
                <a href="#" title="通用选项" class="avatar-1x">
                  <img src="<?php $avatar($vr("USER.email")) ?>" alt="" class="avatar-img">
                  <i class="red-point<?php $a("MESSAGE.point", "", " hidden") ?>"></i>
                  <i class="down-arrow m-hidden"></i>
                </a>
                <ul class="dropdown-list fixed">
                  <li><a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $v("USER.user_id") ?>">个人主页</a></li>
                  <li><a href="#messages" id="show-message">私信<i class="red-point<?php $a("MESSAGE.point", "", " hidden") ?>"></i></a></li>
                  <li><a href="<?php $v("SYS_CFG.site_url", 0) ?>setting/personal/">设置</a></li>
                  <li><a href="#logout" id="logout">退出</a></li>
                </ul>
              </li>
<?php else: ?>
              <!-- 没有登录的情况 -->
              <li><a href="<?php $v("SYS_CFG.site_url", 0) ?>?redirect=<?php $v("HOME.current_url", 0) ?>">登录或注册<i class="right"></i></a></li>
<?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div><!-- nav bar end -->