jQuery(function() {
    jQuery('body').on('click', '[data-action="add-new-eav-input"]', function() {
        var $lastInputGroup = jQuery(this).parents('.multi-eav').eq(0).find('.input-group').last();
        $lastInputGroup.clone().insertAfter($lastInputGroup).find('input').val('');
        return false;
    }).on('click', '[data-action="delete-eav-input"]', function() {
        var $itemGroup = jQuery(this).parents('.input-group').eq(0);
        if ($itemGroup.parents('.multi-eav').eq(0).find('.input-group').length > 1) {
            $itemGroup.remove();
        } else {
            $itemGroup.find('input').val('');
        }
        return false;
    });
    jQuery('.multi-eav').sortable({
        cursor: 'move',
        handle: '.input-group-addon.arrows'
    });
});