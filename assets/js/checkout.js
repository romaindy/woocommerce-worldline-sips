(function ($) {

    const $formCheckout = $('form.checkout');
    // Update checkout on payment method change.

    $formCheckout.on('change', 'input[name="payment_method"]', function () {
        $(document.body).trigger('update_checkout');
    });

    $formCheckout.on('checkout_place_order_success', function () {

        window.scrollTo(0, 0);

        // Show the iFrame.
        $('#worldline-sips-woocommerce-iframe').show();

        //Move the Iframe.
        $('.payment_box.payment_method_worldline_sips').appendTo($('.woocommerce'));

        // Remove form.
        $formCheckout.remove();

        // Hide the button.
        $('#place_order').hide();

    });
})(jQuery);