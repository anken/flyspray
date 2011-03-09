<?php

class WikiSyntax extends SyntaxPlugin
{
    function getOrder() {
        return 0;
    }

    function code_callback($matches)
    {
        $index = (int)$matches[1];
        $lang = $this->saved_code[$index]['lang'];
        $code = $this->saved_code[$index]['code'];

        // for prevent memory leaks
        unset($this->saved_code[$index]);

        // translate some language names so that simpler names can be used
        $$lang = strtr($lang, array('html' => 'html4strict', 'php' => 'php-brief'));
        // if we get HTML for example, it's already encoded
        $geshi = new GeSHi(htmlspecialchars_decode(trim($code, " \t\n\r\0\x0b\xa0"), ENT_QUOTES), $lang);
        $geshi->enable_classes();
        $geshi->set_overall_class('code');
        return $geshi->parse_code();
    }
    
    private $saved_code = array();
    private function code_cut_tag($matches) {
        static $index = 0;
        $index++;
        $this->saved_code[$index] = array(
                'lang' => $matches[1],
                'code' => $matches[2]
    		);
    	return ":code__{$index}_:";
    }

    function beforeCache(&$input) {
        global $conf;
        // code highlighting
        require_once 'geshi/geshi.php';
        require_once 'Text/Wiki.php';

        // transform it a little to save it from evil wiki parser ^^
        $input = preg_replace_callback('#<code (\w+)>(.*?)</code>#Uuism', array($this, 'code_cut_tag'), $input) . ' ';

        // create a Wiki object with the loaded options
        $wiki = & Text_Wiki::singleton('Doku' /*$type = 'Doku'*/);
        $wiki->setRenderConf('xhtml', 'wikilink', 'new_text', '');

        if (isset($conf['general']['doku_url']) && $conf['general']['doku_url']) {
            $wiki->setRenderConf('xhtml', 'wikilink', 'new_url', $conf['general']['doku_url']);
        }
        $wiki->setFormatConf('Xhtml', 'translate', HTML_SPECIALCHARS);
        $wiki->setFormatConf('Xhtml', 'charset', 'utf-8');

        if (@$type == 'Doku') {
            $input = str_replace('\\\\', '', $input);
        }

        $input = @$wiki->transform($input, 'Xhtml');

        // restoring code tags with highlighting
        $input = preg_replace_callback('#:code__(\d+)_:#Uuism', array($this, 'code_callback'), $input);
    }

    function getHtmlAfter() {
        return '<button type="button" onclick="showPreview(\'%id\', \'%id_preview\')">' . L('preview') . '</button>';
    }

    /**
	 * Displays a toolbar for formatting text in the DokuWiki Syntax
	 * Uses Javascript. Beware: Ugly code right ahead.
	 */
	function getHtmlBefore() {
		global $baseurl, $proj;

		return '<div class="hide preview" id="%id_preview"></div><div>
        <a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'**\', \'**\', \'%id\'); return false;">
		  		<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-bold.png" style="vertical-align:bottom;border:none" alt="Bold" title="Bold" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'//\', \'//\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-italic.png" style="vertical-align:bottom;border:none" alt="Italicized" title="Italicized" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'__\', \'__\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-underline.png" style="vertical-align:bottom;border:none" alt="Underline" title="Underline" /></a>

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'&lt;del&gt;\', \'&lt;/del&gt;\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-strikethrough.png" style="vertical-align:bottom;border:none" alt="Strikethrough" title="Strikethrough" /></a>

			<img src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" alt="|" style="vertical-align:bottom;border:none;margin: 0 3px 0 3px;" />

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'======\', \'======\', \'%id\'); return false;">
			<img title="Level 1 Headline" src="'.$baseurl.'includes/syntaxplugins/img/h1.gif" style="vertical-align:bottom;border:none" width="23" height="22" alt="Heading1" /></a>

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'=====\', \'=====\', \'%id\'); return false;">
			<img title="Level 2 Headline" src="'.$baseurl.'includes/syntaxplugins/img/h2.gif" style="vertical-align:bottom;border:none" width="23" height="22" alt="Heading2" /></a>

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'====\', \'====\', \'%id\'); return false;">
			<img title="Level 3 Headline" src="'.$baseurl.'includes/syntaxplugins/img/h3.gif" style="vertical-align:bottom;border:none" width="23" height="22" alt="Heading3" /></a>

			<img title="Divider" src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'&#123;&#123;http://\', \'&#125;&#125;\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/image-x-generic.png" style="vertical-align:bottom;border:none" alt="Insert Image" title="Insert Image" /></a>

			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\'\n  * \', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/ul.gif" style="vertical-align:bottom;border:none" width="23" height="22" alt="Insert List" title="Insert List" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\'\n  - \', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/ol.gif" style="vertical-align:bottom;border:none" width="23" height="22" alt="Insert List" title="Insert List" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\'----\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/hr.gif" style="vertical-align:bottom;border:none" width="23" height="22" alt="Horizontal Rule" title="Horizontal Rule" /></a>

			<img src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'[[http://example.com|External Link\', \']]\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/text-html.png" style="vertical-align:bottom;border:none" alt="Insert Hyperlink" title="Insert Hyperlink" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'[[\', \']]\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/email.png" style="vertical-align:bottom;border:none" alt="Insert Email" title="Insert Email" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'[[ftp://\', \']]\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/network.png" style="vertical-align:bottom;border:none" alt="Insert FTP Link" title="Insert FTP Link" /></a>

			<img src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'<code>\', \'</code>\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/source.png" style="vertical-align:bottom;border:none" alt="Insert Code" title="Insert Code" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'<code php>\', \'</code>\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/source_php.png" style="vertical-align:bottom;border:none" alt="Insert Code" title="Insert PHP Code" /></a>
            <a tabindex="-1" href="http://wiki.splitbrain.org/wiki:syntax">'. eL('syntax') .'</a>
		';
	}
}

?>