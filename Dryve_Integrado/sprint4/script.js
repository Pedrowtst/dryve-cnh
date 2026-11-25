const $ = id => document.getElementById(id);
const estados = {agendada: "Agendada", em_andamento: "Em andamento", concluida: "Concluída", cancelada: "Cancelada"};
let aulaId = 0;
let aula;
let csrf;
let usuarioId;
let instrutor = false;
let mapa, encontro, destino, marcadorGps, rota;
let pedido, atualizacao, gps;
let ultimoChat, ultimosAvisos;
let atrasoEditado = false;

function status(texto, tipo = "info") {
    $("app-status").textContent = texto;
    $("app-status").className = "alert " + tipo;
    $("app-status").hidden = !texto;
}

function dataTexto(valor) {
    const data = new Date(valor.replace(" ", "T") + "-03:00");
    return data.toLocaleString("pt-BR", {dateStyle: "short", timeStyle: "short", timeZone: "America/Sao_Paulo"});
}

async function api(acao, dados, id = aulaId, signal) {
    const campos = new URLSearchParams({acao, aula_id: id, ...dados});
    const opcoes = {cache: "no-store", signal};
    let url = "api.php";
    if (dados) {
        campos.set("csrf", csrf);
        opcoes.method = "POST";
        opcoes.body = campos;
    } else {
        url += "?" + campos;
    }
    const resposta = await fetch(url, opcoes);
    const resultado = await resposta.json();
    if (!resposta.ok) {
        const erro = new Error(resultado.erro || "Não foi possível acessar a aula.");
        erro.status = resposta.status;
        throw erro;
    }
    return resultado;
}

function texto(tag, conteudo) {
    const elemento = document.createElement(tag);
    elemento.textContent = conteudo;
    return elemento;
}

function desenharMapa(novosPontos) {
    if (typeof L === "undefined") {
        $("mapa").textContent = "Não foi possível abrir o mapa.";
        $("btn-rota").disabled = $("btn-centralizar").disabled = true;
        return;
    }
    if (!mapa) {
        mapa = L.map("mapa", {scrollWheelZoom: false});
        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mapa);
    }
    if (novosPontos || !encontro) {
        for (const camada of [encontro, destino, rota]) if (camada) camada.remove();
        rota = null;
        encontro = L.marker([aula.ponto_lat, aula.ponto_lng]).bindPopup(texto("span", aula.ponto_nome)).addTo(mapa);
        destino = L.marker([aula.destino_lat, aula.destino_lng]).bindPopup(texto("span", aula.destino_nome)).addTo(mapa);
    }
    if (aula.localizacao_disponivel) {
        const posicao = [aula.ultima_lat, aula.ultima_lng];
        if (!marcadorGps) {
            marcadorGps = L.circleMarker(posicao, {radius: 9, color: "#17684a", fillOpacity: 0.9})
                .bindPopup("Localização do instrutor").addTo(mapa);
        } else {
            marcadorGps.setLatLng(posicao);
        }
    } else if (marcadorGps) {
        marcadorGps.remove();
        marcadorGps = null;
    }
    if (novosPontos) centralizarMapa();
}

function centralizarMapa() {
    if (!encontro) return;
    const pontos = [encontro.getLatLng(), destino.getLatLng()];
    if (marcadorGps) pontos.push(marcadorGps.getLatLng());
    mapa.fitBounds(L.latLngBounds(pontos).pad(0.22), {maxZoom: 15});
}

