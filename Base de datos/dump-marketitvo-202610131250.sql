/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.0.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: marketitvo
-- ------------------------------------------------------
-- Server version	12.0.2-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `calificacion_producto`
--

DROP TABLE IF EXISTS `calificacion_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calificacion_producto` (
  `id_calificacion_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `puntuacion` tinyint(4) NOT NULL CHECK (`puntuacion` between 1 and 5),
  `comentario` varchar(255) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_calificacion_producto`),
  UNIQUE KEY `uq_califprod_unica` (`id_usuario`,`id_producto`),
  KEY `fk_califprod_producto` (`id_producto`),
  CONSTRAINT `fk_califprod_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_califprod_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificacion_producto`
--

LOCK TABLES `calificacion_producto` WRITE;
/*!40000 ALTER TABLE `calificacion_producto` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `calificacion_producto` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `calificacion_vendedor`
--

DROP TABLE IF EXISTS `calificacion_vendedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calificacion_vendedor` (
  `id_calificacion_vendedor` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_vendedor` int(11) NOT NULL,
  `puntuacion` tinyint(4) NOT NULL CHECK (`puntuacion` between 1 and 5),
  `comentario` varchar(255) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_calificacion_vendedor`),
  UNIQUE KEY `uq_califvend_unica` (`id_usuario`,`id_vendedor`),
  KEY `fk_califvend_vendedor` (`id_vendedor`),
  CONSTRAINT `fk_califvend_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_califvend_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `vendedores` (`id_vendedor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificacion_vendedor`
--

LOCK TABLES `calificacion_vendedor` WRITE;
/*!40000 ALTER TABLE `calificacion_vendedor` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `calificacion_vendedor` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_categoria` varchar(50) NOT NULL,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `tipo_categoria` (`tipo_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `categorias` VALUES
(7,'Accesorios tecnológicos'),
(2,'Antojitos y snacks'),
(4,'Bebidas'),
(1,'Comida preparada'),
(3,'Dulces y golosinas'),
(11,'Flores'),
(6,'Frutas y verduras'),
(10,'Manualidades'),
(14,'Otros'),
(12,'Perfumes'),
(5,'Postres y panadería'),
(9,'Servicios'),
(8,'Tecnología'),
(13,'Tópers');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `condicion_producto`
--

DROP TABLE IF EXISTS `condicion_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `condicion_producto` (
  `id_condicion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_condicion` varchar(30) NOT NULL,
  PRIMARY KEY (`id_condicion`),
  UNIQUE KEY `nombre_condicion` (`nombre_condicion`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `condicion_producto`
--

LOCK TABLES `condicion_producto` WRITE;
/*!40000 ALTER TABLE `condicion_producto` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `condicion_producto` VALUES
(1,'Nuevo'),
(4,'Usado aceptable'),
(3,'Usado buen estado'),
(2,'Usado como nuevo');
/*!40000 ALTER TABLE `condicion_producto` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `estado_producto`
--

DROP TABLE IF EXISTS `estado_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_producto` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado` varchar(20) NOT NULL,
  PRIMARY KEY (`id_estado`),
  UNIQUE KEY `nombre_estado` (`nombre_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_producto`
--

LOCK TABLES `estado_producto` WRITE;
/*!40000 ALTER TABLE `estado_producto` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `estado_producto` VALUES
(2,'Agotado'),
(1,'Disponible'),
(3,'Pendiente');
/*!40000 ALTER TABLE `estado_producto` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `estado_ubicacion`
--

DROP TABLE IF EXISTS `estado_ubicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_ubicacion` (
  `id_estado_ubicacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_estado` varchar(20) NOT NULL,
  PRIMARY KEY (`id_estado_ubicacion`),
  UNIQUE KEY `nombre_estado` (`nombre_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_ubicacion`
--

LOCK TABLES `estado_ubicacion` WRITE;
/*!40000 ALTER TABLE `estado_ubicacion` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `estado_ubicacion` VALUES
(1,'Activo'),
(2,'Inactivo'),
(3,'Pendiente');
/*!40000 ALTER TABLE `estado_ubicacion` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_vendedor` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_condicion` int(11) NOT NULL,
  `id_estado_ubicacion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion_producto` varchar(255) DEFAULT NULL,
  `precio` decimal(8,2) NOT NULL,
  `imagen_producto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `fk_producto_vendedor` (`id_vendedor`),
  KEY `fk_producto_categoria` (`id_categoria`),
  KEY `fk_producto_estado` (`id_estado`),
  KEY `fk_producto_condicion` (`id_condicion`),
  KEY `fk_producto_estado_ubicacion` (`id_estado_ubicacion`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`),
  CONSTRAINT `fk_producto_condicion` FOREIGN KEY (`id_condicion`) REFERENCES `condicion_producto` (`id_condicion`),
  CONSTRAINT `fk_producto_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado_producto` (`id_estado`),
  CONSTRAINT `fk_producto_estado_ubicacion` FOREIGN KEY (`id_estado_ubicacion`) REFERENCES `estado_ubicacion` (`id_estado_ubicacion`),
  CONSTRAINT `fk_producto_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `vendedores` (`id_vendedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `productos` VALUES
(1,1,14,1,1,1,'MOTO PULSAR N250','MOTO PASADA DE ROSACA SIN DETALLES, JALANDO AL 100 CHAVAL',44.10,'assets/uploads/producto_6ace5bb61921f3.01173664.jpeg');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `rol` enum('Vendedor','Comprador') NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `usuario` VALUES
(1,'Vendedor','l24920226','l24920226@voaxaca.tecnm.mx','$2y$10$.s.sUs1Rhs7qgP686g9wNOYK1BYeoNEZapHOFojcBJkSJD4pOlcU6','2026-10-13 10:21:15');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `vendedores`
--

DROP TABLE IF EXISTS `vendedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendedores` (
  `id_vendedor` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nombre_negocio` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `horario_atencion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_vendedor`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_vendedor_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendedores`
--

LOCK TABLES `vendedores` WRITE;
/*!40000 ALTER TABLE `vendedores` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `vendedores` VALUES
(1,1,'L24920226 Market','Vendedor activo en ITVOmarket','Lunes a domingo');
/*!40000 ALTER TABLE `vendedores` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `zona_venta`
--

DROP TABLE IF EXISTS `zona_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `zona_venta` (
  `id_zona_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_vendedor` int(11) NOT NULL,
  `estado_zona` enum('Activo','Desactivo','Pendiente') NOT NULL DEFAULT 'Pendiente',
  `latitud` decimal(10,7) NOT NULL,
  `longitud` decimal(10,7) NOT NULL,
  `punto_referencia` varchar(150) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_zona_venta`),
  KEY `fk_zonaventa_vendedor` (`id_vendedor`),
  CONSTRAINT `fk_zonaventa_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `vendedores` (`id_vendedor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zona_venta`
--

LOCK TABLES `zona_venta` WRITE;
/*!40000 ALTER TABLE `zona_venta` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `zona_venta` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Dumping routines for database 'marketitvo'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-13 12:50:43
