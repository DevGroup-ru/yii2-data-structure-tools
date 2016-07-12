"use strict";
(function () {
    const WIZARD_SELECTOR = '[data-wizard]';
    const HANDLER_SELECTOR = '#property-property_handler_id';
    const DATA_TYPE_SELECTOR = '#property-data_type';
    const ALLOW_IN_SEARCH_SELECTOR = '#property-in_search';
    const MULTIPLE_MODE_SELECTOR = '#property-allow_multiple_values';
    const STORAGE_SELECTOR = '#property-storage_id';

    const MODE_ALLOW_NOTHING = 10;
    const MODE_ALLOW_SINGLE = 11;
    const MODE_ALLOW_MULTIPLE = 12;
    const MODE_ALLOW_ALL = 13;

    var $set = $(WIZARD_SELECTOR),
        $handler = null,
        WizardData = window.WizardData || {};

    //initial setup
    //if this is not new record - just setting up storage & handler defaults
    if ('undefined' !== typeof WizardData.isNewRecord && WizardData.isNewRecord === true) {
        $set.each(function (i, e) {
            var $e = $(e),
                data = $e.data('wizard');
            if (data != 'handler') {
                resetInput($e);
                $e.prop('disabled', true);
            }
        });
    } else {
        getHandler();
        setHandlerDefaults($handler);
        setStorageDefaults($handler);
    }

    $(WIZARD_SELECTOR).change(function () {
        var $this = $(this),
            data = $this.data('wizard');
        switch (data) {
            case 'handler':
                var handlerId = parseInt($this.val());
                getHandler(handlerId);
                setHandlerDefaults($handler);
                setStorageDefaults($handler);
                break;
            case 'data-type' :
                getHandler();
                setStorageDefaults($handler);
                break;
        }
        chainReset($(this));
    });

    /**
     * Defaults are stored in the DataWizard[handlerId] array
     *
     * @param $handler
     */
    function setStorageDefaults($handler) {
        if (null === $handler || false === $handler.hasOwnProperty('allowedStorage')) {
            return;
        }
        var storage = $handler.allowedStorage || [],
            $storageSelect = $(STORAGE_SELECTOR);
        $('option', $storageSelect).each(function (i, el) {
            var $el = $(el),
                val = parseInt($el.val());
            if ($el.val() == "" || -1 !== storage.indexOf(val)) {
                $el.prop('disabled', false);
            } else {
                $el.prop('disabled', true);
            }
        });
    }

    /**
     * Gets property handler from array of objects WizardData by given or selected handler id
     *
     * @param handlerId
     */
    function getHandler(handlerId) {
        if ('undefined' === typeof handlerId) {
            handlerId = parseInt($('option:selected', $(HANDLER_SELECTOR)).val());
        }
        if ('undefined' !== typeof WizardData[handlerId]) {
            $handler = WizardData[handlerId];
        }
    }

    /**
     * Defaults are stored in the DataWizard[handlerId] array
     *
     * @param $handler
     */
    function setHandlerDefaults($handler) {
        if (null === $handler || false === $handler.hasOwnProperty('allowedTypes')) {
            return;
        }
        var types = $handler.allowedTypes || [],
            $inSearch = $(ALLOW_IN_SEARCH_SELECTOR),
            $multiple = $(MULTIPLE_MODE_SELECTOR),
            $dataType = $(DATA_TYPE_SELECTOR);

        $('option', $dataType).each(function (i, el) {
            var $el = $(el),
                val = parseInt($el.val());
            if ($el.val() == "" || -1 !== types.indexOf(val)) {
                $el.prop('disabled', false);
            } else {
                $el.prop('disabled', true);
            }
        });
        if ($handler.allowInSearch === true) {
            $inSearch.prop('disabled', false);
        } else {
            $inSearch.prop('checked', false).prop('disabled', true);
        }
        switch ($handler.multipleMode) {
            case MODE_ALLOW_ALL:
                $multiple.prop('disabled', false);
                break;
            case MODE_ALLOW_NOTHING:
            case MODE_ALLOW_SINGLE:
                $multiple.prop('checked', false).prop('disabled', true);
                break;
            case MODE_ALLOW_MULTIPLE:
                $multiple.prop('disabled', false).prop('checked', true);
                break;
            default:
                $multiple.prop('checked', false).prop('disabled', true);
        }
    }

    /**
     * Recursively resets all next elements that participate in wizard
     * first given element will not be modified
     *
     * @param $e
     * @param mark
     */
    function chainReset($e, mark) {
        var nextId = $e.data('wizard-next');
        if ('undefined' !== typeof nextId) {
            var $next = $('#' + nextId);
            if ('undefined' !== typeof $next) {
                if ('undefined' === typeof mark) {
                    $next.prop('disabled', false);
                } else {
                    $next.prop('disabled', true);
                }
                resetInput($next);
                chainReset($next, true);
            }
        }
    }

    /**
     * Resets input according to element type
     *
     * @param $e
     */
    function resetInput($e) {
        var type = $e.attr('type') || $e.prop('tagName');
        switch (type.toLowerCase()) {
            case 'text' :
            case 'textarea' :
            case 'password' :
                $e.val("");
                break;
            case 'checkbox' :
                $e.prop('checked', false) || $e.removeAttr('checked');
                break;
            case 'radio' :
                $e.prop('selected', false);
                break;
            case 'select':
                var $selectedSet = $('option:selected', $e);
                $selectedSet.prop('selected', false) || $selectedSet.removeAttr('selected');
                break;
        }
    }
})(jQuery);

