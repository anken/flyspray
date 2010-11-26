<?php

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once('class.field.php');
require_once('class.textformatter.php');

class Tpl
{
    var $_uses  = array();
    var $_vars  = array();
    var $_theme = '';
    var $_tpls  = array();
    var $_title = '';

    function uses()
    {
        $args = func_get_args();
        $this->_uses = array_merge($this->_uses, $args);
    }

    function assign($arg0 = null, $arg1 = null)
    {
        if (is_string($arg0)) {
            $this->_vars[$arg0] = $arg1;
        } elseif (is_array($arg0)) {
            $this->_vars += $arg0;
        } elseif (is_object($arg0)) {
            $this->_vars += get_object_vars($arg0);
        }
    }

    function setTheme($theme = '')
    {
        // Check available themes
        $themes = Flyspray::listThemes();
        if (in_array($theme, $themes)) {
            $this->_theme = $theme.'/';
        } else {
            $this->_theme = $themes[0].'/';
        }
    }

    function setTitle($title)
    {
        $this->_title = $title;
    }

    function themeUrl()
    {
        return FSTpl::relativeUrl($GLOBALS['baseurl']) . 'themes/'.$this->_theme;
    }

    function compile(&$item)
    {
        if (strncmp($item, '<?', 2)) {
            // php function calls in templates look like {!function(arg)}
            $item = preg_replace( '/{!([^\s&][^{}]*)}(\n?)/', '<?php echo \1; ?>\2\2', $item);
            // For lang strings in Javascript - {#somefunc() or #obj->somefunc()}
            $item = preg_replace( '/{#([^\s&][^{}]*)}(\n?)/',
                    '<?php echo Filters::noJsXSS(\1); ?>\2\2', $item);
            // parse all remaining strings that look like function calls wrapped in { }
            $item = preg_replace( '/{([^\s&][^{}]*)}(\n?)/',
                    '<?php echo Filters::noXSS(\1); ?>\2\2', $item);
        }
    }
    // {{{ Display page
    function pushTpl($_tpl)
    {
        $this->_tpls[] = $_tpl;
    }
    
    function exists($page)
    {
        return is_readable(BASEDIR . '/themes/' . $this->_theme.'/templates/'.$page) || is_readable(BASEDIR . '/templates/'.$page);
    }

    function display($_tpl, $_arg0 = null, $_arg1 = null)
    {
        // if only plain text
        if (is_array($_tpl) && count($tpl)) {
            echo $_tpl[0];
            return;
        }

        // if there's a themed template by this name, then use it, otherwise use the stock.
        if (is_readable(BASEDIR . '/themes/' . $this->_theme.'/templates/'.$_tpl)) {
            $_tpl_data = file_get_contents(BASEDIR . '/themes/' . $this->_theme.'/templates/'.$_tpl);
        } else {
            $_tpl_data = file_get_contents(BASEDIR . '/templates/'.$_tpl);
        }

        // compilation part
        // pass all things that look like php code to the compile() func, join the results back together
        $_tpl_data = preg_split('!(<\?php.*\?>)!sU', $_tpl_data, -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        array_walk($_tpl_data, array(&$this, 'compile'));
        $_tpl_data = join('', $_tpl_data);

        // replace any occurences of &lbrace; and $rbrace; with their actual characters { and }
        $from = array('&lbrace;','&rbrace;');
        $to = array('{','}');
        $_tpl_data = str_replace($from, $to, $_tpl_data);

        // variables part
        if (!is_null($_arg0)) {
            $this->assign($_arg0, $_arg1);
        }

        foreach ($this->_uses as $_var) {
            global $$_var;
        }

        extract($this->_vars, EXTR_REFS|EXTR_SKIP);

        // XXX: if you find a clever way to remove the evil here,
        // send us a patch, thanks.. we don't want this..really ;)

        eval( '?>'. $_tpl_data );
    } // }}}

    function render()
    {
        while (count($this->_tpls)) {
            $this->display(array_shift($this->_tpls));
        }

    }

    function fetch($tpl, $arg0 = null, $arg1 = null)
    {
        ob_start();
        $this->display($tpl, $arg0, $arg1);
        return ob_get_clean();
    }
}

class FSTpl extends Tpl
{
    var $_uses = array('fs', 'conf', 'baseurl', 'proj', 'user', 'do');
    var $text = null;

    function FSTpl()
    {
        $this->text = new TextFormatter();
    }

