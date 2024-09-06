<?php

/**
 * DokuWiki Plugin Numbered Headings: add tiered numbers for hierarchical headings
 *
 * Add numbering to all headings.
 *
 * Config settings
 *     tier1  : heading level corresponding to the 1st tier
 *     format : numbering format (used in vsprintf) of each tier, JSON array string
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 * @author     Jihyeon Yoon <somehowme@gmail.com>
 */
class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin
{
    /** syntax type */
    public function getType()
    {
        return 'substition';
    }

    /** paragraph type */
    public function getPType()
    {
        return 'block';
    }

    /**
     * Connect pattern to lexer
     */
    protected $mode, $pattern;

    public function preConnect()
    {
        // syntax mode, drop 'syntax_' from class name
        $this->mode = substr(get_class($this), 7);

        // syntax pattern
        $this->pattern[0] = '~~HEADLINE NUMBERING FIRST LEVEL = \d~~';
        // $this->pattern[5] = '^[ \t]*={2,} ?-+(?:[#"][^\n]*)? [^\n]*={2,}[ \t]*(?=\n)';
        $this->pattern[5] = '^[ \t]*={2,} ?(?:[#"][^\n]*)? [^\n]*={2,}[ \t]*(?=\n)';
    }

    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern($this->pattern[0], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);

        // backward compatibility, to be obsoleted in future ...
        $this->Lexer->addSpecialPattern(
                        '{{header>[1-5]}}', $mode, $this->mode);
        $this->Lexer->addSpecialPattern(
                        '{{startlevel>[1-5]}}', $mode, $this->mode);
    }

    public function getSort()
    {
        return 45;
    }

    /**
     * Handle the match
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        // obtain the first tier (Tier1) level from the page if defined
        $match = trim($match);
        if ($match[0] !== '=') {
            // Note: The Tier1 Level may become 0 (auto-detect) in the page
            $level = (int) substr($match, -3, 1);
            return $data = compact('level');
        }

        // obtain the level of the heading
        $level = 7 - min(strspn($match, '='), 6);

        // separate parameter and title
        // == -#n title  == ; "#" is a parameter indicates number
        // == - #n title == ; "#" is a placeholder of numbering label

        $text = trim(trim($match), '='); // drop heading markup
        $text = ltrim($text);
        $dash = 1; // strspn($text, '-');      // count dash marker to check '-' or '--'
        // $text = substr($text, $dash);
        $text = ' ' . $text;
        $title = $text;
        switch ($text[0]) {
            case ' ':
                list($number, $title) = array('', trim($text));
                if (substr($title, 0, 1) == '#') {
                    // extra check of title
                    // == - # title ==     ; "#" is NOT numbering label
                    // == - #12 title ==   ; "#" is numbering label with number
                    // == - #12.3 title == ; "#" is NOT numbering label
                    $part = explode(' ', substr($title, 1), 2);
                    if (ctype_digit($part[0])) {
                        $number = $part[0] +0;
                        $title  = trim($part[1]);
                    }
                }
                break;
            case '#': // numeric numbering, (integer) $number
                list($number, $title) = explode(' ', substr($text, 1), 2);
                $number = ctype_digit($number) ? $number +0 : '';
                $title  = trim($title);
                break;
            case '"': // alpha-numeric numbering, (string) $number
                $closed = strpos($text, '"', 1); // search closing "
                if ($closed !== false) {
                    $number = substr($text, 1, $closed -1);
                    $title  = trim(substr($text, $closed + 1));
                } else {
                    list($number, $title) = explode(' ', substr($text, 1), 2);
                    $title  = trim($title);
                }
                break;
        }

        // non-visible numbered headings, marked with '--'
        if ($dash > 1 && substr($title, 0, 1) == '[' && substr($title, -1) == ']') {
            $format = $title;
            $title = null;
        } else {
            $format = null;
        }

        $data = compact('dash', 'level', 'number', 'title', 'format');

        if ($dash == 1) {
            // do same as parser::handler->header()
            if ($this->getSectionState($handler)) $this->addCall($handler, 'section_close', [], $pos);

            // plugin instruction to be rewrited later
            $handler->addPluginCall(substr(get_class($this), 14), $data, $state, $pos, $match);
            $this->addCall($handler, 'section_open', [$level], $pos);
            $this->setSectionState($handler, true);
        } else {
            return $data;
        }
        return false;
    }

    /**
     * Create output
     */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        // nothing to do, should never be called because plugin instructions
        // are converted to normal headers in PARSER_HANDLER_DONE event handler
    }

    /* -------------------------------------------------------------- *
     * Compatibility methods for DokuWiki Hogfather
     * -------------------------------------------------------------- */
     // add a new call using CallWriter of the handler object
     private function addCall(Doku_Handler $handler, $method, $args, $pos)
     {
         if (method_exists($handler, 'addCall')) {
             // applicable since DokuWiki RC3 2020-06-10 Hogfather
             $handler->addCall($method, $args, $pos);
         } else {
             // until DokuWiki 2018-04-22 Greebo
             $handler->_addCall($method, $args, $pos);
         }
     }

     // get section status of the handler object
     private function getSectionstate(Doku_Handler $handler)
     {
         if (method_exists($handler, 'getStatus')) {
             return $handler->getStatus('section');
         } else {
             return $handler->status['section'];
         }
     }

     // set section status of the handler object
     private function setSectionstate(Doku_Handler $handler, $value)
     {
         if (method_exists($handler, 'setStatus')) {
             $handler->setStatus('section', $value);
         } else {
             $handler->status['section'] = $value;
         }
     }

}
