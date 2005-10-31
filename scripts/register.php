<?php
$fs->get_language_pack('register');

// If the application preferences require the use of
// confirmation codes, use this script
if ($fs->prefs['spam_proof'] != '1'
    || $fs->prefs['anon_reg'] != '1'
    || Cookie::has('flyspray_userid'))
{
    $fs->Redirect( $fs->CreateURL('error', null) );
}

// If the user came here from their notification link
if (Get::has('magic')):
   // Check that the magic url is valid
    $check_magic = $db->Query("SELECT * FROM {registrations}
                               WHERE magic_url = ?",
                               array(Get::val('magic')));

    if (!$db->CountRows($check_magic)) {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }
?>
    <h1><?php echo $register_text['registernewuser'];?></h1>
    <form action="<?php echo $conf['general']['baseurl'];?>index.php" name="form2" method="post" id="registernewuser">
      <table class="admin">
        <tr>
          <td colspan="2">
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="registeruser" />
          <input type="hidden" name="magic_url" value="<?php echo Get::val('magic');?>" />
          <?php echo $register_text['entercode']; ?>
          </td>
        </tr>
        <tr>
          <td><label for="confirmationcode"><?php echo $register_text['confirmationcode']; ?></label></td>
          <td><input id="confirmationcode" name="confirmation_code" type="text" size="20" maxlength="20" /><strong>*</strong></td>
        </tr>
        <tr>
          <td><label for="userpass"><?php echo $register_text['password'];?></label></td>
          <td><input id="userpass" name="user_pass" type="password" size="20" maxlength="100" /><strong>*</strong></td>
        </tr>
        <tr>
          <td><label for="userpass2"><?php echo $register_text['confirmpass'];?></label></td>
          <td><input id="userpass2" name="user_pass2" type="password" size="20" maxlength="100" /><strong>*</strong></td>
        </tr>
        <tr>
          <td colspan="2" class="buttons">
          <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $register_text['registeraccount'];?>" />
          </td>
        </tr>
      </table>
    </form>

<?php else: // If there was no magic url specified ?>

    <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="registernewuser">

    <h1><?php echo $register_text['registernewuser'];?></h1>

    <p><em><?php echo $register_text['requiredfields'];?></em> <strong>*</strong></p>

      <table class="admin">
        <tr>
          <td>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="sendcode" />
            <label for="username"><?php echo $register_text['username'];?></label></td>
          <td><input id="username" name="user_name" type="text" size="20" maxlength="20" /><strong>*</strong></td>
        </tr>
        <tr>
          <td><label for="realname"><?php echo $register_text['realname'];?></label></td>
          <td><input id="realname" name="real_name" type="text" size="20" maxlength="100" /><strong>*</strong></td>
        </tr>
        <tr>
          <td><label for="emailaddress"><?php echo $register_text['emailaddress'];?></label></td>
          <td><input id="emailaddress" name="email_address" type="text" size="20" maxlength="100" /></td>
        </tr>
        <tr>
          <td><label for="jabberid"><?php echo $register_text['jabberid'];?></label></td>
          <td><input id="jabberid" name="jabber_id" type="text" size="20" maxlength="100" /></td>
        </tr>
        <tr>
          <td><label><?php echo $register_text['notifications'];?></label></td>
          <td>
          <input type="radio" name="notify_type" value="1" checked="checked" /><?php echo $register_text['email'];?> <br />
          <input type="radio" name="notify_type" value="2" /><?php echo $register_text['jabber'];?>
          </td>
        </tr>
        <tr>
          <td colspan="2">
          <?php echo $register_text['note'];?>
          </td>
        </tr>
        <tr>
          <td colspan="2" class="buttons">
          <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $register_text['sendcode'];?>" />
          </td>
        </tr>
      </table>
    </form>

<?php endif; // End of checking for magic_url ?>