    function get_image($name, $base = true)
	{
        global $proj, $baseurl;
        $pathinfo = pathinfo($name);
        $link = 'themes/' . $proj->prefs['theme_style'] . '/';
        if ($pathinfo['dirname'] != '.') {
            $link .= $pathinfo['dirname'] . '/';
            $name = $pathinfo['basename'];
        }

        $extensions = array('.png', '.gif', '.jpg', '.ico');

        foreach ($extensions as $ext) {
            if (is_file(BASEDIR . '/' . $link . $name . $ext)) {
                return ($base) ? (FSTpl::relativeUrl($baseurl) . $link . $name . $ext) : ($link . $name . $ext);
            }
        }
        return '';
    }
    
    function relativeUrl($url)
    {
        $url = parse_url($url);
        return $url['path'] . (isset($url['query']) ? '?' . $url['query'] : '') . (isset($url['fragment']) ? '#' . $url['fragment'] : '');
    }
    
    /*
     * Use a relative URL within templates
     */
    function url()
    {
        $args = func_get_args();
        return FSTpl::relativeUrl(call_user_func_array('CreateUrl', $args));
    }

    function finish($tpl = '')
    {
        if ($tpl) {
           $this->pushTpl($tpl);
        }

        $this->render();

        unset($_SESSION['ERROR'], $_SESSION['SUCCESS']);
        exit; // finish is finish, we should really be done here
    }
}

// {{{ costful templating functions, TODO: optimize them

function tpl_tasklink($task, $text = null, $strict = false, $attrs = array(), $title = array('state','summary','percent_complete'))
{
    global $user;

    $params = array();

    if (!is_array($task)) {
        $task = Flyspray::GetTaskDetails(Flyspray::GetTaskId($task), true);
    }

    if ($strict === true && (!is_object($user) || !$user->can_view_task($task))) {
        return '';
    }

    if (is_object($user) && $user->can_view_task($task)) {
        $summary = utf8_substr($task['item_summary'], 0, 64);
    } else {
        $summary = L('taskmadeprivate');
    }

    if (is_null($text)) {
        $text = sprintf('%s#%d - %s', $task['project_prefix'], $task['prefix_id'], Filters::noXSS($summary));
    } elseif(is_string($text)) {
        $text = Filters::noXSS(utf8_substr($text, 0, 64));
    } else {
        //we can't handle non-string stuff here.
        return '';
    }

    if (!$task['task_id']) {
        return $text;
    }

    $title_text = array();

    foreach($title as $info)
    {
        switch($info)
        {
            case 'state':
                if ($task['is_closed']) {
                    $title_text[] = L('closed');
                    $attrs['class'] = 'closedtasklink';
                } elseif ($task['closed_by']) {
                    $title_text[] = L('reopened');
                } else {
                    $title_text[] = L('open');
                }
                break;

            case 'summary':
                $title_text[] = $summary;
                break;

            case 'assignedto':
                if (isset($task['assigned_to_name']) ) {
                    if (is_array($task['assigned_to_name'])) {
                        $title_text[] = implode(', ', $task['assigned_to_name']);
                    } else {
                        $title_text[] = $task['assigned_to_name'];
                    }
                }
                break;

            case 'percent_complete':
                    $title_text[] = $task['percent_complete'].'%';
                break;

            case 'age':
                $title_text[] = formatDate($task['date_opened']);
                break;

            // ... more options if necessary
        }
    }

    $title_text = implode(' | ', $title_text);

    $params = $_GET;
    unset($params['do'], $params['action'], $params['task_id'], $params['switch']);

    $url = Filters::noXSS(FSTpl::relativeUrl(CreateURL(array('details', 'task' . $task['task_id']), $params)));
    $title_text = Filters::noXSS($title_text);
    $link  = sprintf('<a href="%s" title="%s" %s>%s</a>',$url, $title_text, join_attrs($attrs), $text);

    if ($task['is_closed']) {
        $link = '<del>&#160;' . $link . '&#160;</del>';
    }
    return $link;
}

function tpl_userlink($uid)
{
    global $db, $user;

    static $cache = array();

    if (is_array($uid)) {
        $uname = $uid['user_name'];
        $rname = $uid['real_name'];
        $uid = $uid['user_id'];
    } elseif (is_string($uid) && !is_numeric($uid)) {
        // map username to ID
        $userinfo = $db->x->getRow('SELECT user_id, user_name, real_name
                                        FROM {users}
                                       WHERE user_name = ?', null, $uid);
        $uname = $userinfo['user_name'];
        $rname = $userinfo['real_name'];
        $uid = $userinfo['user_id'];
    } elseif (empty($cache[$uid])) {
        $sql = $db->x->getRow('SELECT user_name, real_name FROM {users} WHERE user_id = ?',
                                      null, intval($uid));
        if ($sql) {
            $uname = $sql['user_name'];
            $rname = $sql['real_name'];
        }
    }

    if (isset($uname)) {
        $args = array_map(array('Filters', 'noXSS'), array(FSTpl::relativeUrl(CreateURL(array('user'), array('uid' => $uid))), $rname, $uname));
        $cache[$uid] = vsprintf('<a href="%s">%s (%s)</a>', $args);
    } elseif (empty($cache[$uid])) {
        $cache[$uid] = (!is_numeric($uid)) ? eL('anonymous') : $uid;
    }

    return $cache[$uid];
}

function tpl_fast_tasklink($arr)
{
    return tpl_tasklink($arr[1] . $arr[2], $arr[0]);
}

// }}}
// {{{ some useful plugins

function join_attrs($attr = null) {
    if (is_array($attr) && count($attr)) {
        $arr = array();
        foreach ($attr as $key=>$val) {
            $arr[] = vsprintf('%s="%s"', array_map(array('Filters','noXSS'), array($key, $val)));
        }
        return ' '.join(' ', $arr);
    }
    return '';
}
// {{{ Datepicker
function tpl_datepicker($name, $label = '', $value = 0, $attrs = array()) {
    //global $user;

    $date = '';

    if ($value) {
        if (!is_numeric($value)) {
            $value = strtotime($value);
        }
        $date = date('Y-m-d', intval($value));

     /* It must "look" as a date..
      * XXX : do not blindly copy this code to validate other dates
      * this is mostly a tongue-in-cheek validation
      * 1. it will fail on 32 bit systems on dates < 1970
      * 2. it will produce different results bewteen 32 and 64 bit systems for years < 1970
      * 3. it will not work when year > 2038 on 32 bit systems (see http://en.wikipedia.org/wiki/Year_2038_problem)
      *
      * Fortunately tasks are never opened to be dated on 1970 and maybe our sons or the future flyspray
      * coders may be willing to fix the 2038 issue ( in the strange case 32 bit systems are still used by that year) :-)
      */

    } elseif (Req::has($name) && strlen(Req::val($name))) {

        //strtotime sadly returns -1 on faliure in php < 5.1 instead of false
        $ts = strtotime(Req::val($name));

        foreach (array('m','d','Y') as $period) {
            //checkdate only accepts arguments of type integer
            $$period = intval(date($period, $ts));
        }
        // $ts has to be > 0 to get around php behavior change
        // false is casted to 0 by the ZE
        $date = ($ts > 0 && checkdate($m, $d, $Y)) ? Req::val($name) : '';
    }


    $page = new FSTpl;
    $page->assign('name', $name);
    $page->assign('date', $date);
    $page->assign('label', $label);
    $page->assign('attrs', $attrs);
    $page->assign('dateformat', '%Y-%m-%d');
    $page->display('common.datepicker.tpl');
}
// }}}
// {{{ user selector
function tpl_userselect($input_name, $value = null, $input_id = '', $attrs = array()) {
    global $db, $user;

    if (!$input_id) {
        $input_id = $input_name;
    }

    if ($value && is_numeric($value)) {
        $value = $db->x->GetOne('SELECT user_name FROM {users} WHERE user_id = ?', null, $value);
    }

    if (!$value) {
        $value = '';
    }

    $page = new FSTpl;
    $page->assign('name', $input_name);
    $page->assign('id', $input_id);
    $page->assign('value', $value);
    $page->assign('attrs', $attrs);
    $page->display('common.userselect.tpl');
}
// }}}

// {{{ Options for a <select>
function tpl_options($options, $selected = null, $labelIsValue = false, $attr = null, $remove = null, $classcol = null)
{
    $html = '';

    // force $selected to be an array.
    // this allows multi-selects to have multiple selected options.

    // operate by value ..
    $selected = is_array($selected) ? $selected : (array) $selected;
    $options = is_array($options) ? $options : (array) $options;
    
    //$debug = print_r($options, True);
    //$debug2 = print_r($selected, True);
    
    //$debug4 = "";

    foreach ($options as $value=>$label)
    {
        //$debug4 .= print_r($value, True) . " " . print_r($label, True) . ";";
        if (!is_null($classcol) && isset($label[$classcol])) {
            $attr['class'] = $classcol . $label[$classcol];
        }
        if (is_object($label)) {
            $label = $label->prefs;
        }
        if (is_array($label)) {
            $value = reset($label);
            $label = next($label);
        }
        $label = Filters::noXSS($label);
        $value = $labelIsValue ? $label : Filters::noXSS($value);

        if ($value === $remove) {
            continue;
        }

        $html .= '<option value="'.$value.'"';
        if (in_array($value, $selected)) {
            $html .= ' selected="selected"';
        }

        $html .= ($attr ? join_attrs($attr): '') . '>' . $label . '</option>';
    }
    
    //$debug3="";
    //foreach ($selected as $value=>$label)
    //{
        //$debug3 .= $value." ".$label.";";
    //}
    if (!$html) {
        $html .= '<option value="0">---</option>';
        //$html .= "<option value='1'>options=$debug\r\n<br></option>";
        //$html .= "<option value='2'>selected=$debug2\r\n<br></option>";
        //$html .= "<option value='3'>$debug3\r\n<br></option>";
        //$html .= "<option value='4'>$debug4\r\n<br></option>";
    }

    return $html;
} // }}}
// {{{ Double <select>
function tpl_double_select($name, $options, $selected = null, $labelIsValue = false, $updown = true)
{
    static $_id = 0;
    static $tpl = null;

    if (!$tpl) {
        // poor man's cache
        $tpl = new FSTpl();
    }

    settype($selected, 'array');
    settype($options, 'array');

    $tpl->assign('id', '_task_id_'.($_id++));
    $tpl->assign('name', $name);
    $tpl->assign('selected', $selected);
    $tpl->assign('updown', $updown);

    $html = $tpl->fetch('common.dualselect.tpl');

    $selectedones = array();

    $opt1 = '';
    foreach ($options as $value => $label) {
        if (is_array($label) && count($label) >= 2) {
            $value = reset($label);
            $label = next($label);
        }
        if ($labelIsValue) {
            $value = $label;
        }
        if (in_array($value, $selected)) {
            $selectedones[$value] = $label;
            continue;
        }
        $label = Filters::noXSS($label);
        $value = Filters::noXSS($value);

        $opt1 .= sprintf('<option title="%2$s" value="%1$s">%2$s</option>', $value, $label);
    }

    $opt2 = '';
    foreach ($selected as $value) {
        if (!isset($selectedones[$value])) {
            continue;
        }
        $label = Filters::noXSS($selectedones[$value]);
        $value = Filters::noXSS($value);

        $opt2 .= sprintf('<option title="%2$s" value="%1$s">%2$s</option>', $value, $label);
    }

    return sprintf($html, $opt1, $opt2);
} // }}}
// {{{ Checkboxes
function tpl_checkbox($name, $checked = false, $id = null, $value = 1, $attr = null, $type = 'checkbox')
{
    $name  = Filters::noXSS($name);
    $value = Filters::noXSS($value);
    $html  = '<input type="' . $type . '" name="'.$name.'" value="'.$value.'" ';
    if (is_string($id)) {
        $html .= 'id="'.Filters::noXSS($id).'" ';
    }
    if ($checked == true) {
        $html .= 'checked="checked" ';
    }
    // do not call join_attrs if $attr is null or nothing..
    return ($attr ? $html. join_attrs($attr) : $html) . '/>';
} // }}}
// {{{ Image display
function tpl_img($src, $alt)
{
    global $baseurl;
    if ($src && is_file(BASEDIR .'/'.$src)) {
        $args = array_map(array('Filters','noXSS'), array($baseurl . $src, $alt));
        return vsprintf('<img src="%s" alt="%s"/>', $args);
    }
    return Filters::noXSS($alt);
} // }}}
// Format Date {{{
function formatDate($timestamp, $extended = false, $default = '')
{
    global $db, $conf, $user, $fs;

    if (!$timestamp) {
        return $default;
    }

    $dateformat = '';
    $format_id  = $extended ? 'dateformat_extended' : 'dateformat';
    $st = date('Z'); // server timezone offset

    if (!$user->isAnon()) {
        $dateformat = $user->infos[$format_id];
        $timestamp += ($user->infos['time_zone'] - $st);
        $st = $user->infos['time_zone'];
    }

    if (!$dateformat) {
        $dateformat = $fs->prefs[$format_id];
    }

    if (!$dateformat) {
        $dateformat = $extended ? '%A, %d %B %Y, %H:%M %GMT' : '%Y-%m-%d';
    }
    
    $h = floor($st / 3600);
    $m = str_pad(round(($st - $h * 3600) / 60, 0), 2, 0);
    $zone = L('UTC') . (($st == 0) ? ' ' : (($st > 0) ? '+' . $h.':'.$m : $h.':'.$m));
    $dateformat = str_replace('%GMT', $zone, $dateformat);
    // it's returned utf-8 encoded by the system
    return strftime(Filters::noXSS($dateformat), (int) $timestamp);
} /// }}}
// {{{ Draw permissions table
function tpl_draw_perms($perms)
{
    global $proj, $fs;

    $yesno = array(
            '<td class="bad">' . eL('no') . '</td>',
            '<td class="good">' . eL('yes') . '</td>');

    // FIXME: html belongs in a template, not in the template class
    $html = '<table border="1" onmouseover="perms.hide()" onmouseout="perms.hide()">';
    $html .= '<thead><tr><th colspan="2">';
    $html .= Filters::noXSS(L('permissionsforproject').$proj->prefs['project_title']);
    $html .= '</th></tr></thead><tbody>';

    foreach ($perms[$proj->id] as $key => $val) {
        if (in_array($key, array_merge(array('is_admin', 'group_open'), $fs->perms), true)) {
            $html .= '<tr><th>' . eL(str_replace('_', '', $key)) . '</th>';
            $html .= $yesno[ ($val || $perms[0]['is_admin']) ].'</tr>';
        }
    }
    return $html . '</tbody></table>';
} // }}}

/**
 * Highlights searchqueries in HTML code
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Harry Fuecks <hfuecks@gmail.com>
 */
function html_hilight($html,$query){
  //split at common delimiters
  $queries = preg_split ('/[\s\'"\\\\`()\]\[?:!\.{};,#+*<>]+/',$query,-1,PREG_SPLIT_NO_EMPTY);
  foreach ($queries as $q){
     $q = preg_quote($q,'/');
     $html = preg_replace_callback("/((<[^>]*)|$q)/i",'html_hilight_callback',$html);
  }
  return $html;
}

/**
 * Callback used by html_hilight()
 *
 * @author Harry Fuecks <hfuecks@gmail.com>
 */
function html_hilight_callback($m) {
  $hlight = unslash($m[0]);
  if ( !isset($m[2])) {
    $hlight = '<span class="search_hit">'.$hlight.'</span>';
  }
  return $hlight;
}

function tpl_disableif ($if)
{
    if ($if) {
        return 'disabled="disabled"';
    }
}
// {{{ Url handling
// Create an URL based upon address-rewriting preferences {{{
function CreateURL($type, $args = array())
{
    global $baseurl, $conf;

    $return = $baseurl;
    if (!is_array($type)) {
        $type = array($type);
    }

    // If we do want address rewriting
    if ($conf['general']['address_rewriting'] == '1') {
        $return = $baseurl . implode('/', $type);
    } else {
        // otherwise generate a usual URL
        $args += array('do' => array_shift($type));
        foreach ($type as $arg) {
            if (preg_match('/proj([0-9]+)/', $arg, $matches)) {
                $args += array('project' => $matches[1]);

            } else if (preg_match('/task([0-9]+)/', $arg, $matches)) {
                $args += array('task_id' => $matches[1]);
            } else {
                $args += array('area' => $arg);
            }
        }
    }

    $query_string = http_build_query($args);
    $separator = ini_get('arg_separator.output');
    if (strlen($separator) != 0) {
        $query_string = str_replace($separator, '&', $query_string);
    }
    return (count($args) ? $return . (($conf['general']['address_rewriting']) ? '?' : 'index.php?') . $query_string : $return);
} // }}}
// Page numbering {{{
// Thanks to Nathan Fritz for this.  http://www.netflint.net/
function pagenums($pagenum, $perpage, $totalcount)
{
    global $proj;
    $pagenum = intval($pagenum);
    $perpage = intval($perpage);
    $totalcount = intval($totalcount);

    // Just in case $perpage is something weird, like 0, fix it here:
    if ($perpage < 1) {
        $perpage = $totalcount > 0 ? $totalcount : 1;
    }
    $pages  = ceil($totalcount / $perpage);
    $pagenum = min($pagenum, $pages);
    $output = sprintf(eL('page'), $pagenum, $pages);

    if (!($totalcount / $perpage <= 1)) {
        $output .= '<span class="DoNotPrint"> &nbsp;&nbsp;--&nbsp;&nbsp; ';

        $start  = max(1, $pagenum - 4 + min(2, $pages - $pagenum));
        $finish = min($start + 4, $pages);

        if ($start > 1)
            $output .= '<a href="' . Filters::noXSS(CreateURL(array('index', 'proj' . $proj->id), array_merge($_GET, array('pagenum' => 1)))) . '">&lt;&lt;' . eL('first') . ' </a>';

        if ($pagenum > 1)
            $output .= '<a id="previous" accesskey="p" href="' . Filters::noXSS(CreateURL(array('index', 'proj' . $proj->id), array_merge($_GET, array('pagenum' => $pagenum - 1)))) . '">&lt; ' . eL('previous') . '</a> - ';

        for ($pagelink = $start; $pagelink <= $finish;  $pagelink++) {
            if ($pagelink != $start)
                $output .= ' - ';

            if ($pagelink == $pagenum) {
                $output .= '<strong>' . $pagelink . '</strong>';
            } else {
                $output .= '<a href="' . Filters::noXSS(CreateURL(array('index', 'proj' . $proj->id), array_merge($_GET, array('pagenum' => $pagelink)))) . '">' . $pagelink . '</a>';
            }
        }

        if ($pagenum < $pages)
            $output .= ' - <a id="next" accesskey="n" href="' . Filters::noXSS(CreateURL(array('index', 'proj' . $proj->id), array_merge($_GET, array('pagenum' => $pagenum + 1)))) . '">' . eL('next') . ' &gt;</a>';
        if ($finish < $pages)
            $output .= '<a href="' . Filters::noXSS(CreateURL(array('index', 'proj' . $proj->id), array_merge($_GET, array('pagenum' => $pages)))) . '"> ' . eL('last') . ' &gt;&gt;</a>';
        $output .= '</span>';
    }

    return $output;
} // }}}

// tpl function that Displays a header cell for report list {{{

function tpl_list_heading($colname, $coldisplay, $format = "<th%s>%s</th>")
{
    global $proj, $page;
    $imgbase = '<img src="%s" alt="%s" />';
    $class   = '';
    $html    = $coldisplay;
    $colname = strtolower($colname);
    if ($colname == 'comments' || $colname == 'attachments') {
        $html = sprintf($imgbase, $page->get_image(substr($colname, 0, -1)), $html);
    }

    if (Get::val('order') == $colname) {
        $class  = ' class="orderby"';
        $sort1  = Get::safe('sort', 'desc') == 'desc' ? 'asc' : 'desc';
        $sort2  = Get::safe('sort2', 'desc');
        $order2 = Get::safe('order2');
        $html  .= '&nbsp;&nbsp;'.sprintf($imgbase, $page->get_image(Get::val('sort')), Get::safe('sort'));
    }
    else {
        $sort1  = 'desc';
        $sort2  = Get::safe('sort', 'desc');
        $order2 = Get::safe('order');
    }


    $new_order = array('order' => $colname, 'sort' => $sort1, 'order2' => $order2, 'sort2' => $sort2);
    $html = sprintf('<a title="%s" href="%s">%s</a>',
            L('sortthiscolumn'), Filters::noXSS($page->url(array('index', 'proj' . $proj->id), array_merge($_GET, $new_order))), $html);

    return sprintf($format, $class, $html);
}

function tpl_TimeZones()
{
    $times = array();
    for ($i = -12; $i <= 14; $i++) {
        $times[$i * 3600] = L('UTC') . (($i == 0) ? ' ' : (($i > 0) ? '+' . $i : $i));
    }
    // Specials
    // Half hours
    foreach(array(-9, -4, -3, 3, 4, 5, 6, 9, 10, 11) as $hour) {
        $times[$hour * 3600 + (($hour < 0) ? -30 : 30)] = L('UTC') . (($hour >= 0) ? '+' : '') . $hour . ':30';
    }
    // UTC+5:45
    $times[5 * 3600 + 45] = L('UTC') . '+5:45';
    // UTC+8:45
    $times[8 * 3600 + 45] = L('UTC') . '+8:45';
        // UTC+8:45
    $times[12 * 3600 + 45] = L('UTC') . '+12:45';
    
    ksort($times);
    return $times;
}

// }}}
// }}}
?>
