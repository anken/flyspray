<?php

if (isset($_GET['p'])) {
   $page = $_GET['p'];
} else {
   $page = '1';
};

session_start();
include('../functions.inc.php');
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Flyspray setup</title>
  <link rel="icon" href="../favicon.ico" type="image/png" />
  <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
  <link href="../themes/Bluey/theme.css" rel="stylesheet" type="text/css" />
</head>
<body>

<h1 id="title"></h1>

<br />

<div id="content" style="min-height: 50%;">

<?php

if (file_exists('SETUP_HAS_RUN')) {
   die('Setup has already been completed.  You cannot run it again.');
};

echo '<h3>Flyspray setup</h3>';

//////////////////////////////
// Page one, the intro page //
//////////////////////////////
if ($page == '1') {
   
   echo 'Flyspray does not appear to be set up on this server.  Click the button below to start the configuration process.';
   echo '<br /><br />';
   echo "\n";
   echo '<form action="install-0.9.7.php" method="get">';
   echo "\n";
   echo '<input type="hidden" name="p" value="2" />';
   echo "\n";
   echo '<input class="adminbutton" type="submit" value="Continue to next page" />';
   echo "\n";
   echo '</form>';
   
   
//////////////////////////////////////////////////////////
// Page two, where the user enters their config details //
//////////////////////////////////////////////////////////
} elseif ($page == '2') {
?>

Adjust the settings below to suit your system's setup.

<br /><br />

<?php
// This line gets the operating system so that we know which way to put slashes in the path
strstr( PHP_OS, "WIN") ? $slash = "\\" : $slash = "/";
$basedir = realpath('../') . $slash;
$adodbpath = $basedir . 'adodb' . $slash . 'adodb.inc.php';

if (!isset($_SESSION['basedir'])) {
   $_SESSION['basedir'] = $basedir;
   $_SESSION['adodbpath'] = $adodbpath;
   $_SESSION['dbname'] = 'flyspray';
   $_SESSION['dbhost'] = 'localhost';
   $_SESSION['dbuser'] = 'root';
};
?>

<form action="install-0.9.7.php?p=3" method="post">

<fieldset>
<legend>General</legend>
<table cellpadding="5">
   <tr>
      <td>Filesystem path to the main Flyspray directory:<br />
      <i style="font-size: smaller;">The trailing slash is required</i>
      </td>
      <td>
      <input type="text" size="50" maxlength="200" name="basedir" value="<?php echo $_SESSION['basedir'];?>" /></td>
   </tr>
   <tr>
      <td>
      Location of the adodb.inc.php file:<br />
      <i style="font-size: smaller;">Include 'adodb.inc.php' in this field, as shown</i>
      </td>
      <td><input type="text" size="50" maxlength="200" name="adodbpath" value="<?php echo $_SESSION['adodbpath'];?>" /></td>
   </tr>
</table>
</fieldset>

<br />

<fieldset>
<legend>Database</legend>
<table cellpadding="7">
   <tr>
      <td>
      Database server type:<br />
      <i style="font-size: smaller;">Other database types may work, but are unsupported</i>
      </td>
      <td>
         <select name="dbtype">
            <option value="mysql" <?php if ($_SESSION['dbtype'] == 'mysql') { echo 'SELECTED';};?>>MySQL</option>
            <option value="pgsql" <?php if ($_SESSION['dbtype'] == 'pgsql') { echo 'SELECTED';};?>>PostgreSQL</option>
         </select>
      </td>
   </tr>
   <tr>
      <td>Database server hostname (or domain name):</td>
      <td><input type="text" size="20" maxlength="200" name="dbhost" value="<?php echo $_SESSION['dbhost'];?>" /></td>
   </tr>
   <tr>
      <td>Database name:<br />
      <i style="font-size: smaller;">This database must already exist.</i>
      </td>
      <td>
      <input type="text" size="20" maxlength="200" name="dbname" value="<?php echo $_SESSION['dbname'];?>" />
      </td>
   </tr>
   <tr>
      <td>Database server username:<br />
      <i style="font-size: smaller;">This database user must already exist.</i>
      </td>
      <td>
      <input type="text" size="20" maxlength="200" name="dbuser" value="<?php echo $_SESSION['dbuser'];?>" />
      </td>
   </tr>
   <tr>
      <td>Database server password:</td>
      <td><input type="password" size="20" maxlength="200" name="dbpass" value="<?php echo $_SESSION['dbpass'];?>" /></td>
   </tr>
</table>

<br /><br />

<table cellpadding="5">
   <tr>
      <td>
      <input class="adminbutton" type="submit" value="Continue to next page" />
      </td>
   </tr>
</table>
</form>
</fieldset>



<?php
/////////////////////////////////////////////////////
// Page three, check that submitted values are ok. //
/////////////////////////////////////////////////////
} elseif ($page == '3') {

   $_SESSION['basedir'] = stripslashes($_POST['basedir']);
   $_SESSION['adodbpath'] = stripslashes($_POST['adodbpath']);
   $_SESSION['dbtype'] = $_POST['dbtype'];
   $_SESSION['dbname'] = $_POST['dbname'];
   $_SESSION['dbhost'] = $_POST['dbhost'];
   $_SESSION['dbuser'] = $_POST['dbuser'];
   $_SESSION['dbpass'] = $_POST['dbpass'];

   if ($_POST['basedir'] != ''
      && $_POST['adodbpath'] != ''
      && $_POST['dbhost'] != ''
      && $_POST['dbname'] != ''
      && $_POST['dbuser'] != ''
      && $_POST['dbpass'] != ''
      ) {
         
         // Now, check for the correct path to the adodb.inc.php file
         if (!file_exists($_SESSION['adodbpath']))
            
            echo 'The path to adodb.inc.php wasn\'t set correctly.  Press your browser\'s BACK button to return to the previous page.';
         
         // If the adodbpath is correct, continue to saving flyspray.conf.php
         $filename = '../flyspray.conf.php';         
         
         // Delete flyspray.conf.php, then re-create it
         @unlink($filename);
         @copy("flyspray.conf.skel", "../flyspray.conf.php");
         
         $somecontent = '
         
[general]
basedir = "' . $_SESSION['basedir'] . '"      ; Location of your Flyspray installation
cookiesalt = "4t"                             ; Randomisation value for cookie encoding
adodbpath = "' . $_SESSION['adodbpath']. '"   ; Path to the main ADODB include file
output_buffering = "on"                       ; Available options: "off", "on" and "gzip"

[database]
dbtype = "' . $_SESSION['dbtype'] . '"        ; Type of database ("mysql" or "pgsql") 
dbhost = "' . $_SESSION['dbhost'] . '"        ; Name or IP of your database server
dbname = "' . $_SESSION['dbname'] . '"        ; The name of the database
dbuser = "' . $_SESSION['dbuser'] . '"        ; The user to access the database
dbpass = "' . $_SESSION['dbpass'] . '"        ; The password to go with that username above
';

// Let's make sure the file exists and is writable first.
if (is_writable($filename)) {

   // In our example we're opening $filename in append mode.
   // The file pointer is at the bottom of the file hence
   // that's where $somecontent will go when we fwrite() it.
   if (!$handle = fopen($filename, 'a')) {
         echo "Cannot open file ($filename)";
         exit;
   };

   // Write $somecontent to our opened file.
   if (fwrite($handle, $somecontent) === FALSE) {
       echo "Cannot write to file ($filename)";
       exit;
   };
  
   echo 'Your config settings were successfully saved to flyspray.conf.php';
   echo '<br /><br />';
   echo 'FIXME: Generate a cookie salt on this page, instead of using the same one for every install.';
   echo '<br /><br />';
   echo 'Next, we are going to try setting up your database using the settings you just provided.';
   echo 'Note that the next page may be a little slow to load, because it has a lot of database work to do.';
   echo '<br /><br />';
   echo "\n";
   echo '<form action="install-0.9.7.php" method="get">';
   echo "\n";
   echo '<input type="hidden" name="p" value="4" />';
   echo "\n";
   echo '<input class="adminbutton" type="submit" value="Continue to next page" />';
   echo "\n";
   echo '</form>';
  
   fclose($handle);

} else {
   echo "The file $filename is not writable";
};

   // If the user hasn't filled in all the fields
   } else {
      
      echo 'You need to fill in all the fields.  <a href="install-0.9.7.php?p=2">Go back and finish it.</a>';
      
   };


/////////////////////////////////////////////////////
// Page Four, where we insert the database schema. //
/////////////////////////////////////////////////////
} elseif ($page == '4') {

// Activate adodb   
include_once ($_SESSION['adodbpath']);

// Define our functions class
$fs = new Flyspray;

// Open a connection to the database
$res = $fs->dbOpen($_SESSION['dbhost'], $_SESSION['dbuser'], $_SESSION['dbpass'], $_SESSION['dbname'], $_SESSION['dbtype']);
if (!$res) {
  die('Flyspray was unable to connect to the database.  Go back and <a href="install-0.9.7.php?p=2">Check your settings!</a>');
}

// Retrieve th database schema into a string
$sql_file = file_get_contents('flyspray-0.9.7.' . $_SESSION['dbtype']);

// Separate each query
$sql = explode(';', $sql_file);

// Cycle through the queries and insert them into the database
while (list($key, $val) = each($sql)) {
   $insert = $fs->dbQuery($val);
};

// Add code to detect the URL to Flyspray, and put it in the $base_url variable
// Then update the database with it
//$update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = $base_url WHERE pref_name = 'base_url'");

// Create a 'completed' file to stop this script running again
touch($_SESSION['basedir'] . 'sql/SETUP_HAS_RUN');

echo 'The Flyspray configuration process is complete.  The Flyspray developers hope that you have many hours';
echo 'of increased productivity though the use of this software.  If you find Flyspray useful, please consider';
echo 'returning to the Flyspray website and <a href="http://flyspray.rocks.cc/?p=Download">making a donation</a>.';
echo '<br /><br />';
echo '<a href="../>Take me to Flyspray 0.9.7 now!</a>';
echo '<br /><br />';
echo 'Also remember to set some of the global prefs in the database, like the base url.';
echo '<br /><br />';

// End of pages
};
?>

</div>

</body>

</html>