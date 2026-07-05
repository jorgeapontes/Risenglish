-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01/07/2026 às 16:48
-- Versão do servidor: 11.8.8-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u569225384_risenglish`
--
CREATE DATABASE IF NOT EXISTS `u569225384_risenglish` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `u569225384_risenglish`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos_turmas`
--

CREATE TABLE `alunos_turmas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anotacoes_aula`
--

CREATE TABLE `anotacoes_aula` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `comentario_professor` text DEFAULT NULL,
  `visto` tinyint(1) NOT NULL DEFAULT 0,
  `data_visto` timestamp NULL DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anotacoes_itens`
--

CREATE TABLE `anotacoes_itens` (
  `id` int(11) NOT NULL,
  `anotacao_id` int(11) NOT NULL,
  `autor` varchar(50) NOT NULL,
  `conteudo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anotacoes_visualizacoes`
--

CREATE TABLE `anotacoes_visualizacoes` (
  `id` int(11) NOT NULL,
  `anotacao_id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `data_visualizacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `arquivos_visiveis`
--

CREATE TABLE `arquivos_visiveis` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `conteudo_id` int(11) NOT NULL,
  `visivel` tinyint(1) NOT NULL DEFAULT 1,
  `data_modificacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `professor_id` int(11) NOT NULL,
  `recorrente` tinyint(1) NOT NULL DEFAULT 0,
  `dia_semana` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `caderno_anotacoes`
--

CREATE TABLE `caderno_anotacoes` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `conteudos`
--

CREATE TABLE `conteudos` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo_arquivo` varchar(50) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `eh_subpasta` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `grupos_conteudos`
--

CREATE TABLE `grupos_conteudos` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cor` varchar(20) DEFAULT '#081d40',
  `icone` varchar(50) DEFAULT 'fas fa-layer-group',
  `ordem` int(11) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `origem` enum('Instagram','Facebook','Google','Indicação','Site','Outro') DEFAULT 'Outro',
  `nivel_ingles` varchar(100) DEFAULT NULL,
  `status` enum('novo','em_contato','aula_experimental','matriculado') NOT NULL DEFAULT 'novo',
  `data_aula_experimental` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_acesso`
--

CREATE TABLE `logs_acesso` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `tipo_evento` varchar(50) DEFAULT NULL,
  `data_acesso` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'ID do usuário que receberá a notificação',
  `tipo` enum('anotacao_aluno','comentario_professor','aula_agendada','pagamento','sistema') NOT NULL DEFAULT 'sistema',
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `aula_id` int(11) DEFAULT NULL,
  `icone` varchar(50) DEFAULT 'fas fa-bell',
  `cor` varchar(20) DEFAULT '#c0392b',
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_leitura` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `mes_referencia` date NOT NULL COMMENT 'Primeiro dia do mês de referência',
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_modificacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `presenca_aula`
--

CREATE TABLE `presenca_aula` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT 1,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tentativas_ip`
--

CREATE TABLE `tentativas_ip` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `tentativas` int(11) DEFAULT 1,
  `bloqueado_ate` datetime DEFAULT NULL,
  `ultima_tentativa` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int(11) NOT NULL,
  `nome_turma` varchar(100) NOT NULL,
  `professor_id` int(11) DEFAULT NULL,
  `inicio_turma` date NOT NULL,
  `link_aula` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tentativas_falhas` int(11) DEFAULT 0,
  `bloqueado_ate` datetime DEFAULT NULL,
  `tipo_usuario` enum('admin','professor','aluno') NOT NULL,
  `informacoes` text DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `token_expira_em` datetime DEFAULT NULL,
  `dia_vencimento` int(2) DEFAULT NULL COMMENT 'Dia do mês para vencimento (1-31)',
  `status` enum('ativo','desativado') NOT NULL DEFAULT 'ativo',
  `responsavel_financeiro_id` int(11) DEFAULT NULL,
  `nao_pagante` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios_anexos`
--

CREATE TABLE `usuarios_anexos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Índices de tabela `anotacoes_aula`
--
ALTER TABLE `anotacoes_aula`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_aluno_unique` (`aula_id`,`aluno_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `anotacoes_itens`
--
ALTER TABLE `anotacoes_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anotacao_id` (`anotacao_id`);

