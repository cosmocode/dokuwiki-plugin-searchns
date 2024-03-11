jQuery(function () {
    const storageName = 'searchns_ns';
    const $in = jQuery('#qsearchns__in');
    const $select = jQuery('#qsearchns__ns');
    const $out = jQuery('#qsearch__out');

    if (!$in || !$out || !$select) return;

    let timeout;

    // try restoring a saved namespace, or first option if stored is not present
    const toRestore = localStorage.getItem(storageName);
    if (toRestore && $select.find('option[value="' + toRestore + '"').length > 0) {
        $select.val(toRestore);
    } else {
        $select[0].selectedIndex = 0;
    }

    /**
     * Fetch results with AJAX
     *
     * @returns {Promise<void>}
     */
    async function doSearch() {
        jQuery.post(
            DOKU_BASE + 'lib/exe/ajax.php',
            {
                call: 'plugin_searchns_qsearch',
                q: encodeURI($in.val()),
                ns: encodeURI($select.val())
            },
            function (data) {
                if (data.length === 0 ) {
                    return;
                }
                $out
                    .html(data)
                    .show()
                    .css('white-space', 'nowrap');
            },
            'html'
        );
    }

    // event listener on search input
    $in.on('keyup', function (evt) {
        if ($in.val() === '') {
            $out.text = '';
            $out.hide();
        } else {
            if (timeout) {
                window.clearTimeout(timeout);
                timeout = null;
            }
            timeout = window.setTimeout(doSearch, 500);
        }
    });

    // event listener on namespace selector
    $select.on('change', function (evt) {
        localStorage.setItem(storageName, evt.target.value);
    });
});
