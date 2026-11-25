const mensagemGps = document.getElementById('mensagem-gps');

function localizar(preencher) {
    if (!navigator.geolocation) {
        mensagemGps.textContent = 'A localização está indisponível. Você pode preencher os dados manualmente.';
        return;
    }
    mensagemGps.textContent = 'Obtendo localização…';
    navigator.geolocation.getCurrentPosition(({coords}) => {
        preencher(coords.latitude.toFixed(6), coords.longitude.toFixed(6));
        mensagemGps.textContent = 'Localização preenchida.';
    }, () => {
        mensagemGps.textContent = 'Não foi possível obter a localização. Preencha os dados manualmente.';
    }, {timeout: 12000});
}

document.getElementById('usar-gps')?.addEventListener('click', () => {
    localizar((lat, lng) => {
        document.getElementById('busca-lat').value = lat;
        document.getElementById('busca-lng').value = lng;
        document.getElementById('busca').submit();
    });
});

document.querySelectorAll('[data-local]').forEach(botao => {
    botao.addEventListener('click', () => {
        localizar((lat, lng) => {
            document.getElementById(botao.dataset.local + '_lat').value = lat;
            document.getElementById(botao.dataset.local + '_lng').value = lng;
        });
    });
});