--
-- Índices de tabela `anotacoes_visualizacoes`
--
ALTER TABLE `anotacoes_visualizacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anotacao_id` (`anotacao_id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `arquivos_visiveis`
--
ALTER TABLE `arquivos_visiveis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_conteudo_unique` (`aula_id`,`conteudo_id`),
  ADD KEY `aula_id` (`aula_id`),
  ADD KEY `conteudo_id` (`conteudo_id`);

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
-- Índices de tabela `caderno_anotacoes`
--
ALTER TABLE `caderno_anotacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `conteudos`
--
ALTER TABLE `conteudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `conteudos_ibfk_2` (`grupo_id`);

--
-- Índices de tabela `grupos_conteudos`
--
ALTER TABLE `grupos_conteudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`);

--
-- Índices de tabela `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `logs_acesso`
--
ALTER TABLE `logs_acesso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuario_log` (`usuario_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `lida` (`lida`),
  ADD KEY `data_criacao` (`data_criacao`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_mes_unique` (`aluno_id`,`mes_referencia`),
  ADD KEY `idx_mes_referencia` (`mes_referencia`),
  ADD KEY `idx_aluno_mes` (`aluno_id`,`mes_referencia`);

--
-- Índices de tabela `presenca_aula`
--
ALTER TABLE `presenca_aula`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aula_aluno_unique` (`aula_id`,`aluno_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `recursos_uteis`
--
ALTER TABLE `recursos_uteis`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Índices de tabela `tentativas_ip`
--
ALTER TABLE `tentativas_ip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip`),
  ADD KEY `idx_bloqueado` (`bloqueado_ate`);

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
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `fk_responsavel` (`responsavel_financeiro_id`);

--
-- Índices de tabela `usuarios_anexos`
--
ALTER TABLE `usuarios_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos_turmas`
--
ALTER TABLE `alunos_turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `anotacoes_aula`
--
ALTER TABLE `anotacoes_aula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `anotacoes_itens`
--
ALTER TABLE `anotacoes_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `anotacoes_visualizacoes`
--
ALTER TABLE `anotacoes_visualizacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `arquivos_visiveis`
--
ALTER TABLE `arquivos_visiveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `aulas_conteudos`
--
ALTER TABLE `aulas_conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `caderno_anotacoes`
--
ALTER TABLE `caderno_anotacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `conteudos`
--
ALTER TABLE `conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `grupos_conteudos`
--
ALTER TABLE `grupos_conteudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_acesso`
--
ALTER TABLE `logs_acesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `presenca_aula`
--
ALTER TABLE `presenca_aula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `recursos_uteis`
--
ALTER TABLE `recursos_uteis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tentativas_ip`
--
ALTER TABLE `tentativas_ip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios_anexos`
--
ALTER TABLE `usuarios_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Restrições para tabelas `anotacoes_aula`
--
ALTER TABLE `anotacoes_aula`
  ADD CONSTRAINT `anotacoes_aula_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anotacoes_aula_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `anotacoes_itens`
--
ALTER TABLE `anotacoes_itens`
  ADD CONSTRAINT `anotacoes_itens_ibfk_1` FOREIGN KEY (`anotacao_id`) REFERENCES `anotacoes_aula` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `anotacoes_visualizacoes`
--
ALTER TABLE `anotacoes_visualizacoes`
  ADD CONSTRAINT `anotacoes_visualizacoes_ibfk_1` FOREIGN KEY (`anotacao_id`) REFERENCES `anotacoes_aula` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `anotacoes_visualizacoes_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `arquivos_visiveis`
--
ALTER TABLE `arquivos_visiveis`
  ADD CONSTRAINT `arquivos_visiveis_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `arquivos_visiveis_ibfk_2` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudos` (`id`) ON DELETE CASCADE;

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
-- Restrições para tabelas `caderno_anotacoes`
--
ALTER TABLE `caderno_anotacoes`
  ADD CONSTRAINT `caderno_anotacoes_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `conteudos`
--
ALTER TABLE `conteudos`
  ADD CONSTRAINT `conteudos_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conteudos_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `grupos_conteudos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `grupos_conteudos`
--
ALTER TABLE `grupos_conteudos`
  ADD CONSTRAINT `grupos_conteudos_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `logs_acesso`
--
ALTER TABLE `logs_acesso`
  ADD CONSTRAINT `fk_usuario_log` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `presenca_aula`
--
ALTER TABLE `presenca_aula`
  ADD CONSTRAINT `presenca_aula_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presenca_aula_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `turmas_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_responsavel` FOREIGN KEY (`responsavel_financeiro_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuarios_anexos`
--
ALTER TABLE `usuarios_anexos`
  ADD CONSTRAINT `fk_anexos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


    