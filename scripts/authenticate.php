<?php
// This script authenticates the user, and sets up a session.
include('../header.php');
$flyspray_prefs = $fs->GetGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
get_language_pack($lang, 'authenticate');

// If logout was requested, log the user out.
if ($_GET['action'] == "logout") {
//  session_start();
//  session_destroy();
  setcookie('flyspray_userid', '', time()-60, '/');
  setcookie('flyspray_passhash', '', time()-60, '/');
  setcookie('flyspray_project', '', time()-60, '/');

   $_SESSION['SUCCESS'] = $authenticate_text['youareloggedout'];
   header("Location: index.php");

   //$message = $authenticate_text['youareloggedout'];

// Otherwise, they requested login.  See if they provided the correct credentials...
} elseif ($_POST['username'] AND $_POST['password']) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  // Get the user's account and group details
  /*$result = $fs->dbQuery("SELECT * FROM flyspray_users, flyspray_groups 
			    WHERE user_name = ? AND group_id = group_in", 
			array($username));*/
			
  $result = $fs->dbQuery("SELECT uig.*, g.group_open, u.account_enabled, u.user_pass FROM flyspray_users_in_groups uig
                          LEFT JOIN flyspray_groups g ON uig.group_id = g.group_id
                          LEFT JOIN flyspray_users u ON uig.user_id = u.user_id
                          WHERE u.user_name = ? AND g.belongs_to_project = ?
                          ORDER BY g.group_id ASC",
                          array($_POST['username'], '0'));

  $auth_details = $fs->dbFetchArray($result);

  // Encrypt the password, and compare it to the one in the database
  if (crypt($password, '4t6dcHiefIkeYcn48B') == $auth_details['user_pass']) {
    
    // Check that the user's account is enabled
    if ($auth_details['account_enabled'] == '1'
       // And that their global group is allowed to login
       && $auth_details['group_open'] == '1') {
      
      $_SESSION['SUCCESS'] = $authenticate_text['loginsuccessful'];
      header("Location: " . $_POST['prev_page']);

      
      //$message = $authenticate_text['loginsuccessful'];

      // Determine if the user should be remembered on this machine
      if ($_POST['remember_login']) {
        $cookie_time = time() + (60 * 60 * 24 * 30); // Set cookies for 30 days
      } else {
        $cookie_time = 0; // Set cookies to expire when session ends (browser closes)
      };

      // Set a couple of cookies
      setcookie('flyspray_userid', $auth_details['user_id'], $cookie_time, "/");
      setcookie('flyspray_passhash', crypt("{$auth_details['user_pass']}", "$cookiesalt"), $cookie_time, "/");
    
      // If the user had previously requested a password change, remove the magic url
      $remove_magic = $fs->dbQuery("UPDATE flyspray_users SET
                                  magic_url = ''
                                  WHERE user_id = ?",
                                  array($auth_details['user_id'])
                                );

    // If the user's account is disabled, throw an error
    } else {
      $_SESSION['ERROR'] = $authenticate_text['loginfailed'] . ' - ' . $authenticate_text['accountdisabled'];
      header("Location: " . $_POST['prev_page']);

      //$message = $authenticate_text['loginfailed'] . '<br /><br />' . $authenticate_text['accountdisabled'];
    };
    
  } else {
    $_SESSION['ERROR'] = $authenticate_text['loginfailed'];
    header("Location: " . $_POST['prev_page']);

    //$message = $authenticate_text['loginfailed'];
  };

} else {
  // If the user didn't provide both a username and a password, show this error:
   $_SESSION['ERROR'] = $authenticate_text['loginfailed'] . ' - ' . $authenticate_text['userandpass'];
   header("Location: " . $_POST['prev_page']);

  //$message = "{$authenticate_text['loginfailed']}<br>{$authenticate_text['userandpass']}";
};
