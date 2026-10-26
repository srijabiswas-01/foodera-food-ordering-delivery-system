document.querySelectorAll('.cart-checkout-form').forEach((form) => {
    const methods = form.querySelectorAll('input[name="payment_method"]');
    const details = form.querySelectorAll('[data-payment-detail]');

    function syncPaymentFields() {
        const selected = form.querySelector('input[name="payment_method"]:checked')?.value;
        details.forEach((detail) => {
            const active = detail.dataset.paymentDetail === selected;
            detail.hidden = !active;
            detail.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !active;
                field.required = active;
            });
        });
    }

    methods.forEach((method) => method.addEventListener('change', syncPaymentFields));
    syncPaymentFields();
});
