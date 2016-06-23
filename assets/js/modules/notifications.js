/** 通知 */
define( [ "backbone" ], function ( Backbone ) {

  var $notifications = $(".notifications");
  var $notificationTemplate = $("#notification-template");

  var push = function ( message, noticeStyle ) {

    var tmpl = _.template( $notificationTemplate.html() );
    var noticeId = _.uniqueId("notify_");

    $notifications.append( tmpl( {
      notificationId: noticeId,
      notificationContent: message
    } ) );

    var $notice = $notifications.find("#" + noticeId);

    $notice.addClass("show");

    if ( undefined !== noticeStyle && -1 !== _.indexOf( ["primary", "warning", "success"], noticeStyle ) ) $notice.addClass(noticeStyle);

    $notice.click( function () {
      this.remove();
    } );

    var removeNotice = setInterval( function() {
      if ( $notifications.find( "#" + noticeId ).css("marginLeft") !== "221px" ) return;
      $notifications.find("#" + noticeId).remove();
      clearInterval(removeNotice);
    }, 2000 );

  };

  return { push: push };
} );