jQuery(function () {
    jQuery('body').on('click', '[data-action="add-property-group"]', function () {
        addGroup(this)
    })
        .on('click', '[data-action="delete-property-group"]', function () {
            deleteGroup(this)
        });
});

function addGroup(element) {
    var $this = jQuery(element);
    var url = $this.parents('[data-add-url]').eq(0).data('add-url');
    jQuery.ajax({
        'url': url,
        'data': {
            'groupId': $this.data('group-id')
        },
        'success': function (data) {
            location.reload();
        }
    });
    return false;
}

function deleteGroup(element) {
    var $this = jQuery(element);
    var url = $this.parents('[data-delete-url]').eq(0).data('delete-url');
    jQuery.ajax({
        'url': url,
        'data': {
            'groupId': $this.data('group-id')
        },
        'success': function (data) {
            location.reload();
        }
    });
    return false;
}