async function tracarRota() {
    if (!encontro) return;
    const id = aulaId;
    const data = aula.inicio;
    const pontos = [encontro.getLatLng(), destino.getLatLng()].map(p => p.lng + "," + p.lat).join(";");
    $("btn-rota").disabled = true;
    status("Consultando a rota…");
    try {
        const resposta = await fetch("https://router.project-osrm.org/route/v1/driving/" + pontos + "?overview=full&geometries=geojson",
            {signal: AbortSignal.timeout(8000)});
        if (!resposta.ok) throw new Error();
        const dados = await resposta.json();
        const caminho = dados.routes?.[0];
        if (!caminho?.geometry) throw new Error();
        if (id !== aulaId || data !== aula?.inicio) return;
        if (rota) rota.remove();
        rota = L.geoJSON(caminho.geometry, {style: {color: "#168653", weight: 5}}).addTo(mapa);
        mapa.fitBounds(rota.getBounds().pad(0.2), {maxZoom: 16});
        const km = (caminho.distance / 1000).toLocaleString("pt-BR", {maximumFractionDigits: 1});
        status(`Rota pelas ruas: ${km} km, cerca de ${Math.round(caminho.duration / 60)} min.`);
    } catch {
        if (id === aulaId) status("A rota está indisponível. Os pontos da aula continuam no mapa.", "warning");
    } finally {
        $("btn-rota").disabled = false;
    }
}

function renderizarAula() {
    const aberta = ["agendada", "em_andamento"].includes(aula.status);
    $("aula-titulo").textContent = "Aula prática";
    $("aula-resumo").textContent = dataTexto(aula.inicio) + " · " + aula.ponto_nome;
    $("aula-status").textContent = estados[aula.status];
    $("aula-select").selectedOptions[0].textContent = dataTexto(aula.inicio);
    $("detalhe-instrutor").textContent = aula.nome_instrutor;
    $("detalhe-horario").textContent = dataTexto(aula.inicio);
    $("detalhe-encontro").textContent = aula.ponto_nome;
    $("detalhe-destino").textContent = aula.destino_nome;
    $("delay-box").hidden = !instrutor || !aberta;
    $("instructor-actions").hidden = !instrutor || !aberta;
    $("btn-iniciar-aula").hidden = aula.status !== "agendada";
    $("btn-iniciar-aula").disabled = !aula.pode_iniciar;
    $("inicio-pendente").hidden = !instrutor || aula.status !== "agendada" || aula.pode_iniciar;
    $("btn-concluir-aula").hidden = aula.status !== "em_andamento";
    $("pos-aula").hidden = aula.status !== "concluida";
    $("pos-aula").href = "../sprint6/aula.php?id=" + aula.id;
    if (!atrasoEditado) $("atraso-minutos").value = aula.atraso_minutos;
    $("location-badge").textContent = aula.localizacao_disponivel ? "Localização ativa" : "Sem compartilhamento";
    $("status-instrutor").textContent = aula.localizacao_disponivel ? "Compartilhando localização" : "Localização indisponível";
    $("atualizacao-local").textContent = aula.localizacao_disponivel ? "Atualizado às " + aula.ultima_localizacao_em.slice(11, 16) : "";
    $("btn-localizacao").hidden = !instrutor || !aberta || Boolean(gps);
    $("btn-parar").hidden = !instrutor || !gps;
    $("chat-form").hidden = !aberta;
    $("chat-aviso").textContent = aberta ? "" : "Chat encerrado. As mensagens continuam disponíveis.";
}

function renderizarChat(mensagens) {
    const ultimo = mensagens.at(-1)?.id ?? 0;
    if (ultimo === ultimoChat) return;
    ultimoChat = ultimo;
    const caixa = $("mensagens");
    const posicao = caixa.scrollTop;
    const noFim = caixa.scrollHeight - posicao - caixa.clientHeight < 60;
    caixa.replaceChildren(...mensagens.map(m => {
        const item = document.createElement("div");
        item.className = "chat-bubble" + (Number(m.remetente_id) === usuarioId ? " mine" : "");
        item.append(texto("strong", m.remetente), texto("p", m.mensagem), texto("time", m.horario));
        return item;
    }));
    if (!mensagens.length) caixa.append(texto("p", "Nenhuma mensagem nesta aula."));
    caixa.scrollTop = noFim ? caixa.scrollHeight : posicao;
}

