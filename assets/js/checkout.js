(function ($) {
    // Update checkout on payment method change.
    $('form.checkout').on('change', 'input[name="payment_method"]', function () {
        $(document.body).trigger('update_checkout');
    });
})(jQuery);