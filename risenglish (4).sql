-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/10/2025 às 02:46
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `risenglish`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos_turmas`
--

CREATE TABLE `alunos_turmas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `alunos_turmas`
--

INSERT INTO `alunos_turmas` (`id`, `aluno_id`, `turma_id`) VALUES
(7, 4, 1),
(10, 4, 2),
(8, 5, 1),
(11, 5, 2),
(9, 6, 1),
(6, 7, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas`
--

CREATE TABLE `aulas` (
  `id` int(11) NOT NULL,
  `titulo_aula` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_aula` date NOT NULL,
  `horario` time NOT NULL,
  `turma_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aulas`
--

INSERT INTO `aulas` (`id`, `titulo_aula`, `descricao`, `data_aula`, `horario`, `turma_id`, `professor_id`) VALUES
(3, 'myself', '', '2025-09-30', '11:00:00', 2, 2),
(4, 'sla', '', '2025-09-30', '10:00:00', 1, 2),
(5, 'testando', 'aulinha', '2025-09-19', '18:00:00', 2, 2),
(6, 'adwd', '', '2025-09-30', '00:00:00', 2, 2),
(7, 'adad', '', '2025-09-30', '23:00:00', 1, 2),
(8, 'n sei', '', '2025-10-02', '22:00:00', 2, 2),
(9, 'adfwdfa', '', '2025-10-15', '21:00:00', 1, 2),
(10, 'afaf', '', '2025-10-31', '00:00:00', 2, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas_conteudos`
--

CREATE TABLE `aulas_conteudos` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `conteudo_id` int(11) NOT NULL,
  `planejado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aulas_conteudos`
--

INSERT INTO `aulas_conteudos` (`id`, `aula_id`, `conteudo_id`, `planejado`) VALUES
(1, 8, 2, 0),
(2, 8, 10, 0),
(3, 8, 15, 1),
(4, 8, 1, 1),
(20, 9, 17, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `conteudos`
--

CREATE TABLE `conteudos` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo_arquivo` varchar(50) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conteudos`
--

INSERT INTO `conteudos` (`id`, `professor_id`, `parent_id`, `titulo`, `descricao`, `tipo_arquivo`, `caminho_arquivo`, `data_upload`) VALUES
(1, 2, NULL, 'MyCar', 'teste', 'jpeg', '../uploads/conteudos/cont_68d96e5bdebbd4.87423538.jpeg', '2025-09-28 17:20:27'),
(2, 2, 1, 'imagem teste', 'imagem teste (Arquivo: urus.jpeg)', 'image/jpeg', 'uploads/conteudos/1759411748_68de7e243304d.jpeg', '2025-10-02 13:29:08'),
(10, 2, 1, 'pdf teste', 'pdf teste (Arquivo: CONTRATO_Laura.pdf)', 'application/pdf', 'uploads/conteudos/1759413616_68de8570c2a0a.pdf', '2025-10-02 14:00:16'),
(12, 2, 1, 'ppt teste pdf', 'ppt teste pdf (Arquivo: pptTeste.pdf)', 'application/pdf', 'uploads/conteudos/1759414097_68de8751e13ba.pdf', '2025-10-02 14:08:17'),
(13, 2, 1, 'teste de link', 'teste de link (Link: youtu.be)', 'URL', 'https://youtu.be/aq-DH4iwviE?si=xYXctrZTNO343Tfq', '2025-10-02 14:36:05'),
(14, 2, 1, 'teste pdf de novo', 'teste pdf de novo (Arquivo: Mapa conceitual.pdf)', 'application/pdf', 'uploads/conteudos/1759415810_68de8e0289ad5.pdf', '2025-10-02 14:36:50'),
(15, 2, NULL, 'My self', '', 'TEMA', '', '2025-10-02 14:44:00'),
(17, 2, NULL, 'Friendship', '', 'TEMA', '', '2025-10-03 13:19:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recursos_uteis`
--

CREATE TABLE `recursos_uteis` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `link` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `recursos_uteis`
--

INSERT INTO `recursos_uteis` (`id`, `titulo`, `link`, `descricao`, `data_criacao`) VALUES
(1, 'Linguee', 'https://www.linguee.com.br/', 'Dicionário inglês-português\r\ne buscador de traduções.', '2025-10-06 21:21:17'),
(2, 'Youglish', 'https://pt.youglish.com/', 'Use o YouTube para melhorar sua pronúncia em inglês. Com mais de 100 milhões de faixas, o YouGlish te dá respostas rápidas e imparciais sobre como o inglês é falado por pessoas reais dentro de um contexto.', '2025-10-06 21:31:05'),
(3, 'toPhonetics', 'https://tophonetics.com/', 'Faça a tradução dos seus textos usando o Alfabético Fonético', '2025-10-06 21:33:23'),
(4, 'DeepL', 'https://www.deepl.com/pt-BR/translator', 'Tradutor recomendado', '2025-10-06 21:33:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int(11) NOT NULL,
  `nome_turma` varchar(100) NOT NULL,
  `professor_id` int(11) DEFAULT NULL,
  `inicio_turma` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id`, `nome_turma`, `professor_id`, `inicio_turma`) VALUES
(1, 'Turma teste', 2, '2025-09-28'),
(2, 'turma 1', 2, '2025-09-28');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` enum('admin','professor','aluno') NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `reset_token`, `token_expira_em`) VALUES
(1, 'Admin Risenglish', 'admin@risenglish.com', '$2y$10$/43jnz3JO8o5umNcabQ16eXnt1.pVdef3L7.6HvaILzUYbrRqPhBS', 'admin', NULL, NULL),
(2, 'Laura ', 'laura@risenglish.com', '$2y$10$/43jnz3JO8o5umNcabQ16eXnt1.pVdef3L7.6HvaILzUYbrRqPhBS', 'professor', NULL, NULL),
(4, 'Jorge Augusto Possani Pontes', 'jorgeappontes13@gmail.com', '$2y$10$pU0aqm5w/k.WZOVdU324F.4acQnOKbSJsotAH3eIaJ4U3uJd4Ftne', 'aluno', NULL, NULL),
(5, 'Rafael Tonetti Cardoso', 'rafaeltonetti.cardoso@gmail.com', '$2y$10$ABEbkqnbGsw.a6nFivA6quafuPOwnLwBEJzrpGzjHS7kyo4GTxDT6', 'aluno', NULL, NULL),
(6, 'Silene Cristina Possani', 'silene@gmail.com', '$2y$10$LZcwgOFnEzR6HUbm.R2QJugM1R5y5N.3mVm78DF46VfEPe1KXNf9K', 'aluno', NULL, NULL),
(7, 'João Victor', 'jv@gmail.com', '$2y$10$Ui89Uk.FUac.U0unv4OsvOBW/bugPb5BRrjHmzibFp7bmtQbdd1OC', 'aluno', NULL, NULL),
(8, 'Professor TESTE', 'profteste@risenglish.com', '$2y$10$DWtEWvdKsdhyoNjE9.7U6umkXyoqJdyiEezoLF4Hp6JB7e9VsOnye', 'professor', NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alunos_turmas`
--
ALTER TABLE `alunos_turmas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_id` (`aluno_id`,`turma_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `aulas_conteudos`
--
ALTER TABLE `aulas_conteudos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_id` (`aula_id`,`conteudo_id`),
  ADD KEY `conteudo_id` (`conteudo_id`);

--
-- Índices de tabela `conteudos`
--
ALTER TABLE `conteudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `recursos_uteis`
--
ALTER TABLE `recursos_uteis`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos_turmas`
--
ALTER TABLE `alunos_turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `aulas_conteudos`
--
ALTER TABLE `aulas_conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `conteudos`
--
ALTER TABLE `conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `recursos_uteis`
--
ALTER TABLE `recursos_uteis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alunos_turmas`
--
ALTER TABLE `alunos_turmas`
  ADD CONSTRAINT `alunos_turmas_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alunos_turmas_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `aulas_conteudos`
--
ALTER TABLE `aulas_conteudos`
  ADD CONSTRAINT `aulas_conteudos_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aulas_conteudos_ibfk_2` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `conteudos`
--
ALTER TABLE `conteudos`
  ADD CONSTRAINT `conteudos_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `turmas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
