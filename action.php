<?php
/**
 * DokuWiki Plugin Numbered Headings; action component
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class action_plugin_numberedheadings extends DokuWiki_Action_Plugin
{
    /**
     * Registers a callback function for a given event
     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook(
            'PARSER_HANDLER_DONE', 'BEFORE', $this, '_numbering', []
        );

        if ($this->getConf('fancy')) {
            $controller->register_hook(
                'RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, '_tieredNumber'
            );
        }
    }

    /**
     * PARSER_HANDLER_DONE event handler
     * convert plugin instruction to header
     */
    function _numbering(Doku_Event $event)
    {
        // load helper object
        static $numbering;
        isset($numbering) || $numbering = $this->loadHelper($this->getPluginName());

        $instructions =& $event->data->calls;

        foreach ($instructions as $k => &$ins) {
            if ($ins[0] == 'plugin' && $ins[1][0] == 'numberedheadings') {
                [$level, $number, $title] = $ins[1][1];

                // obtain the first level (Tier1) from the page if defined
                if ($title === null) {
                    $numbering->setTier1($level);
                    continue;
                }

                if (!$numbering->getTier1()) {
                    $numbering->setTier1($this->getConf('startlevel'));
                }

                // set the internal heading counter
                $numbering->setHeadingCounter($level, $number);
                
                // build tiered numbers for hierarchical headings
                $tieredNumbers = $numbering->getTieredNumbers($level);
                if ($tieredNumbers) {
                    // append figure space after tiered number to distinguish title
                    $tieredNumbers .= ' '; // U+2007 figure space
                }
                $text = $tieredNumbers.$title;

                // rewrite plugin call to header
                $ins[0] = 'header';
                $ins[1] = [$text, $level, $ins[2]];
            }
        }
        unset($ins);
        $numbering->setTier1();
        $numbering->setTierFormat();
        $numbering->setHeadingCounter();
    }

    /**
     * RENDERER_CONTENT_POSTPROCESS
     * enclose tiered numbers for hierarchical headings in span tag
     */
    function _tieredNumber(Doku_Event $event)
    {
        if ($event->data[0] == 'xhtml') {
            $search = '/(<h\d.*?>)([\d.]+)(?: )/u'; // U+2007 figure space
            $replacement = '${1}<span class="plugin_numberedheadings">${2}</span>'."\t";
            $event->data[1] = preg_replace($search, $replacement, $event->data[1]);
        }
    }

}
