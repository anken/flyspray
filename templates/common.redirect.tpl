<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="refresh" content="0; url='{$url}'">
    <title>{$this->_title}</title>
  </head>
  <body>
    <div align="center">
    {!sprintf($language['redirectmsg'], '<a href="' . htmlspecialchars($url) . '">', '</a>')}
    </div>
  </body>
</html>