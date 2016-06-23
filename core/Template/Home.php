<?php $v->other( "Header" ) ?>

  <div class="home">
    <div class="container">
      <div class="g8 c3 inner m-w">
        <div class="logo"></div>
        <div class="region g10 c9">

<?php 
  switch ( $v( "HOME.form", false, false ) ) :
    case 'login':
      $v->other( "Home/LoginForm" );
    break;
    case 'register':
      $v->other( "Home/RegisterForm" );
    break;
    case 'retrieve':
      $v->other( "Home/RetrieveForm" );
    break;
    case 'email_verify':
      $v->other( "Home/VerifyEmail" );
    break;
    case 'password_reset':
      $v->other( "Home/SetNewPassword" );
    break;
    case 'resend_email':
      $v->other( "Home/ResendEmail" );
    break;

  endswitch;
?>

        </div>
      </div>
    </div>
    <div class="index-footer g10 c3">
      &copy;&nbsp;<?php echo date("Y"); ?>&nbsp;<a href="http://get.restrans.com/" title="ResTrans: 了解更多">ResTrans</a>
    </div>
  </div>

<?php $v->other( "Footer" ) ?>