(() => {
    const nav = document.querySelector('.dryve-site-nav');
    if (!nav) return;
    const secao = location.pathname.match(/\/sprint[2-6]\//)?.[0];
    if (secao) {
        const link = [...nav.querySelectorAll('a')].find(a => new URL(a.href).pathname.includes(secao));
        link?.setAttribute('aria-current', 'page');
    }
    const path = document.currentScript && document.currentScript.src;
    const url = path ? new URL('../sessao.php', path) : null;
    const account = document.getElementById('dryve-account-link');
    const logout = document.getElementById('dryve-signout');
    if (!url || !account || !logout) return;

    fetch(url, {credentials: 'same-origin', cache: 'no-store'})
        .then(response => response.ok ? response.json() : null)
        .then(session => {
            if (!session || !session.logado) return;
            account.textContent = 'Minha conta';
            const base = new URL('../', path);
            account.href = new URL('sprint1/painel.php', base).href;
            logout.hidden = false;
        }).catch(() => {});
})();
