/**
 * YForm Saved Filters - JavaScript
 */

(function($) {
    'use strict';
    
    $(document).on('rex:ready', function() {
        // Auto-Focus für Filter-Name Input im Modal
        $('#yform-save-filter-modal').on('shown.bs.modal', function() {
            $('#filter_name').focus();
        });
    });
    
})(jQuery);
