const checkout = document.getElementById('checkout');
const cupons = JSON.parse(checkout.dataset.cupons);
const valor = Math.round(Number(checkout.dataset.valor) * 100);
const moeda = new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'});

function atualizarCupom() {
    const codigo = document.getElementById('cupom').value.trim().toUpperCase();
    const desconto = Math.round(valor * (Object.hasOwn(cupons, codigo) ? cupons[codigo] : 0) / 100);
    document.getElementById('desconto').textContent = moeda.format(desconto / 100);
    document.getElementById('total').textContent = moeda.format((valor - desconto) / 100);
    document.getElementById('cupom-mensagem').textContent = !codigo ? '' :
        (desconto ? 'Cupom aplicado.' : 'Cupom inválido.');
}

document.getElementById('aplicar-cupom').addEventListener('click', atualizarCupom);
document.getElementById('cupom').addEventListener('input', atualizarCupom);
atualizarCupom();
