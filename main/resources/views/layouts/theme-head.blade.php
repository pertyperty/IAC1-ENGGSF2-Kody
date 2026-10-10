{{-- Apply a validated preference before painting; unavailable storage falls back to the system. --}}
<script>
    (() => {
        let preference;
        try { preference = localStorage.getItem('kody-theme'); } catch {}
        try { document.documentElement.dataset.rail = localStorage.getItem('kody-rail') === 'expanded' ? 'expanded' : 'collapsed'; } catch { document.documentElement.dataset.rail = 'collapsed'; }
        document.documentElement.dataset.theme = preference === 'light' || preference === 'dark'
            ? preference : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    })();
</script>