function renderizarAvisos(avisos) {
    const ultimo = avisos[0]?.id ?? 0;
    if (ultimo === ultimosAvisos) return;
    if (ultimosAvisos !== undefined && document.hidden && window.Notification?.permission === "granted") {
        for (const aviso of avisos.filter(a => a.id > ultimosAvisos)) {
            try { new Notification("Dryve", {body: aviso.texto}); } catch { /* O aviso também fica na página. */ }
        }
    }
    ultimosAvisos = ultimo;
    const lista = $("lista-avisos");
    lista.replaceChildren(...avisos.map(a => {
        const item = document.createElement("div");
        item.className = "notice";
        item.append(texto("p", a.texto), texto("small", a.horario));
        return item;
    }));
    if (!avisos.length) lista.append(texto("p", "Nenhum aviso para esta aula."));
}

async function atualizarEstado() {
    if (pedido || !aulaId) return;
    const atual = pedido = new AbortController();
    const id = aulaId;
    try {
        const dados = await api("estado", null, id, atual.signal);
        if (id !== aulaId || atual.signal.aborted) return;
        const mudou = aula?.id !== dados.aula.id || aula?.inicio !== dados.aula.inicio;
        if (gps && (mudou || !["agendada", "em_andamento"].includes(dados.aula.status))) pararGps();
        aula = dados.aula;
        instrutor = dados.instrutor;
        if ($("dados-da-aula").hidden) status("");
        $("selecao-aula").hidden = false;
        $("dados-da-aula").hidden = $("atalhos-aula").hidden = false;
        renderizarAula();
        desenharMapa(mudou);
        renderizarChat(dados.mensagens);
        renderizarAvisos(dados.avisos);
    } catch (erro) {
        if (!atual.signal.aborted) status(erro.message, "error");
    } finally {
        if (pedido === atual) pedido = null;
    }
}

async function iniciarPagina() {
    try {
        const dados = await api("inicio");
        if (!dados.aulas.length) {
            status("Você ainda não tem uma aula confirmada.");
            $("ir-agendamentos").hidden = false;
            return;
        }
        csrf = dados.csrf;
        usuarioId = Number(dados.usuario.id);
        const pedidoUrl = Number(new URLSearchParams(location.search).get("aula"));
        aulaId = dados.aulas.some(a => Number(a.id) === pedidoUrl) ? pedidoUrl : Number(dados.aulas[0].id);
        $("aula-select").replaceChildren(...dados.aulas.map(a => new Option(dataTexto(a.inicio), a.id)));
        $("aula-select").value = aulaId;
        $("aula-opcoes").hidden = dados.aulas.length < 2;
        $("aula-status").textContent = "Carregando…";
        $("selecao-aula").hidden = false;
        await atualizarEstado();
        atualizacao = setInterval(atualizarEstado, 5000);
    } catch (erro) {
        $("entrar-acompanhamento").hidden = erro.status !== 401;
        status(erro.status === 401 ? "Entre na sua conta para acompanhar uma aula." : "Não foi possível carregar suas aulas. Atualize a página.", "warning");
    }
}

async function pararGps() {
    if (!gps) return;
    const atual = gps;
    gps = null;
    navigator.geolocation.clearWatch(atual.watch);
    clearInterval(atual.repetir);
    try {
        await atual.envio;
        if (atual.iniciou) await api("localizacao", {comando: "parar"}, atual.aula);
    } catch (erro) {
        if (atual.aula === aulaId && erro.status !== 409) status("Não foi possível avisar que o GPS foi desligado.", "warning");
    }
}

