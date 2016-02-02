propertyGroup = function (widgetId) {

    var dataAddUrl;
    var dataDeleteUrl;
    var modelId;
    var el;

    this.widgetId = widgetId;
    this.url = '';

    this.init = function (element, url) {
        this.url = window.location.pathname;
        el = jQuery('#' + element);
        el.trigger('init::before');
        dataAddUrl = el.data('add-url');
        dataDeleteUrl = el.data('delete-url');
        modelId = el.data('model-id');
        el.trigger('init::after');
    };

    this.addGroup = function (id) {
        if (modelId) {
            addGroupByModel(id);
        } else {
            var attachedPropertyGroup = [];
            attachedPropertyGroup.push(id);
            jQuery('.property-group-ids').each(function (key, el) {
                attachedPropertyGroup.push(jQuery(el).val());
            });

            window.location = this.url + '?' + $.param({"propertyGroupIds": attachedPropertyGroup});
        }
    };

    this.deleteGroup = function (id) {
        if (modelId) {
            deleteGroupByModel(id);
        } else {
            var attachedPropertyGroup = [];
            jQuery('.property-group-ids').each(function (key, el) {
                if(id != jQuery(el).val()) {
                    attachedPropertyGroup.push(jQuery(el).val());
                }

            });
            window.location = this.url + '?' + $.param({"propertyGroupIds": attachedPropertyGroup});
        }
    };

    addGroupByModel = function (id) {
        jQuery.ajax({
            'url': dataAddUrl,
            'data': {
                'groupId': id
            },
            'success': function (data) {
                location.reload();
            }
        });
        return false;
    };

    deleteGroupByModel = function (id) {
        jQuery.ajax({
            'url': dataDeleteUrl,
            'data': {
                'groupId': id
            },
            'success': function (data) {
                location.reload();
            }
        });
        return false;
    };


    this.init(this.widgetId)

};
