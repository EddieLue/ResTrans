<?php
/**
 * --------------------------------------------------
 * (c) ResTrans 2016
 * --------------------------------------------------
 * Apache License 2.0
 * --------------------------------------------------
 * get.restrans.com
 * --------------------------------------------------
*/

namespace ResTrans\Core\Language;

class zh_cn extends LanguageConventions {

  protected $lang = [

    /** 
     * 通用
     */
    "page_not_found"                       => "找不到页面",
    "db_connection_error"                  => "数据库连接错误",
    "params_error"                         => "参数错误",
    "verification_expired"                 => "验证码已过期",
    "invalid_captcha"                      => "验证码错误",
    "user_input_incorrect"                 => "输入有误",
    "send_email_failed"                    => "发送邮件失败",
    "login_successful"                     => "登录成功",
    "token_auth_failed"                    => "token 无效或已过期",
    "register_successful_and_email_sended" => "注册成功，邮件已发送",
    "register_successful"                  => "注册成功",
    "retrieve_email_sended"                => "找回密码邮件已发送",
    "logout_failed"                        => "退出失败",
    "logout_successful"                    => "退出完成",
    "new_password_saved"                   => "密码已更新",
    "set_password_failed"                  => "密码更新失败",
    "user_not_found"                       => "用户不存在",
    "no_result"                            => "没有结果",
    "permission_denied"                    => "没有权限",
    "validate_email_subject"               => "验证您在 ResTrans 上注册的邮箱地址",
    "retrieve_email_subject"               => "找回您在 ResTrans 上注册的账号",
    "verification_email_resended"          => "验证邮件已重新发送",
    "global_options_error"                 => "全局选项读取错误",
    "token_auth_failed"                    => "token 认证错误",
    "non_admin"                            => "非管理员",
    "task_frozen"                          => "任务已冻结",
    "unknown_error_occurred"               => "发生未知的错误",
    "user_can_not_login"                   => "用户无法登录",
    "update_last_login_time_failed"        => "更新最后登录时间失败",
    "create_session_failed"                => "创建会话失败",
    "get_login_cookies_failed"             => "获取登录 Cookie 失败",
    "not_logged_in"                        => "未登录",
    "delete_session_failed"                => "删除会话失败",
    "verification_code_not_found"          => "验证码不存在",
    "remove_verification_code_failed"      => "删除验证码不存在",
    "failed_to_set_user_as_verified"       => "设置用户状态为“已验证”时失败",
    "invalid_username"                     => "用户名不正确",
    "invalid_email"                        => "邮箱不正确",
    "user_already_exists"                  => "用户已存在",
    "user_register_failed"                 => "注册新用户时失败",
    "register_email_send_failed"           => "新用户注册邮件发送失败",
    "set_verification_code_failed"         => "设置新的验证码时失败",
    "retrieve_email_invalid"               => "找回密码的邮箱地址错误",
    "account_unavailable"                  => "此账户不可用",
    "retrieve_account_failed"              => "找回账户失败",
    "send_email_failed"                    => "发送邮件失败",
    "list_retrieve_token_failed"           => "读取找回密码 token 时出错",
    "remove_retrieve_token_failed"         => "删除找回密码 token 出错",
    "token_not_found"                      => "token 不存在",
    "invalid_password"                     => "密码不可用",
    "old_password_verify_failed"           => "旧密码验证失败",
    "set_password_failed"                  => "设置密码失败",
    "session_not_found"                    => "找不到会话密钥",
    "verification_not_found"               => "验证码不存在",
    "zh-CN"                                => "简体中文(大陆)",
    "zh-HK"                                => "繁体中文(港)",
    "zh-TW"                                => "繁体中文(台)",
    "en-UK"                                => "英语(英)",
    "en-US"                                => "英语(美)",
    "ja-JP"                                => "日语",
    "de-DE"                                => "德语",
    "ru-RU"                                => "俄语",
    "fr-FR"                                => "法语",
    "ko-KR"                                => "韩语",

    /** 
     * 私信
     */
    "cannot_send_message"           => "无法发送私信",
    "message_sended"                => "私信已发送",
    "messages_not_found"            => "没有私信",
    "owner_error"                   => "私信所有者错误",
    "conversation_has_been_deleted" => "会话已删除",
    "delete_message_completed"      => "私信已删除",
    "message_content_is_empty"      => "私信内容是空的",
    "content_too_long"              => "私信内容太长",
    "send_message_error"            => "私信发送失败",
    "delete_message_failed"         => "删除私信失败",
    "message_not_found"             => "没有找到私信",
    "delete_conversation_failed"    => "会话删除失败",

    /** 
     * 提醒
     */
    "notification_not_found" => "通知不存在",
    "notification_updated"   => "通知已更新",
    "notification_deleted"   => "通知已删除",

    /** 
     * API
     */
    "api_key_error" => "API 密钥错误",

    /**
     * 任务
     */
    "set_not_found"                   => "文件集不存在",
    "file_not_found"                  => "文件不存在",
    "set_deleted"                     => "文件集已删除",
    "create_task_succeed"             => "任务已创建",
    "create_set_succeed"              => "文件集已创建",
    "no_sets"                         => "没有文件集",
    "worktable_unavailable"           => "工作台不可用",
    "upload_failed"                   => "上传过程出现问题",
    "file_name_error"                 => "文件名错误",
    "file_too_large"                  => "文件太大",
    "file_type_not_accepted"          => "不被接受的文件类型",
    "file_saved"                      => "文件已保存",
    "delete_file_succeed"             => "删除文件完成",
    "no_patch"                        => "补丁是空的",
    "part_patched"                    => "只有部分补丁被接受",
    "all_patched"                     => "全部补丁已接受",
    "translation_update_succeed"      => "翻译已更新",
    "translation_deleted"             => "翻译已删除",
    "settings_saved"                  => "设置已保存",
    "task_deleted"                    => "任务已删除",
    "api_key_updated"                 => "API 密钥已更新",
    "user_settings_saved"             => "用户设置已保存",
    "create_task_failed"              => "创建任务失败",
    "save_task_failed"                => "保存任务失败",
    "name_too_long"                   => "任务名称过长",
    "tasks_not_found"                 => "任务不存在",
    "task_not_found"                  => "任务不存在",
    "set_does_not_belong_to_task"     => "文件集不属于此任务",
    "create_set_failed"               => "创建文件集失败",
    "update_set_total_failed"         => "更新文件集总数失败",
    "file_save_failed"                => "文件保存失败",
    "update_file_total_failed"        => "更新文件总数失败",
    "translation_not_found"           => "翻译不存在",
    "translation_save_failed"         => "翻译保存失败",
    "delete_translation_failed"       => "删除翻译失败",
    "text_cannot_be_empty"            => "翻译不能为空",
    "update_translation_failed"       => "更新翻译失败",
    "update_percentage_failed"        => "更新完成率失败",
    "update_task_percentage_failed"   => "更新任务完成率失败",
    "update_file_name_failed"         => "更新文件名失败",
    "get_best_translations_failed"    => "获得最佳翻译时失败",
    "get_newest_translations_failed"  => "获得最新翻译时失败",
    "delete_file_failed"              => "删除文件失败",
    "delete_files_failed"             => "删除文件失败",
    "delete_set_failed"               => "删除文件集失败",
    "delete_sets_failed"              => "删除文件集失败",
    "delete_translations_failed"      => "删除翻译失败",
    "update_task_status_failed"       => "更新任务状态失败",
    "no_translations_found"           => "没有找到任何译文",
    "update_api_request_count_failed" => "更新 API 请求次数出错",
    "update_api_key_failed"           => "更新 API 密钥错误",
    "reset_api_request_count_failed"  => "重设 API 请求次数出错",

    /**
     * 工作台
     */
    "parse_error"                  => "解析错误",
    "no_file_selected"             => "没有选择文件或文件不存在",
    "download_link_created"        => "下载链接已生成",
    "download_unavailable"         => "此下载不可用",
    "worktable_link_created"       => "工作台链接已生成",
    "record_deleted"               => "记录已删除",
    "record_not_found"             => "找不到记录",
    "update_record_failed"         => "更新记录失败",
    "no_record"                    => "没有记录",
    "worktable_link_create_failed" => "工作台链接创建失败",
    "delete_record_failed"         => "删除记录失败",

    /** 
     * 组织
     */
    "organization_not_found"                 => "找不到组织",
    "create_organization_succeed"            => "组织已创建",
    "discussion_has_been_created"            => "讨论已发表",
    "discussion_comment_has_been_created"    => "讨论回复已发送",
    "discussion_deleted"                     => "讨论已删除",
    "comment_deleted"                        => "回复已删除",
    "already_a_member"                       => "已是组织成员",
    "unable_to_join"                         => "无法加入",
    "organization_full"                      => "组织成员已满",
    "join_organization_succeed"              => "已加入组织",
    "join_organization_request_sended"       => "加入组织的请求已发送",
    "user_exited"                            => "已退出组织",
    "change_property_error"                  => "更改属性出错",
    "user_deleted"                           => "用户已删除",
    "settings_saved"                         => "设置已保存",
    "update_organization_failed"             => "更新组织失败",
    "organization_name_is_too_long_or_empty" => "组织名称为空或太长",
    "description_too_long"                   => "描述过长",
    "create_organization_failed"             => "创建组织失败",
    "save_new_organization_member_failed"    => "组织成员创建失败",
    "update_member_joined_time_failed"       => "更新成员加入时间失败",
    "update_member_total_failed"             => "更新成员总数失败",
    "user_isnt_a_member_of_the_organization" => "用户并非是组织的成员",
    "content_is_too_long"                    => "内容过长",
    "created_discussion_failed"              => "创建讨论失败",
    "update_discussion_total_failed"         => "更新讨论总数失败",
    "discussion_not_found"                   => "讨论不存在",
    "comment_not_found"                      => "回复不存在",
    "parent_comment_not_found"               => "回复对象不存在",
    "created_discussion_comment_failed"      => "回复讨论失败",
    "update_comment_total_failed"            => "更新回复数失败",
    "delete_all_comments_failed"             => "删除所有回复时失败",
    "delete_comment_failed"                  => "删除回复失败",
    "users_not_found"                        => "用户不存在",
    "user_not_found"                         => "用户不存在",
    "update_user_privilege_failed"           => "更新用户权限失败",
    "delete_user_failed"                     => "删除用户失败",
    "delete_users_failed"                    => "删除用户失败",
    "delete_discussions_failed"              => "删除讨论失败",
    "delete_discussion_comments_failed"      => "删除讨论下的回复失败",
    "delete_discussion_failed"               => "删除讨论失败",
    "delete_working_set_records_failed"      => "删除工作集记录失败",
    "update_task_total_failed"               => "更新任务总数失败",
    "delete_tasks_failed"                    => "删除任务失败",
    "delete_organization_failed"             => "删除组织失败",

    /**
     * 搜索
     */
    "no_tasks"         => "没有任何任务",
    "no_organizations" => "没有任何组织",

    /** 
     * 设置
     */
    "profile_settings_saved"               => "资料设置已保存",
    "common_settings_saved"                => "通用设置已保存",
    "security_settings_saved"              => "安全设置已保存",
    "cannot_remove_current_session_id"     => "无法直接删除当前会话",
    "session_deleted"                      => "会话已删除",
    "global_common_settings_saved"         => "全局通用设置已保存",
    "global_register_settings_saved"       => "全局注册设置已保存",
    "no_sessions"                          => "没有任何会话",
    "save_profile_setting_failed"          => "保存资料设置失败",
    "save_common_setting_failed"           => "保存通用设置失败",
    "delete_session_failed"                => "删除会话失败",
    "no_users"                             => "没有任何用户",
    "save_global_common_settings_failed"   => "保存全局通用设置失败",
    "save_global_register_settings_failed" => "保存全局注册设置失败",
    "save_user_settings_failed"            => "保存用户设置失败",

    /**
     * 解析器
     */
    "save_error"             => "保存失败",
    "parse_failed"           => "解析失败",
    "length_error"           => "长度错误",
    "encoding_error"         => "编码错误",
    "check_file_type_failed" => "检查文件类型出错",
    "build_failed"           => "生成失败",

    /**
     * 机器翻译 API
     */
    "api_config_error"       => "机器翻译 API 错误",
    "get_token_failed"       => "API token 获取失败",
    "api_resource_exhausted" => "API 资源已耗尽",

    /**
     * 通知提醒
     */
    "push_notification_failed"    => "推送提醒时错误",
    "no_notification"             => "没有提醒",
    "notification_existed"        => "提醒已存在",
    "notification_not_found"      => "提醒不存在",
    "update_status_failed"        => "更新状态失败",
    "notification_destroy_failed" => "销毁提醒时错误",
  ];
}