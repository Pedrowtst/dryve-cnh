# Validação do Dryve

O projeto foi verificado com Apache, PHP 8.2, MariaDB 10.11 e Chrome, com contas separadas para aluno, instrutor e administrador. O arquivo [casos-de-uso.drawio](casos-de-uso.drawio) pode ser aberto no diagrams.net. Os códigos abaixo conferem as ligações do diagrama com as telas e as permissões do sistema.

## O que foi integrado

- A busca passou a usar instrutores e horários cadastrados no banco. A avaliação média é calculada a partir das aulas concluídas.
- Agendamentos, favoritos e publicações da comunidade passaram do armazenamento local do navegador para o banco e ficam ligados às contas. O aceite cria a aula de acompanhamento.
- O módulo de estudos ganhou registro do progresso, programação de transmissão, links de gravação e simulado com correção ao final.
- A Sprint 6 ligou a conclusão da aula ao registro pedagógico e à avaliação. O painel reúne módulos, aulas, pagamentos, estornos e comprovante.

## Casos de uso

| Caso | Quem usa | O que foi conferido |
| --- | --- | --- |
| UC01 · Criar conta e entrar | Aluno e instrutor | Cadastro, erro de senha, entrada e saída. Ao entrar pelo agendamento, o horário escolhido é mantido. O administrador usa uma conta já cadastrada. |
| UC02 · Recuperar senha | Aluno, instrutor e administrador | E-mail chegou ao capturador local. A nova senha funcionou e o link usado perdeu a validade. |
| UC03 · Buscar e favoritar instrutor | Aluno | Busca por categoria, bairro/cidade, nota e ordenação por GPS. O favorito ficou apenas na conta que o marcou. |
| UC04 · Publicar horários e locais | Instrutor | Perfil, categoria, preço, horários, ponto de encontro e destino. A grade refletiu a reserva. |
| UC05 · Solicitar aula e pagar | Aluno | Cupom, disponibilidade, pagamento em teste e bloqueio de pagamento duplicado. Validado com o simulador local. |
| UC06 · Responder e alterar agendamento | Aluno e instrutor | Aceite, recusa, cancelamento com estorno e reagendamento. O horário original pode ser usado enquanto a proposta estiver pendente. |
| UC07 · Ver mapa, rota, chat e avisos | Aluno e instrutor | A aula aceita apareceu no acompanhamento. Houve troca de mensagens, consulta da rota, alerta por atraso e lembrete. Uma terceira conta não teve acesso à aula. |
| UC08 · Compartilhar GPS e concluir aula | Instrutor | GPS ligado e desligado no navegador, com a posição aparecendo na conta do aluno. O chat fica disponível para leitura quando a aula termina. |
| UC09 · Assistir, estudar e baixar PDFs | Aluno | Módulos gravados, calendário de transmissões e download dos PDFs. |
| UC10 · Fazer simulado e participar do fórum | Aluno | Conferidas 30 questões com quatro opções, correção, publicação e resposta lida em outra sessão. |
| UC11 · Avaliar instrutor | Aluno | Avaliação de 1 a 5 e comentário após a aula. A busca passou a refletir a média. |
| UC12 · Registrar desempenho | Instrutor | Observação e nível de desempenho aparecem no pós-aula dos dois participantes. |
| UC13 · Consultar progresso e emitir comprovante | Aluno | Percentual, histórico de aulas, pagamento/estorno e comprovante com código persistente. Outra conta não teve acesso. |
| UC14 · Consultar usuários | Administrador | Lista acessível com o perfil de administrador. |
| UC15 · Publicar transmissão e gravação | Administrador | Links cadastrados pelo administrador aparecem em Estudos. O aluno não consegue acessar a gestão. |

## Pontos de configuração

O aceite, o pagamento local, a conclusão e o pós-aula foram exercitados juntos. Os e-mails foram recebidos em um capturador SMTP, com endereços fictícios. O Stripe é uma opção de teste que depende de credenciais, e a validação desta entrega usou o meio de pagamento local. A publicação da transmissão e da gravação foi testada com links de exemplo. A aula ao vivo acontece no serviço informado pelo administrador.

## Referências

- [Resolução CONTRAN nº 1.020/2025, artigos 31 a 34](https://www.gov.br/transportes/pt-br/assuntos/transito/conteudo-contran/resolucoes/Resolucao10202025.pdf) · formato de referência do simulado.
- [Código de Trânsito Brasileiro](https://www.planalto.gov.br/ccivil_03/leis/l9503compilado.htm) e [SAMU 192](https://www.gov.br/saude/pt-br/composicao/saes/samu-192/samu-192) · temas das questões de prática.
- [Detran-SP](https://www.youtube.com/watch?v=ElHbj84wvyg), [Detran-DF](https://www.youtube.com/watch?v=yTddrb4wtt0) e [Detran-PR Educa](https://www.detraneduca.pr.gov.br/Pagina/Videos-Educativos-de-Transito-Campanhas-Educativas) · materiais em vídeo.
- [Leaflet](https://leafletjs.com/), [OpenStreetMap](https://www.openstreetmap.org/copyright) e [OSRM](https://project-osrm.org/docs/v26.6.1/http) · mapa e cálculo de rota.
- [Stripe Checkout em ambiente de teste](https://docs.stripe.com/api/checkout/sessions/create) · opção de checkout hospedado.
