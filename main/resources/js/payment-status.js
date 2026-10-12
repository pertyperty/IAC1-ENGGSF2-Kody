import { publishFeedback } from './feedback.js';

export function mountPaymentStatus(root, win) {
    const doc = root.ownerDocument;
    let endpoint;
    try {
        endpoint = new URL(root.dataset.paymentStatus, win.location.origin);
        if (endpoint.origin !== win.location.origin || !/^\/wallet\/purchases\/[a-f0-9-]{36}\/status$/.test(endpoint.pathname)) return;
    } catch { return; }
    const purchasePath = endpoint.pathname.replace(/\/status$/, '');
    const states = ['Queued', 'Creating', 'Pending', 'Review', 'Failed', 'Succeeded', 'Refunding', 'Refunded'];
    let timer = null, controller = null, attempts = 0, stopped = false, paused = false;
    const pause = () => { paused = true; win.clearTimeout(timer); timer = null; controller?.abort(); };
    const link = (selector, value) => {
        const control = root.querySelector(selector); control.hidden = true; control.removeAttribute('href');
        if (typeof value !== 'string') return;
        try {
            const url = new URL(value, win.location.origin);
            if (url.origin === win.location.origin && [purchasePath + '/checkout', purchasePath + '/receipt'].includes(url.pathname)) { control.href = url.href; control.hidden = false; }
        } catch {}
    };
    const poll = async () => {
        if (controller || doc.hidden || paused) return;
        controller = new AbortController(); attempts++;
        const timeout = win.setTimeout(() => controller?.abort(), 10000);
        const refresh = root.querySelector('[data-payment-refresh]'); refresh.disabled = true;
        try {
            const response = await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store', redirect: 'error', headers: { Accept: 'application/json' }, signal: controller.signal });
            if (paused || controller.signal.aborted) return;
            if ([401,403,404].includes(response.status)) { stopped = true; throw new Error('Access'); }
            if (!response.ok) throw new Error('Status unavailable');
            const data = await response.json();
            if (!states.includes(data.state) || typeof data.verified !== 'boolean' || (data.state === 'Succeeded' && !data.verified) || !Number.isSafeInteger(data.available) || data.available < 0) throw new Error('Invalid status');
            root.querySelector('[data-payment-state]').textContent = data.state;
            link('[data-payment-checkout]', data.checkout); link('[data-payment-receipt]', data.receipt);
            doc.querySelectorAll('[data-wallet-available]').forEach(node => node.textContent = data.available.toLocaleString('en-US'));
            const messages = { Queued: 'Your request is saved. Waiting for secure checkout creation.', Creating: 'Preparing secure checkout.', Pending: 'Awaiting verified payment. Returning from Xendit alone does not credit your wallet.',
                Review: 'This payment needs reconciliation. Do not submit a duplicate purchase.', Failed: 'This purchase was unsuccessful. No KodeBits were credited.', Succeeded: 'Payment verified. Your KodeBits are available and the payment receipt is ready.', Refunding: 'Refund processing. See the retained receipt and ledger history.', Refunded: 'Refund verified. The original purchase receipt remains in your history.' };
            root.querySelector('[data-payment-feedback]').textContent = messages[data.state];
            stopped = ['Succeeded','Failed','Review','Refunded'].includes(data.state);
            const receipt = root.querySelector('[data-payment-receipt]');
            if (stopped && root.dataset.lastPaymentState !== data.state) publishFeedback(root, messages[data.state], data.verified ? 'success' : 'info', false, null, !receipt.hidden ? { href: receipt.href, label: 'View receipt' } : null);
            root.dataset.lastPaymentState = data.state;
        } catch {
            if (paused) return;
            root.querySelector('[data-payment-feedback]').textContent = stopped ? 'Sign in again to check this purchase. Committed processing continues.' : 'Could not refresh payment status. Your saved purchase remains available; use Check status to retry.';
        } finally {
            win.clearTimeout(timeout); controller = null; refresh.disabled = false;
            if (!stopped && !paused && !doc.hidden && attempts < 30) timer = win.setTimeout(poll, 4000);
        }
    };
    root.querySelector('[data-payment-refresh]').addEventListener('click', () => { win.clearTimeout(timer); attempts = 0; stopped = false; paused = false; poll(); });
    win.addEventListener('pagehide', pause);
    win.addEventListener('pageshow', event => { if (event.persisted && !stopped) { paused = false; attempts = 0; poll(); } });
    doc.addEventListener('visibilitychange', () => { if (doc.hidden) pause(); else if (!stopped && attempts < 30) { paused = false; poll(); } });
    poll();
}
