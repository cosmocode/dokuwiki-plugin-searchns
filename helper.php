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
    public const SEARCHNS_HEADING = 'SEARCHNS_HEADING';

    /**
     * @var array
     */
    protected $ns;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ns = $this->getNsFromConfig();
    }

    /**
     * Converts config string to array
     *
     * @return array
     */
    public function getNsFromConfig()
    {
        if (!is_null($this->ns)) {
            return $this->ns;
        }

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

        if ($this->getConf('first all')) {
            $ns = array_merge([$this->getLang('all label') => ''], $ns);
        } else {
            $ns[$this->getLang('all label')] = '';
        }

        return $ns;
    }

    /**
     * array_filter callback to remove namespaces that the current user can't read
     *
     * @param string $ns
     * @return bool
     */
    public function filterByACL($ns)
    {
        if ($ns === '') return true; // all is always allowed
        return auth_quickaclcheck($ns . ':*') >= AUTH_READ;
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
            $ret .= '<li>';
            $ret .= $title === self::SEARCHNS_HEADING ? $id : html_wikilink(':' . $id, $name);
            $ret .= '</li>';

            $counter++;
            if ($counter > $maxnumbersuggestions) {
                $ret .=  '<li>...</li>';
                break;
            }
        }

        $ret .= '</ul>';
        return $ret;
    }

    /**
     * Group results based on configured namespaces.
     * Insert headings as pseudo results.
     *
     * @param $results
     * @return array
     */
    public function sortAndGroup($results)
    {
        $namespaces = array_filter($this->getNsFromConfig());
        $original = $namespaces;

        // make sure more specific namespaces are sorted first
        uasort($namespaces, 'ft_pagesorter');
        $namespaces = array_reverse($namespaces);

        $res = [];
        foreach ($namespaces as $label => $ns) {
            $res[$ns] = array_filter($results, function ($page) use ($ns) {
                return strpos($page, $ns . ':') === 0;
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($res[$ns])) {
                // prepend namespace label
                $res[$ns] = array_merge([$label => self::SEARCHNS_HEADING], $res[$ns]);
                // remove matches from result set
                $results = array_diff_key($results, $res[$ns]);
            }
        }

        // reorder namespace as in the original configuration
        $all = [];
        foreach ($original as $ns) {
            if (!empty($res[$ns])) {
                $all = array_merge($all, $res[$ns]);
            }
        }

        // add the remainder as "other"
        $rest = array_diff_key($results, $all);
        if (!empty($rest)) {
            $all[$this->getLang('other label')] = self::SEARCHNS_HEADING;
            $all = array_merge($all, $rest);
        }

        return $all;
    }
}
