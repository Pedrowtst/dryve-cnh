const formulario = document.getElementById('simulado-form');
const relogio = document.getElementById('tempo-restante');
const fim = Date.now() + Number(formulario.dataset.segundos) * 1000;

function atualizarRelogio() {
    const segundos = Math.max(0, Math.ceil((fim - Date.now()) / 1000));
    relogio.textContent = Math.floor(segundos / 60) + ':' + String(segundos % 60).padStart(2, '0');
    if (segundos === 0) {
        formulario.requestSubmit();
    } else {
        setTimeout(atualizarRelogio, 500);
    }
}

atualizarRelogio();
