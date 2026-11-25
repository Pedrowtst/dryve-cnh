-- Dryve | Exportação do esquema e dados fictícios para demonstração

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `dryve` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `dryve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `horario_id` int(11) NOT NULL,
  `aula_id` int(11) DEFAULT NULL,
  `responder_id` int(11) NOT NULL,
  `proposta_id` int(11) DEFAULT NULL,
  `status` enum('pendente','aceito','recusado','cancelado','reagendamento') NOT NULL DEFAULT 'pendente',
  `reserva` tinyint(4) DEFAULT 1,
  `categoria` enum('A','B') NOT NULL,
  `valor` decimal(8,2) NOT NULL,
  `desconto` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cupom` varchar(20) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `aula_id` (`aula_id`),
  UNIQUE KEY `horario_id` (`horario_id`,`reserva`),
  KEY `aluno_id` (`aluno_id`),
  KEY `proposta_id` (`proposta_id`),
  KEY `responder_id` (`responder_id`),
  CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `agendamentos_ibfk_2` FOREIGN KEY (`horario_id`) REFERENCES `horarios` (`id`),
  CONSTRAINT `agendamentos_ibfk_3` FOREIGN KEY (`aula_id`) REFERENCES `s4_aulas` (`id`),
  CONSTRAINT `agendamentos_ibfk_4` FOREIGN KEY (`proposta_id`) REFERENCES `horarios` (`id`),
  CONSTRAINT `agendamentos_ibfk_5` FOREIGN KEY (`responder_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `avaliacoes` (
  `aula_id` int(11) NOT NULL,
  `nota` tinyint(3) unsigned NOT NULL CHECK (`nota` between 1 and 5),
  `comentario` varchar(500) NOT NULL,
  PRIMARY KEY (`aula_id`),
  CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `s4_aulas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkout_testes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agendamento_id` int(11) NOT NULL,
  `sessao` varchar(120) NOT NULL,
  `url` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agendamento_id` (`agendamento_id`),
  CONSTRAINT `checkout_testes_ibfk_1` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comprovantes` (
  `aluno_id` int(11) NOT NULL,
  `codigo` char(16) NOT NULL,
  `emitido_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`aluno_id`),
  UNIQUE KEY `codigo` (`codigo`),
  CONSTRAINT `comprovantes_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `favoritos` (
  `aluno_id` int(11) NOT NULL,
  `instrutor_id` int(11) NOT NULL,
  PRIMARY KEY (`aluno_id`,`instrutor_id`),
  KEY `instrutor_id` (`instrutor_id`),
  CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`instrutor_id`) REFERENCES `instrutores` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `horarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instrutor_id` int(11) NOT NULL,
  `inicio` datetime NOT NULL,
  `ponto_nome` varchar(150) NOT NULL,
  `ponto_lat` decimal(10,7) NOT NULL,
  `ponto_lng` decimal(10,7) NOT NULL,
  `destino_nome` varchar(150) NOT NULL,
  `destino_lat` decimal(10,7) NOT NULL,
  `destino_lng` decimal(10,7) NOT NULL,
  `disponivel` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instrutor_id` (`instrutor_id`,`inicio`),
  CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`instrutor_id`) REFERENCES `instrutores` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `instrutores` (
  `usuario_id` int(11) NOT NULL,
  `categoria` enum('A','B','A/B') NOT NULL,
  `bairro` varchar(80) NOT NULL,
  `cidade` varchar(80) NOT NULL,
  `preco` decimal(8,2) NOT NULL,
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `instrutores_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `descricao` varchar(300) NOT NULL,
  `video_url` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `observacoes` (
  `aula_id` int(11) NOT NULL,
  `desempenho` enum('em_pratica','evoluindo','bom') NOT NULL,
  `texto` varchar(1000) NOT NULL,
  PRIMARY KEY (`aula_id`),
  CONSTRAINT `observacoes_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `s4_aulas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `progresso` (
  `aluno_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `concluido_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`aluno_id`,`modulo_id`),
  KEY `modulo_id` (`modulo_id`),
  CONSTRAINT `progresso_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `progresso_ibfk_2` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `publicacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `mensagem` varchar(800) NOT NULL,
  `resposta_a` int(11) DEFAULT NULL,
  `criada_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `resposta_a` (`resposta_a`),
  CONSTRAINT `publicacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `publicacoes_ibfk_2` FOREIGN KEY (`resposta_a`) REFERENCES `publicacoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `s4_aulas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `instrutor_id` int(11) NOT NULL,
  `inicio` datetime NOT NULL,
  `ponto_nome` varchar(150) NOT NULL,
  `ponto_lat` decimal(10,7) NOT NULL,
  `ponto_lng` decimal(10,7) NOT NULL,
  `destino_nome` varchar(150) NOT NULL,
  `destino_lat` decimal(10,7) NOT NULL,
  `destino_lng` decimal(10,7) NOT NULL,
  `status` enum('agendada','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'agendada',
  `atraso_minutos` int(11) NOT NULL DEFAULT 0,
  `compartilhando` tinyint(1) NOT NULL DEFAULT 0,
  `ultima_lat` decimal(10,7) DEFAULT NULL,
  `ultima_lng` decimal(10,7) DEFAULT NULL,
  `ultima_localizacao_em` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_s4_aulas_inicio` (`inicio`),
  KEY `idx_s4_aulas_aluno` (`aluno_id`),
  KEY `idx_s4_aulas_instrutor` (`instrutor_id`),
  CONSTRAINT `fk_s4_aula_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_s4_aula_instrutor` FOREIGN KEY (`instrutor_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `s4_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aula_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `mensagem` varchar(500) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_s4_msg_usuario` (`remetente_id`),
  KEY `idx_s4_msg_aula` (`aula_id`,`id`),
  CONSTRAINT `fk_s4_msg_aula` FOREIGN KEY (`aula_id`) REFERENCES `s4_aulas` (`id`),
  CONSTRAINT `fk_s4_msg_usuario` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `s4_notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aula_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `texto` varchar(255) NOT NULL,
  `codigo` varchar(80) DEFAULT NULL,
  `criada_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_s4_not_aula` (`aula_id`),
  KEY `idx_s4_not_user` (`usuario_id`,`aula_id`,`id`),
  CONSTRAINT `fk_s4_not_aula` FOREIGN KEY (`aula_id`) REFERENCES `s4_aulas` (`id`),
  CONSTRAINT `fk_s4_not_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agendamento_id` int(11) NOT NULL,
  `tipo` enum('pagamento','estorno') NOT NULL,
  `valor` decimal(8,2) NOT NULL,
  `metodo` varchar(30) NOT NULL,
  `referencia` varchar(120) DEFAULT NULL,
  `criada_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `agendamento_id` (`agendamento_id`,`tipo`),
  CONSTRAINT `transacoes_ibfk_1` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transmissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `inicio` datetime NOT NULL,
  `link_ao_vivo` varchar(500) NOT NULL,
  `link_gravacao` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(20) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `sobrenome` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `perfil` enum('aluno','instrutor','administrador') NOT NULL DEFAULT 'aluno',
  `token_recuperacao` varchar(64) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Contas e aulas fictícias para a apresentação. Senha de demonstração: Dryve2026
USE dryve;
SET NAMES utf8mb4;
SET time_zone = '-03:00';
SET @senha = '$2y$10$nD8bg2WoEXQGqvDE7mMxTeE.HFfpdtb850ZmxsIzUi.jn5G9A.1Hi';
INSERT IGNORE INTO usuarios (usuario, senha, nome, sobrenome, email, perfil) VALUES
('aluno_demo', @senha, 'Ana', 'Lima', 'aluno@exemplo.test', 'aluno'),
('instrutor_demo', @senha, 'Mariana', 'Costa', 'instrutor@exemplo.test', 'instrutor'),
('instrutor_a_demo', @senha, 'Rafael', 'Mendes', 'instrutor.a@exemplo.test', 'instrutor'),
('admin_demo', @senha, 'Admin', 'Dryve', 'admin@exemplo.test', 'administrador');

SET @ana = (SELECT id FROM usuarios WHERE usuario = 'aluno_demo');
SET @mariana = (SELECT id FROM usuarios WHERE usuario = 'instrutor_demo');
SET @rafael = (SELECT id FROM usuarios WHERE usuario = 'instrutor_a_demo');
SET @hoje = CURRENT_DATE();

INSERT IGNORE INTO instrutores (usuario_id, categoria, bairro, cidade, preco) VALUES
(@mariana, 'B', 'Centro', 'Curitiba', 85.00),
(@rafael, 'A', 'Água Verde', 'Curitiba', 75.00);

INSERT IGNORE INTO horarios (instrutor_id, inicio, ponto_nome, ponto_lat, ponto_lng,
    destino_nome, destino_lat, destino_lng) VALUES
(@mariana, TIMESTAMP(@hoje - INTERVAL 1 DAY, '14:00:00'), 'Praça Tiradentes', -25.42958, -49.27189, 'Passeio Público', -25.42460, -49.26785),
(@mariana, TIMESTAMP(@hoje + INTERVAL 1 DAY, '14:00:00'), 'Praça Tiradentes', -25.42958, -49.27189, 'Passeio Público', -25.42460, -49.26785),
(@mariana, TIMESTAMP(@hoje + INTERVAL 2 DAY, '15:00:00'), 'Praça Tiradentes', -25.42958, -49.27189, 'Passeio Público', -25.42460, -49.26785),
(@mariana, TIMESTAMP(@hoje + INTERVAL 3 DAY, '10:00:00'), 'Praça Tiradentes', -25.42958, -49.27189, 'Passeio Público', -25.42460, -49.26785),
(@rafael, TIMESTAMP(@hoje + INTERVAL 1 DAY, '16:00:00'), 'Praça do Japão', -25.44911, -49.28791, 'Praça Afonso Botelho', -25.44927, -49.27873);

SET @anterior = (SELECT id FROM horarios WHERE instrutor_id = @mariana AND inicio = TIMESTAMP(@hoje - INTERVAL 1 DAY, '14:00:00'));
INSERT INTO s4_aulas (aluno_id, instrutor_id, inicio, ponto_nome, ponto_lat, ponto_lng, destino_nome, destino_lat, destino_lng, status)
SELECT @ana, instrutor_id, inicio, ponto_nome, ponto_lat, ponto_lng, destino_nome, destino_lat, destino_lng, 'concluida'
FROM horarios WHERE id = @anterior AND NOT EXISTS (SELECT 1 FROM agendamentos WHERE horario_id = @anterior AND reserva = 1);
SET @aula_demo = (SELECT MAX(a.id) FROM s4_aulas a JOIN horarios h ON h.inicio = a.inicio AND h.instrutor_id = a.instrutor_id
    WHERE h.id = @anterior AND a.aluno_id = @ana);
INSERT IGNORE INTO agendamentos (aluno_id, horario_id, aula_id, responder_id, status, categoria, valor)
VALUES (@ana, @anterior, @aula_demo, @mariana, 'aceito', 'B', 85.00);
INSERT IGNORE INTO transacoes (agendamento_id, tipo, valor, metodo)
SELECT id, 'pagamento', 85.00, 'Pix simulado' FROM agendamentos WHERE aula_id = @aula_demo;

INSERT IGNORE INTO modulos (id, titulo, descricao, video_url) VALUES
(1, 'Legislação e convivência', 'O que muda no trânsito quando condutores respeitam as regras e evitam distrações. Vídeo do Detran-SP.', 'https://www.youtube.com/watch?v=ElHbj84wvyg'),
(2, 'Direção defensiva na chuva', 'Cuidados com o veículo e mudanças na condução quando a pista está molhada. Vídeo do Detran-DF.', 'https://www.youtube.com/watch?v=yTddrb4wtt0'),
(3, 'Segurança no trânsito', 'Campanhas em vídeo sobre atitudes seguras. Material da Escola Pública de Trânsito do Paraná.', 'https://www.detraneduca.pr.gov.br/Pagina/Videos-Educativos-de-Transito-Campanhas-Educativas');
