/* globals $: false */

"use strict";

var RelatedProperty = {
    'settings'         : {
        'attrInputs'          : [
            '#property-params-handlerparams-nameattribute',
            '#property-params-handlerparams-attributes',
            '#property-params-handlerparams-sortorder'
        ],
        'getAttributeNamesUrl': '/en/properties/manage/get-attributes-names'
    },
    'getAttributeNames': function (className, callback) {
        var settings = this.settings;
        $.ajax({
            'data'    : {'className': className},
            'dataType': 'json',
            'success' : function (data) {
                if (typeof(callback) === 'function') {
                    $.each(settings.attrInputs, function (index, attr) {
                        callback(data, attr);
                    });
                }
            },
            'type'    : 'post',
            'url'     : settings.getAttributeNamesUrl
        });
    },
    'changeAttributes' : function (attributes, selectSelector) {
        if ($(selectSelector).prop('multiple')) {
            $(selectSelector + ' option').remove();
        } else {
            $(selectSelector + ' option:gt(0)').remove();
        }
        if (attributes.length !== 0) {
            $(selectSelector).prop('disabled', false);
        } else {
            $(selectSelector).prop('disabled', true);
        }
        $.each(attributes, function (index, option) {
            var $option = $("<option></option>")
                .attr("value", index)
                .text(option);
            $(selectSelector).append($option);
        });
    },
    'classNameSelected': function (className) {
        this.getAttributeNames(className, this.changeAttributes)
    }
};
