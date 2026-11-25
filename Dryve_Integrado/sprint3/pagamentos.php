<?php

function stripe_ativo() {
    return str_starts_with(getenv('DRYVE_STRIPE_TESTE') ?: '', 'sk_test_');
}

function stripe($rota, $dados = null, $chave = '') {
    if (!stripe_ativo() || !function_exists('curl_init')) {
        throw new DomainException('O checkout de teste precisa da chave Stripe e da extensão cURL.');
    }
    $curl = curl_init('https://api.stripe.com/v1/' . $rota);
    curl_setopt_array($curl, [
        CURLOPT_USERPWD => getenv('DRYVE_STRIPE_TESTE') . ':',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 10,
    ]);
    if ($dados !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($dados));
        if ($chave) curl_setopt($curl, CURLOPT_HTTPHEADER, ['Idempotency-Key: ' . $chave]);
    }
    $resultado = json_decode((string) curl_exec($curl), true);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($status < 200 || $status >= 300 || !is_array($resultado) || ($resultado['livemode'] ?? false)) {
        throw new DomainException('O serviço de pagamento de teste está indisponível. Tente novamente.');
    }
    return $resultado;
}

function checkout_aberto($a) {
    return linha('SELECT sessao, url FROM checkout_testes WHERE agendamento_id = ?', [$a['id']]);
}

function registrar_pagamento_stripe($a, $sessao) {
    if ($sessao['payment_status'] !== 'paid') return false;
    $centavos = (int) round(total_agendamento($a) * 100);
    if (($sessao['client_reference_id'] ?? '') !== (string) $a['id'] ||
        $sessao['amount_total'] !== $centavos || $sessao['currency'] !== 'brl' ||
        !is_string($sessao['payment_intent'] ?? null)) {
        throw new DomainException('O pagamento retornado não corresponde ao agendamento.');
    }
    consulta("INSERT INTO transacoes (agendamento_id, tipo, valor, metodo, referencia)
        VALUES (?, 'pagamento', ?, 'Stripe teste', ?)", [$a['id'], total_agendamento($a), $sessao['payment_intent']]);
    return true;
}

function iniciar_checkout($a) {
    global $url_base;
    $atual = checkout_aberto($a);
    if ($atual) {
        $sessao = stripe('checkout/sessions/' . rawurlencode($atual['sessao']));
        if (registrar_pagamento_stripe($a, $sessao)) return null;
        if ($sessao['status'] === 'open') return $atual['url'];
        if ($sessao['status'] !== 'expired') throw new DomainException('O pagamento está em processamento.');
    }
    $sessao = stripe('checkout/sessions', [
        'mode' => 'payment', 'payment_method_types' => ['card'], 'client_reference_id' => (string) $a['id'],
        'success_url' => $url_base . '/sprint3/index.php?retorno=1',
        'cancel_url' => $url_base . '/sprint3/index.php',
        'line_items' => [[
            'quantity' => 1, 'price_data' => [
                'currency' => 'brl', 'unit_amount' => (int) round(total_agendamento($a) * 100),
                'product_data' => ['name' => 'Aula Dryve #' . $a['id']],
            ],
        ]],
    ], 'dryve-aula-' . $a['id'] . '-' . ($atual['sessao'] ?? 'primeiro'));
    consulta('INSERT INTO checkout_testes (agendamento_id, sessao, url) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE sessao = ?, url = ?',
        [$a['id'], $sessao['id'], $sessao['url'], $sessao['id'], $sessao['url']]);
    return $sessao['url'];
}

function estornar_pagamento($a) {
    $pagamento = linha("SELECT * FROM transacoes WHERE agendamento_id = ? AND tipo = 'pagamento'", [$a['id']]);
    if (!$pagamento && ($atual = checkout_aberto($a))) {
        $rota = 'checkout/sessions/' . rawurlencode($atual['sessao']);
        $sessao = stripe($rota);
        if ($sessao['status'] === 'open') {
            stripe($rota . '/expire', []);
        } elseif (registrar_pagamento_stripe($a, $sessao)) {
            $pagamento = linha("SELECT * FROM transacoes WHERE agendamento_id = ? AND tipo = 'pagamento'", [$a['id']]);
        } elseif ($sessao['status'] !== 'expired') {
            throw new DomainException('Aguarde o processamento do pagamento para cancelar.');
        }
    }
    if (!$pagamento) return;
    $referencia = null;
    if ($pagamento['referencia']) {
        $estorno = stripe('refunds', ['payment_intent' => $pagamento['referencia']], 'dryve-estorno-' . $pagamento['referencia']);
        if ($estorno['status'] !== 'succeeded') throw new DomainException('O estorno de teste ainda não foi confirmado.');
        $referencia = $estorno['id'];
    }
    consulta("INSERT INTO transacoes (agendamento_id, tipo, valor, metodo, referencia) VALUES (?, 'estorno', ?, ?, ?)",
        [$a['id'], $pagamento['valor'], $pagamento['metodo'], $referencia]);
}
