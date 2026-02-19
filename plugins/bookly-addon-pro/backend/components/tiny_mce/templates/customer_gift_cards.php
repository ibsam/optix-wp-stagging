<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly ?>
<script type="text/javascript">
    jQuery(function ($) {
        $('body').on('click', '#add-customer-gift-cards', function (e) {
            e.preventDefault();
            window.send_to_editor('[bookly-customer-gift-cards]');
            return false;
        });
    });
</script>