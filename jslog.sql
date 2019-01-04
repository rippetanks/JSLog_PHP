-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Gen 04, 2019 alle 21:25
-- Versione del server: 10.1.37-MariaDB
-- Versione PHP: 7.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jslog`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `entity`
--

CREATE TABLE `entity` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(64) COLLATE utf8_bin NOT NULL,
  `log_key` char(32) COLLATE utf8_bin NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Struttura della tabella `log`
--

CREATE TABLE `log` (
  `id` bigint(20) NOT NULL,
  `entity` smallint(5) UNSIGNED DEFAULT NULL,
  `storage_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `record_date` datetime NOT NULL,
  `UserAgent` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `Host` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `message` varchar(1024) COLLATE utf8_bin NOT NULL,
  `http_code` smallint(6) DEFAULT NULL,
  `level` enum('FATAL','ERROR','WARN','INFO','DEBUG','TRACE','ASSERT') COLLATE utf8_bin NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Struttura della tabella `profile`
--

CREATE TABLE `profile` (
  `id` bigint(20) NOT NULL,
  `entity` smallint(5) UNSIGNED DEFAULT NULL,
  `profile_time` smallint(5) UNSIGNED NOT NULL,
  `descr` varchar(32) COLLATE utf8_bin NOT NULL,
  `date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=Aria DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `entity`
--
ALTER TABLE `entity`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `log_entity_fk` (`entity`);

--
-- Indici per le tabelle `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `entity`
--
ALTER TABLE `entity`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `log`
--
ALTER TABLE `log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `profile`
--
ALTER TABLE `profile`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
