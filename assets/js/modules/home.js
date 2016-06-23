/** 首页模块 */
define( [ "backbone", "notifications" ], function( Backbone, Notifications ){

  var UserLogin = Backbone.Model.extend( {

    defaults: {
      token: _s["APP.token"]
    },
    urlRoot: _s["SYS_CFG.site_uri"] + "user/login/",
    validate: function ( attrs, options ) {

      if ( "" === attrs.userident ) return "userident_is_empty";
      if ( "" === attrs.password ) return "password_is_empty";
    }

  } );

  var UserRegister = Backbone.Model.extend( {

    defaults: {
      token: _s["APP.token"]
    },
    urlRoot: _s["SYS_CFG.site_uri"] + "user/register/",
    validate: function ( attrs, options ) {

      if ( "" === attrs.username ) return "username_is_empty";
      if ( "" === attrs.password ) return "password_is_empty";
    }

  } );

  var UserRetrieve = Backbone.Model.extend( {

    defaults: {
      token: _s["APP.token"]
    },
    urlRoot: _s["SYS_CFG.site_uri"] + "user/retrieve/",
    validate: function ( attrs, options ) {

      if ( "" === attrs.useremail ) return "useremail_is_empty";
    }

  } );

  var NewPassword = Backbone.Model.extend( {
    defaults: {
      token: _s["APP.token"]
    },
    urlRoot: _s["SYS_CFG.site_uri"] + "user/password/reset/",
  } );

  var HomeBase = Backbone.View.extend( {

    el: "body",
    _changeButtonState: function ( $button, disable, html ) {

      if( disable ) {
        $button.attr( "disabled", "disabled" );
      } else {
        $button.removeAttr("disabled");
      }

      $button.html( html );
    },
    changeCaptcha: function ( e ) {

      $( "img.captcha" ).attr( "src", _s["SYS_CFG.site_uri"] + "captcha?" + Math.random() );
      e.preventDefault();
    }

  } );

  var Login = HomeBase.extend( {

    events: {
      "submit form#login-form": "login",
      "click #change-captcha": "changeCaptcha"
    },

    initialize: function () {

      this.$userIdentInput = this.$el.find( "#login-user-ident" );
      this.$passwordInput = this.$el.find( "#login-password" );
      this.$captchaInput = this.$el.find( "#login-captcha" );
      this.$loginActionButton = this.$el.find( "#login-action" );

    },
    login: function ( e ) {

      this._changeButtonState( this.$loginActionButton, true, "正在登录···" );
      this.model.set( { userident: this.$userIdentInput.val(),
                        password: this.$passwordInput.val(),
                        captcha: this.$captchaInput.val() } );

      this.listenToOnce( this.model, "invalid", function () {

        if ( "userident_is_empty" === this.model.validationError ) {
          this.$userIdentInput.addClass("re-input-error");
        } else {
          this.$userIdentInput.removeClass("re-input-error");
        }
        if ( "password_is_empty" === this.model.validationError ) {
          this.$passwordInput.addClass("re-input-error");
        } else {
          this.$passwordInput.removeClass("re-input-error");
        }
        this._changeButtonState( this.$loginActionButton, false, "登录<i class=\"right\"></i>" );

      } );

      var that = this;
      var saveSuccess = function ( model, response, options ) {

        if ( "login_successful" === response.status_short ) {
          that._changeButtonState( that.$loginActionButton, true, "请稍候···" );
          location.href= _s["HOME.redirect"] ? 
           decodeURIComponent(_s["HOME.redirect"]) : _s["SYS_CFG.site_url"] + "start";
        }

        if ( "login_successful_verify_email" === response.status_short ) {
          that._changeButtonState( that.$loginActionButton, true, "需要完善一些信息···" );
          location.href = _s["SYS_CFG.site_url"] + "user/email/resend/";
        }

      };
      var saveError = function ( model, response, options ) {

        var data = response.responseJSON;

        ( data.status_detail !== "" ) ? Notifications.push( data.status_detail, "warning" ) : "";

        if ( data.token ) {

          that.model.set( { "token": data.token } );
          _s["APP.token"] = data.token;
        }

        $( "img.captcha" ).attr( "src", _s["SYS_CFG.site_uri"] + "captcha?" + Math.random() );
        that._changeButtonState( that.$loginActionButton, false, "登录<i class=\"right\"></i>" );
      };

      this.model.save( null, { success: saveSuccess,
                               error:   saveError } );

      e.preventDefault();
    }

  } );

  var Register = HomeBase.extend( {

    events: {
      "submit form#register-form": "register",
      "click #show-password": "showPassword",
      "click #change-captcha": "changeCaptcha"
    },

    initialize: function () {

      this.$usernameInput = this.$el.find("#register-username");
      this.$emailInput = this.$el.find("#register-email");
      this.$passwordInput = this.$el.find("#register-password");
      this.$captchaInput = this.$el.find("#register-captcha");
      this.$registerActionButton = this.$el.find("#register-action");
      this.$showPasswordAction = this.$el.find("#show-password");

    },

    showPassword: function ( e ) {
      e.preventDefault();

      var $passwordInputType = this.$passwordInput.attr("type");
      if ( "password" === $passwordInputType ) {
        this.$passwordInput.attr( "type", "text" );
        this.$showPasswordAction.text("藏");
        return;
      }
      this.$showPasswordAction.text( "显" );
      this.$passwordInput.attr( "type", "password" );
    },

    register: function ( e ) {

      this._changeButtonState( this.$registerActionButton, true, "正在注册···" );
      var modelData = { username: this.$usernameInput.val(),
                        password: this.$passwordInput.val(),
                        captcha: this.$captchaInput.val() };
      if ( 1 === +this.$emailInput.length ) modelData.useremail = this.$emailInput.val();
      this.model.set( modelData );
      this.listenToOnce( this.model, "invalid", function() {
        if ( "username_is_empty" === this.model.validationError ) {
          this.$usernameInput.addClass("re-input-error");
        } else {
          this.$usernameInput.removeClass("re-input-error");
        }
        if ( "password_is_empty" === this.model.validationError ) {
          this.$passwordInput.addClass("re-input-error");
        } else {
          this.$passwordInput.removeClass("re-input-error");
        }
        this._changeButtonState( this.$registerActionButton, false, "注册" );
      } );

      var that = this;
      var saveSuccess = function ( model, response, options ) {

        that._changeButtonState( that.$registerActionButton, true, response.status_detail );

        that.$emailInput.parent("label").hide();
        that.$passwordInput.parent("label").hide();
        that.$captchaInput.parent("label").hide();
        that.$usernameInput.siblings("span").text("使用此用户名登录");
        that.$usernameInput.attr( "disabled", "disabled" );

      };
      var saveError = function ( model, response,options ) {

        var data = response.responseJSON;
        var allowDisplay = [ "invalid_username",
                             "invalid_password",
                             "invalid_email",
                             "username_unable_to_use",
                             "email_unable_to_use",
                             "unknown_error_occurred",
                             "invalid_captcha",
                             "token_auth_failed" ];

        if ( -1 !== _.indexOf( allowDisplay, data.status_short ) ) {
          Notifications.push( data.status_detail, "warning" );
        }

        if ( data.token ) {
          that.model.set( { "token": data.token } );
          _s["APP.token"] = data.token;
        }

        $( "img.captcha" ).attr( "src", _s["SYS_CFG.site_uri"] + "captcha?" + Math.random() );
        that._changeButtonState( that.$registerActionButton, false, "注册" );
      }

      this.model.save( null, { success: saveSuccess, error:saveError } );
      e.preventDefault();
    }

  } );

  var Retrieve = HomeBase.extend( {

    events: {
      "submit form#retrieve-form": "retrieve",
      "click #change-captcha": "changeCaptcha"
    },

    initialize: function () {

      this.$emailInput = this.$el.find("#retrieve-email");
      this.$captchaInput = this.$el.find("#retrieve-captcha");
      this.$retrieveActionButton = this.$el.find("#retrieve-action");
    },

    retrieve: function ( e ) {

      e.preventDefault();
      this._changeButtonState( this.$retrieveActionButton, true, "请求发送邮件···" );
      this.model.set( { useremail: this.$emailInput.val(),
                        captcha: this.$captchaInput.val() } );

      this.listenToOnce( this.model, "invalid", function ( ){
        if ( "useremail_is_empty" === this.model.validationError ) {
          this.$emailInput.addClass("re-input-error");
        } else {
          this.$emailInput.removeClass("re-input-error");
        }
      } );

      var that = this;
      var saveSuccess = function ( model, response, options ) {

        that.$captchaInput.parent("label").hide();
        that.$emailInput.attr("disabled", "disabled");
        that._changeButtonState( that.$retrieveActionButton, true, "找回密码邮件已发送" );

      };

      var saveError = function ( model, response, options ) {

        var data = response.responseJSON;
        var allowDisplay = [ "retrieve_email_invalid",
                             "account_can_not_use",
                             "token_already_exist",
                             "unknown_error_occurred",
                             "retrieve_email_send_failure",
                             "invalid_captcha",
                             "token_auth_failed" ];

        if ( -1 !== _.indexOf( allowDisplay, data.status_short ) ) {
          Notifications.push( data.status_detail, "warning" );
        }

        if ( data.token ) {

          that.model.set( { "token": data.token } );
          _s["APP.token"] = data.token;
        }

        $( "img.captcha" ).attr( "src", _s["SYS_CFG.site_uri"] + "captcha?" + Math.random() );
        that._changeButtonState( that.$retrieveActionButton, false, "发送找回密码邮件" );
      }

      this.model.save( null, { success: saveSuccess, error:saveError } );
    }

  } );

  var Verfiy = HomeBase.extend({
    el: ".verification-main",
    events: {
      "click .goto-login": "gotoLogin",
      "click .goto-register": "gotoRegister"
    },

    gotoLogin: function (e) {
      e && e.preventDefault();
      location.href = _s["SYS_CFG.site_url"] + "login/";
    },

    gotoRegister: function (e) {
      e && e.preventDefault();
      location.href = _s["SYS_CFG.site_url"] + "register/";
    }
  });

  var SetNewPassword = HomeBase.extend({
    el: "body",
    events: {
      "click #change-captcha": "changeCaptcha",
      "click #show-password": "showPassword",
      "submit .set-new-password": "saveNewPassword"
    },

    initialize: function () {
      this.$passwordInput = this.$(".new-password-input");
    },

    saveNewPassword: function (e) {
      e && e.preventDefault();

      this._changeButtonState( this.$(".save-new-password"), true, "请求更改密码···" );
      this.model.save({
        "retrieve_token": _s["USER.retrieve_token"],
        "new_password": this.$passwordInput.val()
      }, {
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "new_password_saved") {
            this._changeButtonState( this.$(".save-new-password"), true, "正在转到登录···" );
            window.setTimeout(function () {
              location.href = _s["SYS_CFG.site_url"] + "login/";
            }, 3000);
          }
        },
        error: function (m, resp) {
          if (resp && resp.token) {
            this._changeButtonState( this.$(".save-new-password"), false, "完成" );
            _s["APP.token"] = resp.token;
            _s.reqErrorHandle(resp, "请求失败：", null, true, "warning", Notifications);
          }
        },
      });
    },

    showPassword: function ( e ) {
      e.preventDefault();

      var $passwordInputType = this.$passwordInput.attr("type");
      if ( "password" === $passwordInputType ) {
        this.$passwordInput.attr( "type", "text" );
        $(e.target).text("隐藏密码");
        return;
      }
      $(e.target).text( "显示密码" );
      this.$passwordInput.attr( "type", "password" );
    },
  });

  var ResendEmailView = HomeBase.extend({
    el: "body",

    events: {
      "submit .resend-email": "emailResend",
      "click #change-captcha": "changeCaptcha",
    },

    initialize: function () {
      this.emailModel = new (Backbone.Model.extend({
        url: _s["SYS_CFG.site_uri"] + "user/email/resend/"
      }));
      console.log(this.emailModel);
      this.$resendButton = this.$(".resend");
    },

    emailResend: function (e) {
      e && e.preventDefault();

      this._changeButtonState( this.$resendButton, true, "请求发送新的邮件···" );
      this.emailModel.save({
        "token": _s["APP.token"],
        "email": this.$(".email-addr").val()
      }, {
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "verification_email_resended") {
            this._changeButtonState( this.$resendButton, true, resp.status_detail);
          }
        },
        error: function (m, resp) {
          if (resp && resp.token) {
            this._changeButtonState( this.$resendButton, false, "完成" );
            _s["APP.token"] = resp.token;
            _s.reqErrorHandle(resp, "请求失败：", null, true, "warning", Notifications);
          }
        }
      });
    }
  });

  if ( $( "#login-form" ).length ) new Login( { model: new UserLogin() } );
  if ( $( "#register-form" ).length ) new Register( { model: new UserRegister() } );
  if ( $( "#retrieve-form" ).length ) new Retrieve( { model: new UserRetrieve() } );
  if ( $( ".verification-main" ).length ) new Verfiy();
  if ( $( ".set-new-password" ).length ) new SetNewPassword({model: new NewPassword()});
  if ( $( ".resend-email" ).length ) new ResendEmailView();
} );