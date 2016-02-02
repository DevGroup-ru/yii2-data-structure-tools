propertyGroup = function (widgetId) {

    var dataAddUrl;
    var dataDeleteUrl;
    var modelId;
    var el;

    this.widgetId = widgetId;
    this.url = '';

    this.init = function (element, url) {
        el = $('#' + element);
        el.trigger('init::before');
        this.url = window.location.pathname;
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
            $('.property-group-ids').each(function (key, el) {
                attachedPropertyGroup.push($(el).val());
            });

            window.location = this.url + '?' + $.param({"propertyGroupIds": attachedPropertyGroup});
        }
    };

    this.deleteGroup = function (id) {
        if (modelId) {
            deleteGroupByModel(id);
        } else {
            var attachedPropertyGroup = [];
            $('.property-group-ids').each(function (key, el) {
                if(id != $(el).val()) {
                    attachedPropertyGroup.push($(el).val());
                }

            });
            window.location = this.url + '?' + $.param({"propertyGroupIds": attachedPropertyGroup});
        }
    };

    addGroupByModel = function (id) {
        $.ajax({
            'url': dataAddUrl,
            type: 'post',
            'data': {'groupId': id},
            'success': function (data) {
                location.reload();
            }
        });
        return false;
    };

    deleteGroupByModel = function (id) {
        $.ajax({
            'url': dataDeleteUrl,
            type: 'delete',
            'data': {'groupId': id},
            'success': function (data) {
                location.reload();
            }
        });
        return false;
    };


    this.init(this.widgetId)

};
