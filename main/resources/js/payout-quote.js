export function mountPayoutQuote(form) {
    const amount = form.querySelector('[name="amount"]');
    const quote = form.querySelector('[data-payout-net]');
    const fee = Number(form.querySelector('[name="quoted_fee_minor"]').value);
    const minimum = Math.max(Number(form.dataset.minimum), fee + 1);
    const available = Number(form.dataset.available);
    const money = minor => `PHP ${(minor / 100).toFixed(2)}`;

    const update = () => {
        const match = /^(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?$/.exec(amount.value.trim());
        if (!match) {
            quote.textContent = 'Enter an amount in pesos, with up to two decimal places.';
            return;
        }
        const gross = Number(match[1]) * 100 + Number((match[2] || '').padEnd(2, '0'));
        if (gross < minimum || gross > available) {
            quote.textContent = `Enter an amount from ${money(minimum)} to ${money(available)}.`;
            return;
        }
        quote.textContent = `${money(gross)} minus ${money(fee)} fee = ${money(gross - fee)} to the recipient.`;
    };

    amount.addEventListener('input', update);
    if (amount.value) update();
}