function iniciarGps() {
    if (!navigator.geolocation || !window.isSecureContext) {
        status("Para compartilhar o GPS, abra o site em HTTPS ou no localhost.", "warning");
        return;
    }
    const atual = {aula: aulaId, iniciou: false, envio: null};
    gps = atual;
    function enviarGps() {
        if (gps !== atual || atual.envio || !atual.coords) return;
        atual.envio = api("localizacao", {
            comando: atual.iniciou ? "atualizar" : "iniciar",
            lat: atual.coords.latitude, lng: atual.coords.longitude
        }, atual.aula).then(() => {
            atual.iniciou = true;
            atualizarEstado();
        }).catch(erro => {
            status(erro.message, "warning");
            pararGps().then(renderizarAula);
        }).finally(() => { atual.envio = null; });
    }
    atual.watch = navigator.geolocation.watchPosition(posicao => {
        atual.coords = posicao.coords;
        enviarGps();
    }, () => {
        status("O navegador não conseguiu acessar sua localização.", "warning");
        pararGps().then(renderizarAula);
    }, {enableHighAccuracy: true, maximumAge: 10000, timeout: 18000});
    atual.repetir = setInterval(enviarGps, 30000);
    renderizarAula();
}

async function atualizar(acao, dados, mensagem) {
    try {
        await api(acao, dados);
        status(mensagem);
        await atualizarEstado();
        return true;
    } catch (erro) {
        status(erro.message, "error");
        return false;
    }
}

$("aula-select").addEventListener("change", evento => {
    pedido?.abort();
    pedido = null;
    pararGps();
    aulaId = Number(evento.target.value);
    ultimoChat = ultimosAvisos = undefined;
    atrasoEditado = false;
    $("dados-da-aula").hidden = $("atalhos-aula").hidden = true;
    $("aula-status").textContent = "Carregando…";
    $("aula-resumo").textContent = "";
    status("Carregando aula…");
    atualizarEstado();
});
$("btn-rota").addEventListener("click", tracarRota);
$("btn-centralizar").addEventListener("click", centralizarMapa);
$("btn-localizacao").addEventListener("click", iniciarGps);
$("btn-parar").addEventListener("click", async () => {
    await pararGps();
    renderizarAula();
    atualizarEstado();
});
$("atraso-minutos").addEventListener("change", () => { atrasoEditado = true; });
$("btn-atraso").addEventListener("click", async () => {
    const valor = $("atraso-minutos").value;
    if (await atualizar("atraso", {minutos: valor}, "Aviso de atraso atualizado.")) atrasoEditado = false;
});
$("btn-iniciar-aula").addEventListener("click", () => atualizar("situacao", {novo: "em_andamento"}, "Aula iniciada."));
$("btn-concluir-aula").addEventListener("click", async () => {
    if (!window.confirm("Concluir a aula?")) return;
    await pararGps();
    await atualizar("situacao", {novo: "concluida"}, "Aula concluída.");
});
$("chat-form").addEventListener("submit", async evento => {
    evento.preventDefault();
    const campo = $("mensagem");
    const mensagem = campo.value.trim();
    if (!mensagem) return;
    const botao = $("chat-form").querySelector('button[type="submit"]');
    if (botao.disabled) return;
    botao.disabled = true;
    if (await atualizar("enviar", {mensagem}, "Mensagem enviada.")) campo.value = "";
    botao.disabled = false;
});
$("btn-notificacoes").addEventListener("click", async () => {
    const permitido = window.Notification && window.isSecureContext && await Notification.requestPermission() === "granted";
    status(permitido ? "Avisos do navegador ativados." : "Veja os avisos nesta página.");
});
window.addEventListener("pagehide", () => {
    clearInterval(atualizacao);
    if (gps) {
        navigator.geolocation.clearWatch(gps.watch);
        clearInterval(gps.repetir);
        navigator.sendBeacon?.("api.php", new URLSearchParams({acao: "localizacao", aula_id: gps.aula, comando: "parar", csrf}));
        gps = null;
    }
});
window.addEventListener("pageshow", evento => {
    if (evento.persisted) iniciarPagina();
});

iniciarPagina();
