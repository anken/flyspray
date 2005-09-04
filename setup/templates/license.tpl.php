<?php
// +----------------------------------------------------------------------
// | PHP Source
// +----------------------------------------------------------------------
// | Copyright (C) 2005 by Jeffery Fernandez <developer@jefferyfernandez.id.au>
// +----------------------------------------------------------------------
// |
// | Copyright: See COPYING file that comes with this distribution
// +----------------------------------------------------------------------
//
if (!defined('VALID_FLYSPRAY')) die('Sorry you cannot access this file directly');
?>
      <div id="right">
         <?php echo $message; ?>
         <h1>GNU/LGPL License:</h1>
         <div class="installBlock">
         <p>
         <?php echo $product_name; ?> is Free Software released under the GNU/LGPL License.
         To continue installing <?php echo $product_name; ?> you must read, understand and
         accept the license by checking the "I accept the licence" checkbox.
         </p>
         </div>
         <div class="clr"></div>

         <div class="formBlock" style="width:470px;position:relative;">
            <iframe src="../docs/licences/gnu_lgpl.html" class="license" width="450" frameborder="0" scrolling="auto"></iframe>
         </div>

         <div class="clr"></div>
         <h2>Proceed to Database setup:</h2>
         <div class="installBlock">
         <form class="formBlock farRight" action="index.php" method="post" name="adminForm" style="display:inline;">
            <input type="checkbox" name="agreecheck"  class="inputbox" /> I accept the license&nbsp;
            <input type="hidden" name="action" value="database" />
            <input class="button" type="submit" name="next" value="Next >>" />
         </form>
         <p>
         Please read and accept the licence agreement before proceeding
         </p>
         </div>
      </div><!-- end of right -->
      <div class="clr"></div>
