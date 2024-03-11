<?php

use dokuwiki\Extension\Plugin;

/**
 * DokuWiki Plugin searchns (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Anna Dabrowska <dokuwiki@cosmocode.de>
 */
class helper_plugin_searchns extends Plugin
{
    /**
     * Converts config string to array
     *
     * @return array
     */
    public function getNsFromConfig()
    {
        $ns = [];
        $config = $this->getConf('namespaces');

        if (empty($config)) return $ns;

        $lines = array_filter(explode("\n", $config));
        foreach ($lines as $line) {
            $n = sexplode(' ', $line, 2);
            if (strpos($n[0], ':', -1) === false) {
                msg('Search namespaces are not configured correctly!', -1);
                return $ns;
            }
            $ns[trim($n[1])] = rtrim(trim($n[0], ':'));
        }
        $ns['all'] = '';

        return $ns;
    }

    /**
     * Returns HTML list of search results.
     * Based on core quicksearch
     * @see \dokuwiki\Ajax::callQsearch()
     *
     * @return string
     */
    public function qSearch()
    {
        global $INPUT;

        $maxnumbersuggestions = 50;

        // search parameters as posted via AJAX
        $query = $INPUT->str('q');
        $ns = $INPUT->str('ns');
        if (empty($query)) return '';

        $query = urldecode($query) . ($ns ? " @$ns" : '');
        $data = ft_pageLookup($query, true, useHeading('navigation'));

        if ($data === []) return '';

        $ret = '<ul>';

        $counter = 0;
        foreach ($data as $id => $title) {
            if (useHeading('navigation')) {
                $name = $title;
            } elseif (!$ns) {
                $linkNs = getNS($id);
                if ($linkNs) {
                    $name = noNS($id) . ' (' . $linkNs . ')';
                } else {
                    $name = $id;
                }
            } else {
                $name = $id;
            }
            $ret .= '<li>' . html_wikilink(':' . $id, $name) . '</li>';

            $counter++;
            if ($counter > $maxnumbersuggestions) {
                $ret .=  '<li>...</li>';
                break;
            }
        }

        $ret .= '</ul>';
        return $ret;
    }
}
