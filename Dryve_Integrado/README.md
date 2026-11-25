# Dryve

O Dryve ajuda quem está tirando a CNH a encontrar instrutores, marcar aulas práticas e estudar para a prova teórica. O site se adapta à tela do celular e usa PHP, HTML, CSS, JavaScript e MySQL ou MariaDB.

## Rodar no XAMPP

1. Extraia o ZIP. Copie a pasta `Dryve_Integrado` para `C:\xampp\htdocs`.
2. No XAMPP, inicie Apache e MySQL. Abra `http://localhost/phpmyadmin` e importe o `database.sql` que veio ao lado da pasta. A exportação cria o banco `dryve` e inclui dados fictícios para a apresentação.
3. Acesse `http://localhost/Dryve_Integrado/`. Em instalações com outra porta ou senha de MySQL, ajuste `sprint1/config.php`. O padrão é `127.0.0.1:3306`, usuário `root`, senha vazia.

O projeto usa PHP 8.1 ou mais recente com `mysqli`. A importação para avaliação deve usar um banco limpo. Se já existir um banco `dryve` com dados que precisam ser preservados, faça um backup antes.

## Contas para apresentar

| Perfil | Usuário | Senha |
| --- | --- | --- |
| Aluno | `aluno_demo` | `Dryve2026` |
| Instrutora, categoria B | `instrutor_demo` | `Dryve2026` |
| Instrutor, categoria A | `instrutor_a_demo` | `Dryve2026` |
| Administrador | `admin_demo` | `Dryve2026` |

Abra a conta da Ana e a da Mariana em perfis ou navegadores diferentes. A Ana já tem uma aula concluída para mostrar o pós-aula e emitir o comprovante assim que terminar os módulos. A importação cria horários para os próximos dias. A Mariana pode publicar outros em **Minha grade**.

### Caminho de demonstração

1. Na conta da Ana, abra **Instrutores**, filtre pela categoria B e escolha um horário de Mariana. Use `DRYVE10` para ver o desconto.
2. Como Mariana, abra **Agendamentos** e aceite a solicitação. Volte à Ana e registre o pagamento em teste. A aula já aparece em **Acompanhamento**.
3. Abra a aula nos dois perfis, teste o chat e trace a rota. A instrutora pode compartilhar o GPS com permissão do navegador, informar atraso, iniciar e concluir a aula.
4. Em **Progresso**, abra uma aula concluída. Avalie no perfil de aluno e registre a observação no de instrutor. Em **Estudos**, marque os três módulos como concluídos. O comprovante aparece com todos os módulos e pelo menos uma aula concluídos. O botão de impressão também permite salvar em PDF.

## Configuração de e-mail, mapa e pagamento

- **Pagamento.** Para apresentar, use Pix ou cartão simulado. O pagamento e o estorno ficam no banco. O checkout opcional do Stripe também é de teste. Para usá-lo, informe a chave `sk_test_` em `DRYVE_STRIPE_TESTE`, habilite `curl` no PHP e configure a URL do site em `DRYVE_URL`. Depois de voltar do Stripe, clique em **Confirmar pagamento de teste**.
- **E-mail.** Configure o transporte de `mail()` do PHP e defina o remetente em `DRYVE_EMAIL_REMETENTE` e a URL em `DRYVE_URL`. Para testar localmente, um capturador SMTP é suficiente. No Windows, configure `SMTP` e `smtp_port` no `php.ini` e reinicie o Apache. A recuperação envia um link de uso único. O aviso de atraso é enviado ao registrar o atraso. Para enviar lembretes até com o site fechado, agende a execução de `C:\xampp\php\php.exe C:\xampp\htdocs\Dryve_Integrado\sprint4\cron_lembretes.php` a cada cinco minutos, com a mesma configuração. Há avisos dentro do site e notificações do navegador com a tela de acompanhamento aberta.
- **Mapa.** Os pontos da aula e a localização compartilhada aparecem no mapa com Leaflet. O mapa-base do OpenStreetMap e a rota pelas ruas via OSRM dependem de internet. A consulta da rota usa os pontos cadastrados para a aula e o GPS do instrutor é compartilhado separadamente com os participantes. GPS e permissão de notificação exigem HTTPS ou localhost.
- **Estudos.** Os módulos usam vídeos e materiais públicos do Detran. Para uma aula ao vivo, o administrador publica o link da reunião e depois pode incluir a gravação. Os três PDFs podem ser baixados pelo site. O simulado tem 30 questões de prática, quatro alternativas por questão, meta de 20 acertos e cronômetro de 60 ou 120 minutos.

O comprovante registra as atividades concluídas no projeto.

## Arquivos

- `sprint1` · cadastro, login, recuperação, conta e painel.
- `sprint2` · busca, favoritos, avaliações públicas e grade do instrutor.
- `sprint3` · solicitações, cupons, mudanças de horário e checkout.
- `sprint4` · mapa, GPS, chat, alertas e conclusão de aulas.
- `sprint5` · módulos, transmissões, simulado, comunidade e PDFs.
- `sprint6` · pós-aula, progresso, transações e comprovante.
- `docs` · diagrama de casos de uso e registro da validação.
