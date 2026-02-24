jQuery(document).ready(function($) {
    $('#ss-generate').on('click', function() {
        var btn = $(this), status = $('#ss-status');
        btn.prop('disabled', true);
        status.html('<span class="spinner is-active" style="float:none;vertical-align:middle;"></span> Generating...');
        $.post(semanticSitemap.ajaxUrl, {
            action: 'semantic_sitemap_generate',
            nonce:  semanticSitemap.nonce
        }, function(res) {
            if (res.success) {
                status.html('<span style="color:green;">✓ Generated ' + res.data.pages + ' pages — ' + res.data.time + '</span>');
            } else {
                status.html('<span style="color:red;">✗ ' + (res.data.message || 'Error') + '</span>');
            }
            btn.prop('disabled', false);
        }).fail(function() {
            status.html('<span style="color:red;">✗ Request failed</span>');
            btn.prop('disabled', false);
        });
    });
});
