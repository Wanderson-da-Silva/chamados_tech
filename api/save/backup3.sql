-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: chamado_prev
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `chamado`
--

DROP TABLE IF EXISTS `chamado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chamado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `maquina_id` int(11) NOT NULL,
  `usuario_abertura_id` int(11) NOT NULL,
  `usuario_tecnico_id` int(11) DEFAULT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `prioridade` enum('baixa','media','alta','critica') DEFAULT 'media',
  `status` enum('pendente','em_andamento','aguardando_peca','pausado','concluido','cancelado') DEFAULT 'pendente',
  `data_abertura` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_inicio_atendimento` timestamp NULL DEFAULT NULL,
  `data_conclusao` timestamp NULL DEFAULT NULL,
  `prazo_resolucao` timestamp NULL DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `solucao` text DEFAULT NULL,
  `tempo_gasto` int(11) DEFAULT NULL,
  `custo_servico` decimal(10,2) DEFAULT NULL,
  `custo_pecas` decimal(10,2) DEFAULT NULL,
  `nota_atendimento` int(11) DEFAULT NULL CHECK (`nota_atendimento` between 1 and 5),
  `comentario_avaliacao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuario_ultima_atualizacao` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  KEY `usuario_abertura_id` (`usuario_abertura_id`),
  KEY `idx_numero` (`numero`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_maquina` (`maquina_id`),
  KEY `idx_status` (`status`),
  KEY `idx_prioridade` (`prioridade`),
  KEY `idx_data_abertura` (`data_abertura`),
  KEY `idx_tecnico` (`usuario_tecnico_id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_prazo` (`prazo_resolucao`),
  KEY `idx_chamado_loja_status` (`loja_id`,`status`),
  KEY `idx_chamado_data_status` (`data_abertura`,`status`),
  CONSTRAINT `chamado_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `loja` (`id`),
  CONSTRAINT `chamado_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquina` (`id`),
  CONSTRAINT `chamado_ibfk_3` FOREIGN KEY (`usuario_abertura_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `chamado_ibfk_4` FOREIGN KEY (`usuario_tecnico_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chamado`
--

LOCK TABLES `chamado` WRITE;
/*!40000 ALTER TABLE `chamado` DISABLE KEYS */;
INSERT INTO `chamado` VALUES (2,'CH-2025-002',1,1,2,3,'','derfe','energia','baixa','concluido','2025-12-16 23:34:41','2026-01-09 15:19:01','2026-01-29 15:49:48',NULL,NULL,'menino',NULL,NULL,NULL,NULL,NULL,'2025-12-16 23:34:41','2026-01-29 15:49:48',NULL),(3,'CH-2026-001',1,1,2,3,'','tretreter','energia','baixa','concluido','2026-01-09 13:16:13','2026-01-09 14:54:23','2026-01-29 13:36:43',NULL,NULL,'ertete',NULL,NULL,NULL,NULL,NULL,'2026-01-09 13:16:13','2026-01-29 13:36:43',NULL),(4,'CH-2026-002',3,4,2,3,'','ertret','colisao','media','concluido','2026-01-09 15:21:09','2026-01-09 15:39:15','2026-01-12 19:54:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-09 15:21:09','2026-01-12 19:54:56',NULL),(5,'CH-2026-003',2,3,2,3,'','teste ftos','energia','baixa','concluido','2026-01-21 17:20:50','2026-01-29 14:07:30','2026-01-29 14:07:44',NULL,NULL,'ertert',NULL,NULL,NULL,NULL,NULL,'2026-01-21 17:20:50','2026-01-29 14:07:44',NULL),(6,'CH-2026-004',2,3,2,4,'','tretret','vazamento','alta','concluido','2026-01-23 02:19:50','2026-01-27 21:09:08','2026-01-29 13:17:54',NULL,NULL,'etertet',NULL,NULL,NULL,NULL,NULL,'2026-01-23 02:19:50','2026-01-29 13:17:54',NULL),(7,'CH-2026-005',3,4,2,3,'','tretret','vazamento','alta','concluido','2026-01-23 02:30:33','2026-01-29 13:40:31','2026-01-29 13:41:02',NULL,NULL,'fghfh',NULL,NULL,NULL,NULL,NULL,'2026-01-23 02:30:33','2026-01-29 13:41:02',NULL),(8,'CH-2026-006',3,4,2,4,'','TESTE','energia','baixa','concluido','2026-01-26 19:58:48','2026-01-29 13:43:24','2026-01-29 14:05:12',NULL,NULL,'fhfghfgh',NULL,NULL,NULL,NULL,NULL,'2026-01-26 19:58:48','2026-01-29 14:05:12',NULL),(9,'CH-2026-007',3,5,2,4,'','NOVO CHAMADO','energia','media','cancelado','2026-01-26 20:02:42','2026-01-27 20:41:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-26 20:02:42','2026-01-27 20:53:55',NULL),(10,'CH-2026-008',2,3,2,4,'','bola','roda','alta','concluido','2026-01-29 13:22:35','2026-01-29 13:23:09','2026-01-29 13:23:48',NULL,NULL,'fght',NULL,NULL,NULL,NULL,NULL,'2026-01-29 13:22:35','2026-01-29 13:23:48',NULL),(11,'CH-2026-009',2,3,2,3,'','ertrt','colisao','media','concluido','2026-01-29 16:34:02','2026-01-29 16:34:09','2026-01-29 16:34:42',NULL,NULL,'tree',NULL,NULL,NULL,NULL,NULL,'2026-01-29 16:34:02','2026-01-29 16:34:42',NULL),(12,'CH-2026-010',1,1,2,NULL,'','dgdfg','energia','baixa','cancelado','2026-01-29 21:31:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-29 21:31:15','2026-01-30 23:32:47',2);
/*!40000 ALTER TABLE `chamado` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_gerar_numero_chamado 
    BEFORE INSERT ON chamado
    FOR EACH ROW
BEGIN
    IF NEW.numero IS NULL OR NEW.numero = '' THEN
        SET NEW.numero = CONCAT('CH-', YEAR(NOW()), '-', LPAD(
            (SELECT COALESCE(MAX(CAST(SUBSTRING(numero, -3) AS UNSIGNED)), 0) + 1
             FROM chamado 
             WHERE numero LIKE CONCAT('CH-', YEAR(NOW()), '-%')), 
            3, '0'
        ));
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_chamado_historico 
    AFTER UPDATE ON chamado
    FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO chamado_historico (
            chamado_id, 
            usuario_id, 
            status_anterior, 
            status_novo, 
            comentario
        ) VALUES (
            OLD.id,
            NEW.usuario_ultima_atualizacao,
            OLD.status,
            NEW.status,
            'Status alterado automaticamente'
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `chamado_anexo`
--

DROP TABLE IF EXISTS `chamado_anexo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chamado_anexo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chamado_id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `tipo_arquivo` varchar(10) NOT NULL,
  `tamanho_bytes` bigint(20) NOT NULL,
  `caminho_arquivo` varchar(500) NOT NULL,
  `tipo_anexo` enum('foto','video','documento') NOT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chamado` (`chamado_id`),
  KEY `idx_tipo` (`tipo_anexo`),
  KEY `idx_data_upload` (`data_upload`),
  CONSTRAINT `chamado_anexo_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamado` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chamado_anexo`
--

LOCK TABLES `chamado_anexo` WRITE;
/*!40000 ALTER TABLE `chamado_anexo` DISABLE KEYS */;
INSERT INTO `chamado_anexo` VALUES (1,6,'6_2026-01-23_03-19-50_6972dac6a364e_1769134790_0.jpg','VW-Fusca.jpg','image/jpeg',265012,'fotos/6_2026-01-23_03-19-50_6972dac6a364e_1769134790_0.jpg','foto',NULL,'2026-01-23 02:19:50'),(3,7,'7_2026-01-22_23-30-33_6972dd49288cc_1769135433_0.jpg','VW-Fusca.jpg','image/jpeg',265012,'fotos/7_2026-01-22_23-30-33_6972dd49288cc_1769135433_0.jpg','foto',NULL,'2026-01-23 02:30:33'),(4,7,'7_2026-01-22_23-30-33_6972dd4929187_1769135433_1.png','fel.png','image/png',129254,'fotos/7_2026-01-22_23-30-33_6972dd4929187_1769135433_1.png','foto',NULL,'2026-01-23 02:30:33'),(5,8,'8_2026-01-26_16-58-48_6977c77847149_1769457528_0.png','fel.png','image/png',129254,'fotos/8_2026-01-26_16-58-48_6977c77847149_1769457528_0.png','foto',NULL,'2026-01-26 19:58:48'),(6,9,'9_2026-01-26_17-02-42_6977c86228c4f_1769457762_0.png','fel.png','image/png',129254,'fotos/9_2026-01-26_17-02-42_6977c86228c4f_1769457762_0.png','foto',NULL,'2026-01-26 20:02:42'),(7,10,'10_2026-01-29_10-22-35_697b5f1b807c2_1769692955_0.jpg','bola.jpg','image/jpeg',3106,'fotos/10_2026-01-29_10-22-35_697b5f1b807c2_1769692955_0.jpg','foto',NULL,'2026-01-29 13:22:35'),(8,2,'2_2026-01-29_12-10-21_697b785dbc552_1769699421_0.jpg','fio.jpg','image/jpeg',2535,'fotos/2_2026-01-29_12-10-21_697b785dbc552_1769699421_0.jpg','foto',NULL,'2026-01-29 15:10:21'),(9,2,'2_2026-01-29_12-11-48_697b78b414f77_1769699508_0.jpg','fio2.jpg','image/jpeg',4283,'fotos/2_2026-01-29_12-11-48_697b78b414f77_1769699508_0.jpg','foto',NULL,'2026-01-29 15:11:48'),(10,2,'2_2026-01-29_12-31-55_697b7d6bbbbf1_1769700715_0.png','boy.png','image/png',7130,'fotos/2_2026-01-29_12-31-55_697b7d6bbbbf1_1769700715_0.png','foto',NULL,'2026-01-29 15:31:55'),(13,2,'2_2026-01-29_12-49-48_697b819c84078_1769701788_0.png','boy.png','image/png',7130,'fotos/2_2026-01-29_12-49-48_697b819c84078_1769701788_0.png','foto',NULL,'2026-01-29 15:49:48'),(14,2,'2_2026-01-29_12-49-48_697b819c84c28_1769701788_1.jpg','tree.jpg','image/jpeg',4087,'fotos/2_2026-01-29_12-49-48_697b819c84c28_1769701788_1.jpg','foto',NULL,'2026-01-29 15:49:48'),(15,11,'11_2026-01-29_13-34-02_697b8bfa1900b_1769704442_0.jpg','bola.jpg','image/jpeg',3106,'fotos/11_2026-01-29_13-34-02_697b8bfa1900b_1769704442_0.jpg','foto',NULL,'2026-01-29 16:34:02'),(16,11,'11_2026-01-29_13-34-42_697b8c22a5937_1769704482_0.jpg','tree.jpg','image/jpeg',4087,'fotos/11_2026-01-29_13-34-42_697b8c22a5937_1769704482_0.jpg','foto',NULL,'2026-01-29 16:34:42');
/*!40000 ALTER TABLE `chamado_anexo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chamado_historico`
--

DROP TABLE IF EXISTS `chamado_historico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chamado_historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chamado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status_anterior` varchar(20) DEFAULT NULL,
  `status_novo` varchar(20) NOT NULL,
  `comentario` text DEFAULT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_chamado` (`chamado_id`),
  KEY `idx_data` (`data_alteracao`),
  CONSTRAINT `chamado_historico_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamado` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chamado_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chamado_historico`
--

LOCK TABLES `chamado_historico` WRITE;
/*!40000 ALTER TABLE `chamado_historico` DISABLE KEYS */;
INSERT INTO `chamado_historico` VALUES (2,3,3,'pendente','em_andamento','Status alterado automaticamente','2026-01-09 14:54:23'),(3,2,3,'pendente','em_andamento','Status alterado automaticamente','2026-01-09 15:19:01'),(5,4,3,'pendente','em_andamento','Status alterado automaticamente','2026-01-09 15:39:15'),(6,4,3,'em_andamento','concluido','Status alterado automaticamente','2026-01-12 19:54:56'),(7,9,4,'pendente','em_andamento','Status alterado automaticamente','2026-01-27 20:41:24'),(8,9,4,'em_andamento','pausado','Status alterado automaticamente','2026-01-27 20:53:35'),(9,9,4,'pausado','cancelado','Status alterado automaticamente','2026-01-27 20:53:55'),(10,3,3,'em_andamento','aguardando_peca','Status alterado automaticamente','2026-01-27 21:08:44'),(11,6,4,'pendente','em_andamento','Status alterado automaticamente','2026-01-27 21:09:08'),(12,6,4,'em_andamento','pausado','Status alterado automaticamente','2026-01-27 21:09:17'),(13,6,4,'pausado','concluido','Status alterado automaticamente','2026-01-29 13:17:54'),(14,10,4,'pendente','em_andamento','Status alterado automaticamente','2026-01-29 13:23:09'),(15,10,4,'em_andamento','concluido','Status alterado automaticamente','2026-01-29 13:23:48'),(16,3,3,'aguardando_peca','concluido','Status alterado automaticamente','2026-01-29 13:36:43'),(17,7,3,'pendente','em_andamento','Status alterado automaticamente','2026-01-29 13:40:31'),(18,7,3,'em_andamento','concluido','Status alterado automaticamente','2026-01-29 13:41:02'),(19,8,4,'pendente','em_andamento','Status alterado automaticamente','2026-01-29 13:43:24'),(20,8,4,'em_andamento','concluido','Status alterado automaticamente','2026-01-29 14:05:12'),(21,5,3,'pendente','em_andamento','Status alterado automaticamente','2026-01-29 14:07:30'),(22,5,3,'em_andamento','concluido','Status alterado automaticamente','2026-01-29 14:07:44'),(24,2,3,'em_andamento','concluido','Status alterado automaticamente','2026-01-29 15:49:48'),(25,11,3,'pendente','em_andamento','Status alterado automaticamente','2026-01-29 16:34:09'),(26,11,3,'em_andamento','concluido','Status alterado automaticamente','2026-01-29 16:34:42'),(27,12,2,'pendente','cancelado','Status alterado automaticamente','2026-01-30 23:32:47');
/*!40000 ALTER TABLE `chamado_historico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loja`
--

DROP TABLE IF EXISTS `loja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `endereco` text DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `ativa` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_ativa` (`ativa`),
  KEY `idx_cidade` (`cidade`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loja`
--

LOCK TABLES `loja` WRITE;
/*!40000 ALTER TABLE `loja` DISABLE KEYS */;
INSERT INTO `loja` VALUES (1,'Filial Centro','CENTRO',NULL,'São Paulo','SP',NULL,NULL,NULL,NULL,1,'2025-12-02 00:55:54','2025-12-02 00:55:54'),(2,'Filial Norte','NORTE',NULL,'São Paulo','SP',NULL,NULL,NULL,NULL,1,'2025-12-02 00:55:54','2025-12-02 00:55:54'),(3,'Filial Sul','SUL',NULL,'São Paulo','SP',NULL,NULL,NULL,NULL,1,'2025-12-02 00:55:54','2025-12-02 00:55:54'),(4,'Filial Oeste','OESTE',NULL,'São Paulo','SP',NULL,NULL,NULL,NULL,1,'2025-12-02 00:55:54','2025-12-02 00:55:54');
/*!40000 ALTER TABLE `loja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maquina`
--

DROP TABLE IF EXISTS `maquina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maquina` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) NOT NULL,
  `patrimonio` varchar(50) NOT NULL,
  `numero_serie` varchar(100) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `tipo_equipamento` varchar(50) DEFAULT NULL,
  `data_aquisicao` date DEFAULT NULL,
  `valor_aquisicao` decimal(10,2) DEFAULT NULL,
  `status_operacional` enum('ativo','inativo','manutencao','descartado') DEFAULT 'ativo',
  `localizacao` varchar(200) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `periodicidade_preventiva` int(11) DEFAULT NULL,
  `data_ultima_preventiva` date DEFAULT NULL,
  `data_proxima_preventiva` date DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `patrimonio` (`patrimonio`),
  UNIQUE KEY `numero_serie` (`numero_serie`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_patrimonio` (`patrimonio`),
  KEY `idx_serie` (`numero_serie`),
  KEY `idx_status` (`status_operacional`),
  KEY `idx_proxima_preventiva` (`data_proxima_preventiva`),
  KEY `idx_tipo` (`tipo_equipamento`),
  KEY `idx_maquina_loja_status` (`loja_id`,`status_operacional`),
  CONSTRAINT `maquina_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `loja` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maquina`
--

LOCK TABLES `maquina` WRITE;
/*!40000 ALTER TABLE `maquina` DISABLE KEYS */;
INSERT INTO `maquina` VALUES (1,1,'MAQ-001','SER123001','HP LaserJet Pro','HP','Impressora',NULL,NULL,'ativo',NULL,NULL,90,NULL,'2025-12-03','2025-12-02 00:55:54','2025-12-02 00:55:54'),(2,1,'MAQ-015','SER123015','Dell OptiPlex','Dell','Computador',NULL,NULL,'ativo',NULL,NULL,180,NULL,'2025-12-06','2025-12-02 00:55:54','2025-12-02 00:55:54'),(3,2,'MAQ-045','SER123045','Canon ImageRunner','Canon','Impressora',NULL,NULL,'ativo',NULL,NULL,90,NULL,NULL,'2025-12-02 00:55:54','2026-01-29 21:27:08'),(4,3,'MAQ-123','SER123123','Lenovo ThinkCentre','Lenovo','Computador',NULL,NULL,'ativo',NULL,NULL,180,NULL,'2025-12-05','2025-12-02 00:55:54','2025-12-02 00:55:54'),(5,3,'MAQ-598','YTRE987654','LASER','HP','Tablet','2026-01-26',12000.00,'ativo','','',0,NULL,NULL,'2026-01-26 19:53:24','2026-01-26 20:01:13');
/*!40000 ALTER TABLE `maquina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preventiva`
--

DROP TABLE IF EXISTS `preventiva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preventiva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maquina_id` int(11) NOT NULL,
  `usuario_tecnico_id` int(11) DEFAULT NULL,
  `tipo` varchar(50) NOT NULL,
  `data_programada` date NOT NULL,
  `data_realizada` date DEFAULT NULL,
  `status` enum('programada','em_andamento','concluida','cancelada') DEFAULT 'programada',
  `checklist` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `pecas_substituidas` text DEFAULT NULL,
  `custos` decimal(10,2) DEFAULT 0.00,
  `tempo_execucao` int(11) DEFAULT NULL,
  `proxima_data` date DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maquina` (`maquina_id`),
  KEY `idx_data_programada` (`data_programada`),
  KEY `idx_status` (`status`),
  KEY `idx_tecnico` (`usuario_tecnico_id`),
  KEY `idx_preventiva_data_status` (`data_programada`,`status`),
  CONSTRAINT `preventiva_ibfk_1` FOREIGN KEY (`maquina_id`) REFERENCES `maquina` (`id`),
  CONSTRAINT `preventiva_ibfk_2` FOREIGN KEY (`usuario_tecnico_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preventiva`
--

LOCK TABLES `preventiva` WRITE;
/*!40000 ALTER TABLE `preventiva` DISABLE KEYS */;
INSERT INTO `preventiva` VALUES (1,1,3,'Corretiva','2025-12-03',NULL,'programada',NULL,NULL,NULL,0.00,NULL,NULL,'2025-12-02 01:10:22','2025-12-02 01:10:22'),(2,3,3,'Corretiva','2025-12-12',NULL,'concluida',NULL,NULL,NULL,0.00,NULL,NULL,'2025-12-02 01:11:16','2026-01-29 21:27:08'),(4,4,3,'Corretiva','2026-02-14',NULL,'programada',NULL,NULL,NULL,0.00,NULL,NULL,'2025-12-02 01:16:21','2025-12-02 01:16:21'),(5,2,4,'corretiva','2026-01-23',NULL,'programada',NULL,'ertet',NULL,0.00,NULL,NULL,'2026-01-29 21:17:45','2026-01-29 21:17:45');
/*!40000 ALTER TABLE `preventiva` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_atualizar_proxima_preventiva 
    AFTER UPDATE ON preventiva
    FOR EACH ROW
BEGIN
    IF NEW.status = 'concluida' AND OLD.status != 'concluida' THEN
        UPDATE maquina 
        SET 
            data_ultima_preventiva = NEW.data_realizada,
            data_proxima_preventiva = DATE_ADD(NEW.data_realizada, INTERVAL periodicidade_preventiva DAY)
        WHERE id = NEW.maquina_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `nome_completo` varchar(150) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `perfil` enum('admin','tecnico','operador','visualizador') DEFAULT 'operador',
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_perfil` (`perfil`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'admin','admin@techservice.com','$2y$10$exemplo_hash_senha','Administrador Sistema',NULL,'admin',1,NULL,'2025-12-02 00:55:54','2025-12-02 00:55:54'),(2,'trevo','trevo@trevo.com.br','$2y$10$3USn4Jh7vZHWd0GNSvrGFubZIIikF51Cavv7pyO4kvc/KtEjxdTAu','trevo','61994949494','admin',1,'2026-01-30 23:27:55','2025-12-02 01:05:20','2026-01-30 23:27:55'),(3,'vento','gunter@vento.com.br','$2y$10$bdIurFauNI5bRQsFUdKxC.kZmASuc39oECi8FFOsKHU7c/2SArv7.','gunter','996666555','tecnico',1,NULL,'2025-12-02 01:09:44','2025-12-02 01:09:44'),(4,'tecnico','tecnico@tecnico.com.br','$2y$10$fmRApB/bHToTuK27E.fknunerRfNvFjYF74rUx45n8.Eu6atI1wzi','tecnico','','tecnico',1,NULL,'2026-01-26 19:14:25','2026-01-26 19:14:25');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `vw_chamados_completo`
--

DROP TABLE IF EXISTS `vw_chamados_completo`;
/*!50001 DROP VIEW IF EXISTS `vw_chamados_completo`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_chamados_completo` AS SELECT
 1 AS `id`,
  1 AS `numero`,
  1 AS `titulo`,
  1 AS `descricao`,
  1 AS `status`,
  1 AS `prioridade`,
  1 AS `data_abertura`,
  1 AS `data_conclusao`,
  1 AS `custo_servico`,
  1 AS `custo_pecas`,
  1 AS `nota_atendimento`,
  1 AS `comentario_avaliacao`,
  1 AS `loja_nome`,
  1 AS `loja_codigo`,
  1 AS `patrimonio`,
  1 AS `numero_serie`,
  1 AS `modelo`,
  1 AS `valor_aquisicao`,
  1 AS `usuario_abertura`,
  1 AS `tecnico_responsavel`,
  1 AS `horas_em_aberto` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_dashboard_chamados`
--

DROP TABLE IF EXISTS `vw_dashboard_chamados`;
/*!50001 DROP VIEW IF EXISTS `vw_dashboard_chamados`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_dashboard_chamados` AS SELECT
 1 AS `loja_nome`,
  1 AS `pendentes`,
  1 AS `em_andamento`,
  1 AS `concluidos`,
  1 AS `total_chamados` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_dashboard_chamados_por_tipo`
--

DROP TABLE IF EXISTS `vw_dashboard_chamados_por_tipo`;
/*!50001 DROP VIEW IF EXISTS `vw_dashboard_chamados_por_tipo`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_dashboard_chamados_por_tipo` AS SELECT
 1 AS `pendente`,
  1 AS `em_andamento`,
  1 AS `concluido`,
  1 AS `aguardando_peca`,
  1 AS `pausado`,
  1 AS `cancelado`,
  1 AS `total` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_preventivas_proximas`
--

DROP TABLE IF EXISTS `vw_preventivas_proximas`;
/*!50001 DROP VIEW IF EXISTS `vw_preventivas_proximas`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_preventivas_proximas` AS SELECT
 1 AS `loja_nome`,
  1 AS `patrimonio`,
  1 AS `numero_serie`,
  1 AS `modelo`,
  1 AS `data_proxima_preventiva`,
  1 AS `dias_restantes`,
  1 AS `status_preventiva` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `vw_chamados_completo`
--

/*!50001 DROP VIEW IF EXISTS `vw_chamados_completo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_chamados_completo` AS select `c`.`id` AS `id`,`c`.`numero` AS `numero`,`c`.`titulo` AS `titulo`,`c`.`descricao` AS `descricao`,`c`.`status` AS `status`,`c`.`prioridade` AS `prioridade`,`c`.`data_abertura` AS `data_abertura`,`c`.`data_conclusao` AS `data_conclusao`,`c`.`custo_servico` AS `custo_servico`,`c`.`custo_pecas` AS `custo_pecas`,`c`.`nota_atendimento` AS `nota_atendimento`,`c`.`comentario_avaliacao` AS `comentario_avaliacao`,`l`.`nome` AS `loja_nome`,`l`.`codigo` AS `loja_codigo`,`m`.`patrimonio` AS `patrimonio`,`m`.`numero_serie` AS `numero_serie`,`m`.`modelo` AS `modelo`,`m`.`valor_aquisicao` AS `valor_aquisicao`,`ua`.`nome_completo` AS `usuario_abertura`,`ut`.`nome_completo` AS `tecnico_responsavel`,case when `c`.`status` = 'concluido' then timestampdiff(HOUR,`c`.`data_abertura`,`c`.`data_conclusao`) else timestampdiff(HOUR,`c`.`data_abertura`,current_timestamp()) end AS `horas_em_aberto` from ((((`chamado` `c` join `loja` `l` on(`c`.`loja_id` = `l`.`id`)) join `maquina` `m` on(`c`.`maquina_id` = `m`.`id`)) join `usuario` `ua` on(`c`.`usuario_abertura_id` = `ua`.`id`)) left join `usuario` `ut` on(`c`.`usuario_tecnico_id` = `ut`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_dashboard_chamados`
--

/*!50001 DROP VIEW IF EXISTS `vw_dashboard_chamados`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_dashboard_chamados` AS select `l`.`nome` AS `loja_nome`,count(case when `c`.`status` = 'pendente' then 1 end) AS `pendentes`,count(case when `c`.`status` = 'em_andamento' then 1 end) AS `em_andamento`,count(case when `c`.`status` = 'concluido' then 1 end) AS `concluidos`,count(0) AS `total_chamados` from (`loja` `l` left join `chamado` `c` on(`l`.`id` = `c`.`loja_id` and `c`.`data_abertura` >= current_timestamp() - interval 30 day)) where `l`.`ativa` = 1 group by `l`.`id`,`l`.`nome` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_dashboard_chamados_por_tipo`
--

/*!50001 DROP VIEW IF EXISTS `vw_dashboard_chamados_por_tipo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_dashboard_chamados_por_tipo` AS select sum(case when `c`.`status` = 'pendente' then 1 else 0 end) AS `pendente`,sum(case when `c`.`status` = 'em_andamento' then 1 else 0 end) AS `em_andamento`,sum(case when `c`.`status` = 'concluido' then 1 else 0 end) AS `concluido`,sum(case when `c`.`status` = 'aguardando_peca' then 1 else 0 end) AS `aguardando_peca`,sum(case when `c`.`status` = 'pausado' then 1 else 0 end) AS `pausado`,sum(case when `c`.`status` = 'cancelado' then 1 else 0 end) AS `cancelado`,count(0) AS `total` from `chamado` `c` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_preventivas_proximas`
--

/*!50001 DROP VIEW IF EXISTS `vw_preventivas_proximas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_preventivas_proximas` AS select `l`.`nome` AS `loja_nome`,`m`.`patrimonio` AS `patrimonio`,`m`.`numero_serie` AS `numero_serie`,`m`.`modelo` AS `modelo`,`m`.`data_proxima_preventiva` AS `data_proxima_preventiva`,to_days(`m`.`data_proxima_preventiva`) - to_days(curdate()) AS `dias_restantes`,case when to_days(`m`.`data_proxima_preventiva`) - to_days(curdate()) <= 0 then 'Vencida' when to_days(`m`.`data_proxima_preventiva`) - to_days(curdate()) <= 7 then 'Urgente' when to_days(`m`.`data_proxima_preventiva`) - to_days(curdate()) <= 15 then 'Próxima' else 'Normal' end AS `status_preventiva` from (`maquina` `m` join `loja` `l` on(`m`.`loja_id` = `l`.`id`)) where `m`.`status_operacional` = 'ativo' and `m`.`data_proxima_preventiva` is not null and `l`.`ativa` = 1 order by `m`.`data_proxima_preventiva` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-30 20:34:43
