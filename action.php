<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
use dokuwiki\Form\InputElement;

/**
 * DokuWiki Plugin searchns (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Anna Dabrowska <dokuwiki@cosmocode.de>
 */
class action_plugin_searchns extends ActionPlugin
{
    /** @inheritDoc */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('FORM_QUICKSEARCH_OUTPUT', 'BEFORE', $this, 'handleForm');
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handleStart');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handleAjax');
    }

    /**
     * Performs the quick search
     *
     * @param Event $event
     * @return void
     */
    public function handleAjax(Event $event)
    {
        if ($event->data !== 'plugin_searchns_qsearch') return;

        $event->preventDefault();
        $event->stopPropagation();

        /** @var helper_plugin_searchns $helper */
        $helper = plugin_load('helper', 'searchns');
        echo $helper->qSearch();
    }

    /**
     * Adds selected namespace to global query
     * @param Event $event
     * @return void
     */
    public function handleStart(Event $event)
    {
        global $INPUT;
        global $QUERY;

        if ($INPUT->str('ns')) {
            $QUERY .= ' @' . $INPUT->str('ns');
        }
    }

    /**
     * Modifies the quicksearch form
     *
     * @param Event $event Event object
     * @return void
     */
    public function handleForm(Event $event)
    {
        global $ACT;
        global $QUERY;
        global $INPUT;
        global $lang;

        /** @var \dokuwiki\Form\Form $form */
        $form = $event->data;

        /** @var \helper_plugin_searchns $helper */
        $helper = plugin_load('helper', 'searchns');

        $ns = $INPUT->str('ns');

        // strip namespace from text input, we have a dropdown for this
        if ($ns) {
            $q = str_replace('@' . $ns, '', $QUERY);
        } else {
            $q = '';
        }

        $newQ = new InputElement('text', 'q');
        $newQ->addClass('edit')
            ->attrs([
                'title' => '[F]',
                'accesskey' => 'f',
                'placeholder' => $lang['btn_search'],
                'autocomplete' => 'off',
            ])
            ->id('qsearchns__in')
            ->val($ACT === 'search' ? $q : '')
            ->useInput(false);

        $form->replaceElement(
            $newQ,
            $form->findPositionByAttribute('name', 'q')
        );

        // prepend namespace dropdown
        $form->addDropdown('ns',
            array_flip($helper->getNsFromConfig()),
            $this->getLang('namespace label'),
            0
            )
            ->id('qsearchns__ns')
            ->val($ns);
    }
}
