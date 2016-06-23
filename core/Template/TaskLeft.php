        <div class="task-left g6 c2 m-w pc-pull-left m-pull-right">
          <div class="task-lang" title="此任务的默认语言设定，但不代表其中的文件遵循此设定。">
            <span class="original"><?php $v("TASK.friendly_original_language") ?></span>
            <span class="target"><?php $v("TASK.friendly_target_language") ?></span>
          </div>
            <a href="<?php $v("SYS_CFG.site_url", 0) ?>task/<?php $v("TASK.task_id") ?>" class="task-name"><?php $v("TASK.name") ?></a>
          <p class="task-description m-text-center"><?php $e($vr("TASK.description"), null, 1, 1) ?></p>
          <div class="task-info r">
            <div class="creator g6 c4"><a href="<?php $v("SYS_CFG.site_url", 0) ?>organization/<?php $v("TASK.organization_id") ?>"><?php $v("ORG.name") ?></a><span> &raquo; </span><a href="<?php $v("SYS_CFG.site_url", 0) ?>profile/<?php $v("TASK.user_id") ?>"><?php $v("TASK.user_name") ?></a></div>
            <div class="datetime g6 c2">创建于 <?php $v("TASK.friendly_time") ?></div>
          </div>
        </div>