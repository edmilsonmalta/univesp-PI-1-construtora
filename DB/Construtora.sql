-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 14/05/2025 às 18:36
-- Versão do servidor: 9.1.0
-- Versão do PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `construtora`
--
CREATE DATABASE IF NOT EXISTS `construtora` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `construtora`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `db_agendamento`
--

DROP TABLE IF EXISTS `db_agendamento`;
CREATE TABLE IF NOT EXISTS `db_agendamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `requisicao_id` int NOT NULL,
  `data_agendamento` datetime NOT NULL,
  `data_inicio_obra` date NOT NULL,
  `data_fim_obra` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `responsavel` varchar(100) NOT NULL,
  `observacoes` text,
  `status` varchar(20) DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  KEY `requisicao_id` (`requisicao_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `db_cliente`
--

DROP TABLE IF EXISTS `db_cliente`;
CREATE TABLE IF NOT EXISTS `db_cliente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('PF','PJ') NOT NULL,
  `cpf_cnpj` varchar(20) NOT NULL,
  `nome_razao_social` varchar(100) NOT NULL,
  `nome_fantasia` varchar(100) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `complemento` varchar(50) DEFAULT NULL,
  `bairro` varchar(50) DEFAULT NULL,
  `cidade` varchar(50) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `responsavel` varchar(100) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `db_cliente`
--

INSERT INTO `db_cliente` (`id`, `tipo`, `cpf_cnpj`, `nome_razao_social`, `nome_fantasia`, `data_nascimento`, `telefone`, `email`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `responsavel`, `data_cadastro`) VALUES
(1, 'PF', '12345678910', 'Cliente 1', '', '2000-01-01', '1699999999', 'cliente1@cliente1.com.br', 'Cliente ', '123', '', 'Cliente', 'Franca', 'SP', '144000', '', '2025-05-14 14:07:11'),
(2, 'PF', '12345678911', 'Cliente 2', NULL, '0000-00-00', '1699999999', 'cliente2@cliente2.com.br', 'cliente 2', '123', NULL, 'Cliente 2', 'Franca', 'SP', '144000', 'Cliente', '2025-05-14 14:10:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `db_colaboradores`
--

DROP TABLE IF EXISTS `db_colaboradores`;
CREATE TABLE IF NOT EXISTS `db_colaboradores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `funcao` varchar(50) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `db_colaboradores`
--

INSERT INTO `db_colaboradores` (`id`, `nome`, `cpf`, `funcao`, `data_cadastro`) VALUES
(1, 'Colaborador 1', '00000000191', 'Pedreiro', '2025-05-14 18:29:06'),
(2, 'Colaborador 2', '00000000272', 'Pintor', '2025-05-14 18:32:01'),
(3, 'Colaborador 3', '00000000353', 'Mestre de Obras', '2025-05-14 18:32:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `db_funcoes`
--

DROP TABLE IF EXISTS `db_funcoes`;
CREATE TABLE IF NOT EXISTS `db_funcoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_funcao` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_funcao` (`nome_funcao`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `db_funcoes`
--

INSERT INTO `db_funcoes` (`id`, `nome_funcao`) VALUES
(1, 'Pedreiro'),
(2, 'Encanador'),
(3, 'Eletricista'),
(4, 'Carpinteiro'),
(5, 'Pintor'),
(6, 'Servente'),
(7, 'Mestre de Obras'),
(8, 'Arquiteto'),
(9, 'Engenheiro Civil');

-- --------------------------------------------------------

--
-- Estrutura para tabela `db_requisicoes_obra`
--

DROP TABLE IF EXISTS `db_requisicoes_obra`;
CREATE TABLE IF NOT EXISTS `db_requisicoes_obra` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `descricao` text NOT NULL,
  `prioridade` enum('baixa','media','alta','urgente') NOT NULL,
  `data_prevista` date DEFAULT NULL,
  `observacoes` text,
  `data_criacao` datetime NOT NULL,
  `status` enum('Pendente','Em análise','Aprovada','Em andamento','Concluída','Cancelada') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `db_usuarios`
--

DROP TABLE IF EXISTS `db_usuarios`;
CREATE TABLE IF NOT EXISTS `db_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `login` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `db_usuarios`
--

INSERT INTO `db_usuarios` (`id`, `cliente_id`, `login`, `senha_hash`, `token_recuperacao`, `data_criacao`) VALUES
(1, 1, '12345678910', '$2y$10$E4hv9PitJFpfVEYVZ80iLe8oKPKZTonvPiDxXpkd7P1xYnuyABwsm', NULL, '2025-05-14 14:08:22'),
(2, 2, '12345678911', '$2y$10$N112GLRASr20mi5R8ZVcRe.Hz8WfWwsdNoSHPu7L0LpjC0ZUdg7k.', NULL, '2025-05-14 18:35:13');
