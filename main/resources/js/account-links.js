// Email secrets remain in fragments until submitted by POST. A same-page email
// link may fire hashchange instead of loading the document again.
export function mountAccountLinks(document, window) {
    const links = [
        ['#verify-link-form', '#verification-token', 'token'],
        ['#recovery-token-form', '#recovery-token', 'recovery'],
    ].map(([form, input, key]) => ({ form: document.querySelector(form), input: document.querySelector(input), key }));
    let submitted = false;
    const consume = () => {
        if (!window.location.hash || !links.some((link) => link.form && link.input)) return;
        const params = new URLSearchParams(window.location.hash.slice(1));
        window.history.replaceState(null, '', window.location.pathname + window.location.search);
        if (submitted) return;
        for (const { form, input, key } of links) {
            const token = params.get(key);
            if (form && input && token && /^[a-f0-9]{64}$/.test(token)) {
                submitted = true;
                input.value = token;
                form.submit();
                break;
            }
        }
    };
    window.addEventListener('hashchange', consume);
    consume();
}
