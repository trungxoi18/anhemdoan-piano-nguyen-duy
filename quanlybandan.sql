-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: quanlybandan
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
-- Table structure for table `chinhsachbaohanh`
--

DROP TABLE IF EXISTS `chinhsachbaohanh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chinhsachbaohanh` (
  `maCS` int(11) NOT NULL AUTO_INCREMENT,
  `tenChinhSach` varchar(255) NOT NULL,
  `noiDung` text NOT NULL,
  `ngayTao` datetime DEFAULT current_timestamp(),
  `ngayCapNhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`maCS`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chinhsachbaohanh`
--

LOCK TABLES `chinhsachbaohanh` WRITE;
/*!40000 ALTER TABLE `chinhsachbaohanh` DISABLE KEYS */;
INSERT INTO `chinhsachbaohanh` VALUES (1,'Chính sách bảo hành Piano Cơ (Upright & Grand)','1. Thời gian bảo hành: 5\r\n NĂM kể từ ngày giao đàn.\r\n2. Nội dung bảo hành:\r\n- Bảo hành các lỗi kỹ thuật do nhà sản xuất (kẹt phím, đứt dây tự nhiên, nứt thùng âm, gãy búa...).\r\n- Miễn phí căn chỉnh dây (tuning) 2 lần trong năm đầu tiên.\r\n3. Trường hợp KHÔNG bảo hành:\r\n- Đàn bị hỏng do thiên tai, hỏa hoạn, ngập nước, chuột bọ cắn phá.\r\n- Để đàn ở nơi có độ ẩm quá cao/quá thấp không theo hướng dẫn.\r\n- Tự ý nhờ thợ ngoài sửa chữa khi chưa thông báo cho cửa hàng.','2026-08-02 00:05:02','2026-09-16 21:14:47'),(2,'Chính sách bảo hành Piano Điện & Keyboard','1. Thời gian bảo hành: 2 NĂM chính hãng.\n2. Điều kiện bảo hành:\n- Lỗi bo mạch, màn hình LCD, liệt phím điện tử, hỏng nút bấm do lỗi nhà sản xuất.\n- Khách hàng giữ nguyên tem bảo hành, không có dấu hiệu cạy mở.\n3. 1 ĐỔI 1 TRONG 30 NGÀY:\n- Nếu phát sinh lỗi phần cứng không thể khắc phục, cửa hàng hỗ trợ đổi mới 100% sản phẩm cùng loại.','2026-08-02 00:05:02','2026-08-02 00:05:02'),(3,'Chính sách Nâng cấp & Thu cũ đổi mới','1. Cam kết thu mua lại (trong 2 năm đầu):\n- Cửa hàng cam kết thu mua lại đàn đã bán với mức khấu hao từ 15% - 25% giá trị hóa đơn (tùy tình trạng đàn).\n2. Trợ giá nâng cấp đàn:\n- Khi khách hàng có nhu cầu nâng cấp từ Piano Điện lên Piano Cơ, hoặc từ Upright lên Grand, sẽ được trợ giá thêm 10% (Tối đa 5.000.000đ) trên giá trị đàn mới.','2026-08-02 00:05:02','2026-08-02 00:05:02');
/*!40000 ALTER TABLE `chinhsachbaohanh` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chitietdieuchuyen`
--

DROP TABLE IF EXISTS `chitietdieuchuyen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chitietdieuchuyen` (
  `maPhieuDC` int(11) NOT NULL,
  `maSerial` int(11) NOT NULL,
  PRIMARY KEY (`maPhieuDC`,`maSerial`),
  KEY `maSerial` (`maSerial`),
  CONSTRAINT `chitietdieuchuyen_ibfk_1` FOREIGN KEY (`maPhieuDC`) REFERENCES `phieudieuchuyen` (`maPhieuDC`) ON DELETE CASCADE,
  CONSTRAINT `chitietdieuchuyen_ibfk_2` FOREIGN KEY (`maSerial`) REFERENCES `danserial` (`maSerial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chitietdieuchuyen`
--

LOCK TABLES `chitietdieuchuyen` WRITE;
/*!40000 ALTER TABLE `chitietdieuchuyen` DISABLE KEYS */;
INSERT INTO `chitietdieuchuyen` VALUES (3,37);
/*!40000 ALTER TABLE `chitietdieuchuyen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chitiethoadon`
--

DROP TABLE IF EXISTS `chitiethoadon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chitiethoadon` (
  `maChiTiet` int(11) NOT NULL AUTO_INCREMENT,
  `maHoaDon` int(11) DEFAULT NULL,
  `maSerial` int(11) DEFAULT NULL,
  `donGia` decimal(18,2) DEFAULT NULL,
  `khuyenMai` decimal(18,2) DEFAULT 0.00,
  PRIMARY KEY (`maChiTiet`),
  KEY `fk_cthd_hd` (`maHoaDon`),
  KEY `fk_cthd_ds` (`maSerial`),
  CONSTRAINT `fk_cthd_ds` FOREIGN KEY (`maSerial`) REFERENCES `danserial` (`maSerial`),
  CONSTRAINT `fk_cthd_hd` FOREIGN KEY (`maHoaDon`) REFERENCES `hoadon` (`maHoaDon`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chitiethoadon`
--

LOCK TABLES `chitiethoadon` WRITE;
/*!40000 ALTER TABLE `chitiethoadon` DISABLE KEYS */;
INSERT INTO `chitiethoadon` VALUES (1,1,4,1500000.00,0.00),(3,3,4,212212.00,0.00),(4,4,19,115555555.00,0.00),(6,8,26,1.00,0.00),(7,10,32,15000000.00,0.00),(9,12,32,1.00,0.00),(10,13,18,15000000.00,0.00),(11,14,33,85000000.00,0.00),(12,15,101,20000000.00,0.00),(13,15,21,350000000.00,0.00),(14,17,20,8000000.00,0.00),(20,18,16,25000000.00,0.00),(21,19,28,140000000.00,0.00),(22,20,70,15000000.00,0.00),(23,6,103,40000000.00,0.00),(24,11,81,15000000.00,0.00),(26,22,97,150000000.00,0.00),(27,23,37,25000000.00,0.00);
/*!40000 ALTER TABLE `chitiethoadon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chitietphieunhap`
--

DROP TABLE IF EXISTS `chitietphieunhap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chitietphieunhap` (
  `maChiTiet` int(11) NOT NULL AUTO_INCREMENT,
  `maPhieuNhap` int(11) DEFAULT NULL,
  `maSerial` int(11) DEFAULT NULL,
  `giaNhap` decimal(18,2) DEFAULT NULL,
  PRIMARY KEY (`maChiTiet`),
  KEY `fk_ctpn_pn` (`maPhieuNhap`),
  KEY `fk_ctpn_ds` (`maSerial`),
  CONSTRAINT `fk_ctpn_ds` FOREIGN KEY (`maSerial`) REFERENCES `danserial` (`maSerial`),
  CONSTRAINT `fk_ctpn_pn` FOREIGN KEY (`maPhieuNhap`) REFERENCES `phieunhap` (`maPhieuNhap`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chitietphieunhap`
--

LOCK TABLES `chitietphieunhap` WRITE;
/*!40000 ALTER TABLE `chitietphieunhap` DISABLE KEYS */;
INSERT INTO `chitietphieunhap` VALUES (1,1,41,23323423.00),(2,2,42,1500000.00),(3,3,43,15555555.00),(4,4,96,1500000.00),(5,5,97,1500000.00),(6,6,98,10000004.00),(7,7,99,15000000.00),(8,7,100,1577877.00),(9,8,101,16660000.00),(11,9,103,20000000.00),(12,10,104,11500000.00),(13,11,105,15000000.00);
/*!40000 ALTER TABLE `chitietphieunhap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chitietphieuxuat`
--

DROP TABLE IF EXISTS `chitietphieuxuat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chitietphieuxuat` (
  `maChiTiet` int(11) NOT NULL AUTO_INCREMENT,
  `maPhieuXuat` int(11) DEFAULT NULL,
  `maSerial` int(11) DEFAULT NULL,
  PRIMARY KEY (`maChiTiet`),
  KEY `fk_ctpx_px` (`maPhieuXuat`),
  KEY `fk_ctpx_ds` (`maSerial`),
  CONSTRAINT `fk_ctpx_ds` FOREIGN KEY (`maSerial`) REFERENCES `danserial` (`maSerial`),
  CONSTRAINT `fk_ctpx_px` FOREIGN KEY (`maPhieuXuat`) REFERENCES `phieuxuat` (`maPhieuXuat`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chitietphieuxuat`
--

LOCK TABLES `chitietphieuxuat` WRITE;
/*!40000 ALTER TABLE `chitietphieuxuat` DISABLE KEYS */;
INSERT INTO `chitietphieuxuat` VALUES (1,2,4),(2,3,4),(3,4,4),(4,5,4),(5,7,17),(6,10,4),(7,12,15),(8,16,19),(9,17,1),(10,18,1),(11,19,19),(12,20,19),(13,21,29),(14,21,30),(15,30,18),(16,31,33),(17,35,32),(18,40,70),(19,42,28),(20,54,101),(21,54,21),(22,55,16),(23,56,20),(24,57,103),(25,58,81),(26,60,32),(27,61,97),(29,62,97),(30,63,37);
/*!40000 ALTER TABLE `chitietphieuxuat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chuongtrinhkhuyenmai`
--

DROP TABLE IF EXISTS `chuongtrinhkhuyenmai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chuongtrinhkhuyenmai` (
  `maKM` int(11) NOT NULL AUTO_INCREMENT,
  `tenChuongTrinh` varchar(255) NOT NULL,
  `moTa` text DEFAULT NULL,
  `phanTramGiam` int(11) DEFAULT 0,
  `ngayBatDau` date NOT NULL,
  `ngayKetThuc` date NOT NULL,
  `trangThai` enum('Đang diễn ra','Sắp tới','Đã kết thúc') DEFAULT 'Đang diễn ra',
  `ngayTao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`maKM`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chuongtrinhkhuyenmai`
--

LOCK TABLES `chuongtrinhkhuyenmai` WRITE;
/*!40000 ALTER TABLE `chuongtrinhkhuyenmai` DISABLE KEYS */;
INSERT INTO `chuongtrinhkhuyenmai` VALUES (1,'Vui Tựu Trường - Vững Bước Tương Lai (Back to School)','Chào mừng năm học mới, Kho Đàn Pro mang đến gói ưu đãi khủng dành riêng cho học sinh, sinh viên:\r\n- Giảm trực tiếp 15% giá trị hóa đơn.\r\n- Tặng kèm 1 khóa học Piano cơ bản trị giá 2.000.000đ.\r\n- Tặng ghế da cao cấp và khăn phủ phím.\r\n* Yêu cầu: Khách hàng xuất trình thẻ Học sinh/Sinh viên còn hạn sử dụng.',15,'2026-08-01','2026-09-30','Đã kết thúc','2026-08-02 00:05:02'),(2,'Trung Thu Đoàn Viên - Rinh Đàn Cực Chiến','Sự kiện chào mừng Tết Trung Thu:\r\n- Giảm 5% cho tất cả dòng Piano Cơ.\r\n- Tặng Full phụ kiện: Ống sưởi, khăn phủ toàn đàn, ghế xoay thủy lực cao cấp.\r\n- Miễn phí vận chuyển toàn quốc.',10,'2026-09-01','2026-09-16','Đang diễn ra','2026-08-02 00:05:02'),(3,'Clearance Sale - Xả Kho Đón Năm Mới 2026','Chương trình xả kho lớn nhất trong năm:\n- Đồng giá hoặc giảm sâu lên đến 30% cho các mẫu Piano trưng bày tại cửa hàng.\n- Số lượng có hạn, áp dụng cho đến khi hết hàng tồn kho.',30,'2025-12-01','2025-12-31','Đã kết thúc','2026-08-02 00:05:02');
/*!40000 ALTER TABLE `chuongtrinhkhuyenmai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `danserial`
--

DROP TABLE IF EXISTS `danserial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `danserial` (
  `maSerial` int(11) NOT NULL AUTO_INCREMENT,
  `soSerial` varchar(100) NOT NULL,
  `maMau` int(11) DEFAULT NULL,
  `maKho` int(11) DEFAULT NULL,
  `maNCC` int(11) DEFAULT NULL,
  `tinhTrang` varchar(100) DEFAULT NULL,
  `trangThai` enum('Trong kho','Đã bán','Đang bảo hành','Lỗi/Khấu hao','Chờ nhập','Chờ xuất','Chờ giao','Đã hủy','Đang điều chuyển','Đã xuất hãng') DEFAULT 'Trong kho',
  `giaNhap` decimal(18,2) DEFAULT NULL,
  `giaBan` decimal(18,2) DEFAULT NULL,
  PRIMARY KEY (`maSerial`),
  UNIQUE KEY `soSerial` (`soSerial`),
  KEY `fk_ds_mau` (`maMau`),
  KEY `fk_ds_kho` (`maKho`),
  KEY `fk_ds_ncc` (`maNCC`),
  KEY `idx_ds_trangThai` (`trangThai`),
  KEY `idx_ds_giaBan` (`giaBan`),
  CONSTRAINT `fk_ds_kho` FOREIGN KEY (`maKho`) REFERENCES `kho` (`maKho`),
  CONSTRAINT `fk_ds_mau` FOREIGN KEY (`maMau`) REFERENCES `maudan` (`maMau`),
  CONSTRAINT `fk_ds_ncc` FOREIGN KEY (`maNCC`) REFERENCES `nhacungcap` (`maNCC`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `danserial`
--

LOCK TABLES `danserial` WRITE;
/*!40000 ALTER TABLE `danserial` DISABLE KEYS */;
INSERT INTO `danserial` VALUES (1,'FC342365423',3,2,1,'Trong kho','Đã bán',25000000.00,30000000.00),(3,'SN-TEST-1001',1,1,1,'M?i 100%','Trong kho',2500000.00,3000000.00),(4,'trungxoi',1,1,1,'M?i 100%','Đã bán',2500000.00,3000000.00),(13,'GIB-LP-001',6,1,2,'Mới 100%','Trong kho',40000000.00,50000000.00),(14,'GIB-LP-002',6,1,2,'Mới 100%','Trong kho',40000000.00,50000000.00),(15,'IBA-RG-001',7,1,2,'Mới 100%','Đã bán',20000000.00,25000000.00),(16,'IBA-RG-002',7,2,2,'Mới 100%','Chờ xuất',20000000.00,25000000.00),(17,'TAY-114-001',8,1,1,'Mới 100%','Đã bán',15000000.00,18000000.00),(18,'TAY-114-002',8,2,1,'Mới 100%','Đã bán',15000000.00,18000000.00),(19,'COR-C5-001',9,1,2,'Mới 100%','Đã bán',6000000.00,8000000.00),(20,'COR-C5-002',9,2,2,'Mới 100%','Chờ xuất',6000000.00,8000000.00),(21,'YAM-CFX-001',10,2,1,'Mới 100%','Đã bán',300000000.00,350000000.00),(22,'YAM-CFX-002',10,1,1,'Mới 100%','Trong kho',300000000.00,350000000.00),(23,'YAM-CFX-003',10,2,1,'Mới 100%','Trong kho',300000000.00,350000000.00),(24,'YAM-CFX-004',10,2,1,'Mới 100%','Trong kho',300000000.00,350000000.00),(25,'KAW-K300-001',11,1,2,'Mới 100%','Trong kho',120000000.00,140000000.00),(26,'KAW-K300-002',11,1,2,'Mới 100%','Trong kho',120000000.00,140000000.00),(27,'KAW-K300-003',11,2,2,'Mới 100%','Trong kho',120000000.00,140000000.00),(28,'KAW-K300-004',11,2,2,'Mới 100%','Chờ xuất',120000000.00,140000000.00),(29,'FEN-STRAT-001',12,1,2,'Mới 100%','Đã bán',18000000.00,22000000.00),(30,'FEN-STRAT-002',12,1,2,'Mới 100%','Đã bán',18000000.00,22000000.00),(31,'FEN-STRAT-003',12,2,2,'Mới 100%','Trong kho',18000000.00,22000000.00),(32,'FEN-STRAT-004',12,2,2,'Mới 100%','Chờ xuất',18000000.00,22000000.00),(33,'MAR-D28-001',13,1,1,'Mới 100%','Đã xuất hãng',70000000.00,85000000.00),(34,'MAR-D28-002',13,1,1,'Mới 100%','Trong kho',70000000.00,85000000.00),(35,'MAR-D28-003',13,2,1,'Mới 100%','Trong kho',70000000.00,85000000.00),(36,'MAR-D28-004',13,2,1,'Mới 100%','Trong kho',70000000.00,85000000.00),(37,'FEN-JBASS-001',14,2,2,'Mới 100%','Chờ xuất',20000000.00,25000000.00),(38,'FEN-JBASS-002',14,1,2,'Mới 100%','Trong kho',20000000.00,25000000.00),(39,'FEN-JBASS-003',14,2,2,'Mới 100%','Trong kho',20000000.00,25000000.00),(40,'FEN-JBASS-004',14,2,2,'Mới 100%','Trong kho',20000000.00,25000000.00),(41,'FC342365423f',9,2,1,'Mới','Trong kho',23323423.00,150000000.00),(42,'trungxoi nhập',1,2,2,'Mới','Trong kho',1500000.00,NULL),(43,'adsasdas',9,2,2,'Mới','Trong kho',15555555.00,21780000.00),(64,'SN-2-3744-1785735078',2,1,1,'Mới','Trong kho',10000000.00,15000000.00),(65,'SN-2-1697-1785735078',2,1,1,'Mới','Trong kho',10000000.00,15000000.00),(66,'SN-2-9235-1785735078',2,1,1,'Mới','Trong kho',10000000.00,15000000.00),(67,'SN-3-1323-1785735078',3,1,1,'Mới','Trong kho',10000000.00,15000000.00),(68,'SN-3-5933-1785735078',3,1,1,'Mới','Trong kho',10000000.00,15000000.00),(69,'SN-3-3682-1785735078',3,1,1,'Mới','Trong kho',10000000.00,15000000.00),(70,'SN-4-6946-1785735078',4,1,1,'Mới','Chờ xuất',10000000.00,15000000.00),(71,'SN-4-2228-1785735078',4,1,1,'Mới','Trong kho',10000000.00,15000000.00),(72,'SN-4-1539-1785735078',4,1,1,'Mới','Trong kho',10000000.00,15000000.00),(73,'SN-5-3047-1785735078',5,1,1,'Mới','Trong kho',10000000.00,15000000.00),(74,'SN-5-1140-1785735078',5,1,1,'Mới','Trong kho',10000000.00,15000000.00),(75,'SN-5-6079-1785735078',5,1,1,'Mới','Trong kho',10000000.00,15000000.00),(76,'SN-6-2917-1785735078',6,1,1,'Mới','Trong kho',10000000.00,15000000.00),(77,'SN-7-6157-1785735078',7,1,1,'Mới','Trong kho',10000000.00,15000000.00),(78,'SN-7-7918-1785735078',7,1,1,'Mới','Trong kho',10000000.00,15000000.00),(79,'SN-8-8308-1785735078',8,1,1,'Mới','Trong kho',10000000.00,15000000.00),(80,'SN-8-5363-1785735078',8,1,1,'Mới','Trong kho',10000000.00,15000000.00),(81,'SN-15-1994-1785735078',15,1,1,'Mới','Chờ xuất',10000000.00,15000000.00),(82,'SN-15-3661-1785735078',15,1,1,'Mới','Trong kho',10000000.00,15000000.00),(83,'SN-15-1436-1785735078',15,1,1,'Mới','Trong kho',10000000.00,15000000.00),(84,'SN-16-8637-1785735078',16,1,1,'Mới','Trong kho',10000000.00,15000000.00),(85,'SN-16-1342-1785735078',16,1,1,'Mới','Trong kho',10000000.00,15000000.00),(86,'SN-16-7957-1785735078',16,1,1,'Mới','Trong kho',10000000.00,15000000.00),(87,'SN-17-4325-1785735078',17,1,1,'Mới','Trong kho',10000000.00,15000000.00),(88,'SN-17-6570-1785735078',17,1,1,'Mới','Trong kho',10000000.00,15000000.00),(89,'SN-17-3158-1785735078',17,1,1,'Mới','Trong kho',10000000.00,15000000.00),(90,'SN-18-4903-1785735078',18,1,1,'Mới','Trong kho',10000000.00,15000000.00),(91,'SN-18-6231-1785735078',18,1,1,'Mới','Trong kho',10000000.00,15000000.00),(92,'SN-18-9113-1785735078',18,1,1,'Mới','Trong kho',10000000.00,15000000.00),(93,'SN-19-6702-1785735078',19,1,1,'Mới','Trong kho',10000000.00,15000000.00),(94,'SN-19-6627-1785735078',19,1,1,'Mới','Trong kho',10000000.00,15000000.00),(95,'SN-19-1748-1785735078',19,1,1,'Mới','Trong kho',10000000.00,15000000.00),(96,'8/6',9,2,1,'Mới','Trong kho',1500000.00,150000000.00),(97,'moi',9,1,1,'Mới','Đã bán',1500000.00,150000000.00),(98,'yamaha c40 0028',5,2,2,'Mới','Trong kho',10000004.00,NULL),(99,'1112',8,2,2,'Mới','Trong kho',15000000.00,NULL),(100,'211111',6,2,2,'Mới','Trong kho',1577877.00,NULL),(101,'GLS 001',6,1,1,'Mới','Đã bán',16660000.00,20000000.00),(103,'TAY-114-003',2,2,1,'Mới','Chờ xuất',20000000.00,40000000.00),(104,'trungxoimua',1,1,1,'Mới','Trong kho',11500000.00,16100000.00),(105,'CFX 1',10,1,2,'Mới','Trong kho',15000000.00,NULL);
/*!40000 ALTER TABLE `danserial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dieuchuyenkho`
--

DROP TABLE IF EXISTS `dieuchuyenkho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dieuchuyenkho` (
  `maDieuChuyen` int(11) NOT NULL AUTO_INCREMENT,
  `maSerial` int(11) DEFAULT NULL,
  `tuKho` int(11) DEFAULT NULL,
  `denKho` int(11) DEFAULT NULL,
  `ngayChuyen` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`maDieuChuyen`),
  KEY `fk_dc_ds` (`maSerial`),
  KEY `fk_dc_tukho` (`tuKho`),
  KEY `fk_dc_denkho` (`denKho`),
  CONSTRAINT `fk_dc_denkho` FOREIGN KEY (`denKho`) REFERENCES `kho` (`maKho`),
  CONSTRAINT `fk_dc_ds` FOREIGN KEY (`maSerial`) REFERENCES `danserial` (`maSerial`),
  CONSTRAINT `fk_dc_tukho` FOREIGN KEY (`tuKho`) REFERENCES `kho` (`maKho`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dieuchuyenkho`
--

LOCK TABLES `dieuchuyenkho` WRITE;
/*!40000 ALTER TABLE `dieuchuyenkho` DISABLE KEYS */;
/*!40000 ALTER TABLE `dieuchuyenkho` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hangdan`
--

DROP TABLE IF EXISTS `hangdan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hangdan` (
  `maHang` int(11) NOT NULL AUTO_INCREMENT,
  `tenHang` varchar(100) NOT NULL,
  `xuatXu` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`maHang`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hangdan`
--

LOCK TABLES `hangdan` WRITE;
/*!40000 ALTER TABLE `hangdan` DISABLE KEYS */;
INSERT INTO `hangdan` VALUES (1,'Yamaha','Nhật Bản'),(2,'Taylor','Mỹ'),(3,'Fender','Mỹ'),(4,'Cordoba','Tây Ban Nha'),(5,'Suzuki','Nhật Bản'),(6,'Kawai','Nhật Bản'),(7,'Gibson','Mỹ'),(8,'Ibanez','Nhật Bản'),(9,'Martin','Mỹ'),(10,'Martin','Mỹ');
/*!40000 ALTER TABLE `hangdan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hoadon`
--

DROP TABLE IF EXISTS `hoadon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hoadon` (
  `maHoaDon` int(11) NOT NULL AUTO_INCREMENT,
  `maKhachHang` int(11) DEFAULT NULL,
  `maKM` int(11) DEFAULT NULL,
  `tenDonVi` varchar(255) DEFAULT NULL,
  `maSoThue` varchar(50) DEFAULT NULL,
  `maNhanVien` int(11) DEFAULT NULL,
  `ngayLap` datetime DEFAULT current_timestamp(),
  `tongTien` decimal(18,2) DEFAULT NULL,
  `hinhThucThanhToan` varchar(50) DEFAULT NULL,
  `thueGTGT` int(11) DEFAULT 0,
  `trangThai` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`maHoaDon`),
  KEY `fk_hd_kh` (`maKhachHang`),
  KEY `fk_hd_nv` (`maNhanVien`),
  KEY `idx_hoadon_ngayLap` (`ngayLap`),
  KEY `idx_hoadon_trangThai` (`trangThai`),
  CONSTRAINT `fk_hd_kh` FOREIGN KEY (`maKhachHang`) REFERENCES `khachhang` (`maKhachHang`),
  CONSTRAINT `fk_hd_nv` FOREIGN KEY (`maNhanVien`) REFERENCES `nhanvien` (`maNhanVien`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hoadon`
--

LOCK TABLES `hoadon` WRITE;
/*!40000 ALTER TABLE `hoadon` DISABLE KEYS */;
INSERT INTO `hoadon` VALUES (1,1,NULL,NULL,NULL,1,'2026-07-06 22:18:32',1500000.00,'Tiền mặt',0,'Chờ duyệt'),(3,1,NULL,NULL,NULL,1,'2026-07-06 22:26:21',212212.00,'Trả góp',0,'ðang giao'),(4,2,NULL,NULL,NULL,2,'2026-08-03 11:42:41',115555555.00,'Chuyển khoản',0,'Đang giao'),(6,2,NULL,'','',1,'2026-08-06 15:00:59',40000000.00,'Tiền mặt',0,'Đang giao'),(8,1,NULL,NULL,NULL,1,'2026-08-06 15:01:30',1.00,'Trả góp',0,'Đang giao'),(10,1,NULL,'trung xoi','ádfsdafsdafsda',1,'2026-08-08 19:33:31',15000000.00,'Tiền mặt',4,'Hoàn thành'),(11,1,NULL,'trung xoi','ádfsdafsdafsda',1,'2026-08-08 19:34:33',15600000.00,'Tiền mặt',4,'Đang giao'),(12,2,NULL,'trung xoi','ádfsdafsdafsda',1,'2026-08-08 20:09:17',1.00,'Thẻ tín dụng',1,'Đang giao'),(13,2,NULL,'khach hang','1121332213',1,'2026-08-09 13:18:13',15000000.00,'Tiền mặt',5,'Hoàn thành'),(14,4,1,'trung xoi','1121332213',1,'2026-08-09 14:45:11',85000000.00,'Chuyển khoản',3,'Hoàn thành'),(15,5,1,'công ty nguyễn minh','',1,'2026-08-12 11:39:45',317645000.00,'Chuyển khoản',1,'Đang giao'),(17,5,NULL,'','',1,'2026-08-12 13:54:41',8000000.00,'Tiền mặt',0,'Đang giao'),(18,3,1,'','',2,'2026-08-12 13:57:16',21250000.00,'Tiền mặt',0,'Đang giao'),(19,2,NULL,'công ty nguyễn minh','',2,'2026-08-17 00:46:49',140000000.00,'Tiền mặt',0,'Đang giao'),(20,2,NULL,'trung xoi','',2,'2026-08-17 00:58:23',15000000.00,'Tiền mặt',0,'Đang giao'),(21,1,NULL,NULL,NULL,1,'2026-08-25 13:14:20',50000000.00,'Chuy?n kho?n',0,'ðÒ giao'),(22,2,2,'',' 1',1,'2026-09-09 11:02:57',141750000.00,'Tiền mặt',5,'Đang giao'),(23,2,2,'','',1,'2026-09-24 00:51:18',22500000.00,'Tiền mặt',0,'Đang giao');
/*!40000 ALTER TABLE `hoadon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `khachhang`
--

DROP TABLE IF EXISTS `khachhang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `khachhang` (
  `maKhachHang` int(11) NOT NULL AUTO_INCREMENT,
  `hoTen` varchar(100) NOT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `emailKH` varchar(100) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`maKhachHang`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `khachhang`
--

LOCK TABLES `khachhang` WRITE;
/*!40000 ALTER TABLE `khachhang` DISABLE KEYS */;
INSERT INTO `khachhang` VALUES (1,'Khách lẻ vãng lai','',NULL,'Mua trực tiếp tại cửa hàng'),(2,'Đặng Văn Khách','0999888777',NULL,'Lê Chân, Hải Phòng'),(3,'nguyễn đức trung','01547774455','trungxun28@gmail.com','30a1 hạ đoạn 2'),(4,'nguyen tung duong','12312312312312','duong@gmail.com','big c hair phongf'),(5,'nguyễn quang minh','0356611575','minh@gmail.com','hà nội fc');
/*!40000 ALTER TABLE `khachhang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kho`
--

DROP TABLE IF EXISTS `kho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kho` (
  `maKho` int(11) NOT NULL AUTO_INCREMENT,
  `tenKho` varchar(100) NOT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`maKho`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kho`
--

LOCK TABLES `kho` WRITE;
/*!40000 ALTER TABLE `kho` DISABLE KEYS */;
INSERT INTO `kho` VALUES (1,'Kho 1','Manhattan 0901, Vinhome Imperia'),(2,'Kho 2','53 Lạch Tray, Ngô Quyền, Hải Phòng');
/*!40000 ALTER TABLE `kho` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loaidan`
--

DROP TABLE IF EXISTS `loaidan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loaidan` (
  `maLoai` int(11) NOT NULL AUTO_INCREMENT,
  `tenLoai` varchar(100) NOT NULL,
  PRIMARY KEY (`maLoai`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loaidan`
--

LOCK TABLES `loaidan` WRITE;
/*!40000 ALTER TABLE `loaidan` DISABLE KEYS */;
INSERT INTO `loaidan` VALUES (1,'Acoustic Guitar'),(2,'Classic Guitar'),(3,'Electric Guitar'),(4,'Bass Guitar'),(5,'Piano Điện'),(6,'Grand Piano'),(7,'Upright Piano'),(8,'Grand Piano'),(9,'Upright Piano');
/*!40000 ALTER TABLE `loaidan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maudan`
--

DROP TABLE IF EXISTS `maudan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maudan` (
  `maMau` int(11) NOT NULL AUTO_INCREMENT,
  `tenMau` varchar(100) NOT NULL,
  `maLoai` int(11) DEFAULT NULL,
  `maHang` int(11) DEFAULT NULL,
  `moTa` text DEFAULT NULL,
  `hinhAnh` varchar(255) DEFAULT NULL,
  `xuatXu` varchar(100) DEFAULT NULL,
  `chatLieu` varchar(255) DEFAULT NULL,
  `kichThuoc` varchar(100) DEFAULT NULL,
  `trongLuong` varchar(50) DEFAULT NULL,
  `mauSac` varchar(100) DEFAULT NULL,
  `baoHanh` varchar(50) DEFAULT '5 năm',
  PRIMARY KEY (`maMau`),
  KEY `fk_md_loai` (`maLoai`),
  KEY `fk_md_hang` (`maHang`),
  CONSTRAINT `fk_md_hang` FOREIGN KEY (`maHang`) REFERENCES `hangdan` (`maHang`),
  CONSTRAINT `fk_md_loai` FOREIGN KEY (`maLoai`) REFERENCES `loaidan` (`maLoai`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maudan`
--

LOCK TABLES `maudan` WRITE;
/*!40000 ALTER TABLE `maudan` DISABLE KEYS */;
INSERT INTO `maudan` VALUES (1,'Yamaha F310',1,1,'Đàn Acoustic dáng D, phù hợp cho người mới bắt đầu.','yamaha_f310.jpg',NULL,NULL,NULL,NULL,NULL,'5 năm'),(2,'Taylor 114ce',1,2,'Đàn dáng Grand Auditorium có EQ, âm thanh cao cấp.','yamaha-cfx.jpg',NULL,NULL,NULL,NULL,NULL,'5 năm'),(3,'Yamaha C40',2,1,'Đàn Classic tiêu chuẩn quốc dân, dây nilon dễ bấm.','yamaha_f310.jpg',NULL,NULL,NULL,NULL,NULL,'5 năm'),(4,'Fender Stratocaster',3,3,'Đàn điện huyền thoại dành cho dân chơi Rock/Pop.','tải xuống.jpg',NULL,NULL,NULL,NULL,NULL,'5 năm'),(5,'Yamaha C40',2,1,'Đàn Classic tiêu chuẩn quốc dân, dây nilon dễ bấm.','yamaha_f310.jpg',NULL,NULL,NULL,NULL,NULL,'5 năm'),(6,'Gibson Les Paul Standard',3,7,'Huyền thoại nhạc Rock','grand_piano.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(7,'Ibanez RG550',3,8,'Đàn dành cho shredder','upright_piano.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(8,'Taylor 114ce',1,2,'Acoustic Guitar cao cấp','electric_guitar.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(9,'Cordoba C5',2,4,'Classic Guitar chuẩn mực','acoustic_guitar.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(10,'Yamaha CFX Grand Piano',6,1,'Đàn Grand Piano cao cấp dành cho biểu diễn chuyên nghiệp.','grand_piano.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(11,'Kawai K-300',7,6,'Đàn Upright Piano chuẩn mực cho luyện tập và giảng dạy.','upright_piano.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(12,'Fender Stratocaster Player',3,3,'Cây guitar điện huyền thoại với âm thanh linh hoạt, chuẩn mực.','electric_guitar.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(13,'Martin D-28',1,9,'Huyền thoại Acoustic Guitar, âm bass trầm ấm uy lực.','acoustic_guitar.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(14,'Fender Jazz Bass',4,3,'Bass guitar tiêu chuẩn cho các tay chơi chuyên nghiệp.','bass_guitar.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(15,'Yamaha CFX Grand Piano',6,1,'Đàn Grand Piano cao cấp dành cho biểu diễn chuyên nghiệp.','grand_piano.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(16,'Kawai K-300',7,6,'Đàn Upright Piano chuẩn mực cho luyện tập và giảng dạy.','upright_piano.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(17,'Fender Stratocaster Player',3,3,'Cây guitar điện huyền thoại với âm thanh linh hoạt, chuẩn mực.','electric_guitar.png',NULL,NULL,NULL,NULL,NULL,'5 năm'),(18,'Martin D-28',1,9,'Huyền thoại Acoustic Guitar, âm bass trầm ấm uy lực.','acoustic_guitar.png','','','','','','5 năm'),(19,'Fender Jazz Bass',4,3,'Bass guitar tiêu chuẩn cho các tay chơi chuyên nghiệp.','bass_guitar.png',NULL,NULL,NULL,NULL,NULL,'5 năm');
/*!40000 ALTER TABLE `maudan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhacungcap`
--

DROP TABLE IF EXISTS `nhacungcap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nhacungcap` (
  `maNCC` int(11) NOT NULL AUTO_INCREMENT,
  `tenNCC` varchar(100) NOT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `emailNCC` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`maNCC`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nhacungcap`
--

LOCK TABLES `nhacungcap` WRITE;
/*!40000 ALTER TABLE `nhacungcap` DISABLE KEYS */;
INSERT INTO `nhacungcap` VALUES (1,'Công ty Nhạc cụ Yamaha Việt Nam','Quận 1, TP. HCM','02811112222','contact@yamaha.vn'),(2,'Nhà phân phối Sao Mai','Đống Đa, Hà Nội','02433334444','saomaimusic@gmail.com');
/*!40000 ALTER TABLE `nhacungcap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhanvien`
--

DROP TABLE IF EXISTS `nhanvien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nhanvien` (
  `maNhanVien` int(11) NOT NULL AUTO_INCREMENT,
  `hoTen` varchar(100) NOT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `emailNV` varchar(100) DEFAULT NULL,
  `anhDaiDien` varchar(255) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Hoạt động',
  `createAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`maNhanVien`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nhanvien`
--

LOCK TABLES `nhanvien` WRITE;
/*!40000 ALTER TABLE `nhanvien` DISABLE KEYS */;
INSERT INTO `nhanvien` VALUES (1,'Nguyễn Đức Trung','0915738381','admin@shopdan.com','uploads/avatars/avatar_1_1789021721.jpeg','Hoạt động','2026-04-22 10:48:38'),(2,'Nguyễn Tùng Dương','0987654321','sale1@shopdan.com',NULL,'Hoạt động','2026-04-22 10:48:38'),(3,'Nguyễn Quang Minh','0911222333','kho1@shopdan.com',NULL,'Hoạt động','2026-04-22 10:48:38'),(4,'nguyen quang minh','01554784555','minh@gmail.com',NULL,'Đang làm việc','2026-08-12 12:04:49'),(5,'Trung Nguyễn','0915738381','trungxun28@gmail.com','uploads/avatars/avatar_5_1786511505.png','Đang làm việc','2026-08-12 12:09:22');
/*!40000 ALTER TABLE `nhanvien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhatkyhethong`
--

DROP TABLE IF EXISTS `nhatkyhethong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nhatkyhethong` (
  `maNhatKy` int(11) NOT NULL AUTO_INCREMENT,
  `maTaiKhoan` int(11) NOT NULL,
  `loaiHanhDong` varchar(100) NOT NULL,
  `chiTiet` text NOT NULL,
  `ngayTao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`maNhatKy`),
  KEY `maTaiKhoan` (`maTaiKhoan`),
  KEY `idx_log_ngayTao` (`ngayTao`),
  CONSTRAINT `nhatkyhethong_ibfk_1` FOREIGN KEY (`maTaiKhoan`) REFERENCES `taikhoan` (`maTaiKhoan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nhatkyhethong`
--

LOCK TABLES `nhatkyhethong` WRITE;
/*!40000 ALTER TABLE `nhatkyhethong` DISABLE KEYS */;
INSERT INTO `nhatkyhethong` VALUES (1,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-07-06 11:24:54'),(3,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-07-06 12:15:22'),(5,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-07-06 12:23:57'),(6,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-07-06 22:16:13'),(9,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-07-06 23:50:57'),(10,1,'XUẤT KHO','Đã lập phiếu xuất kho #5 - Xuất 1 sản phẩm','2026-07-06 23:58:34'),(11,1,'XUẤT KHO','Đã lập phiếu xuất kho #7 - Xuất 1 sản phẩm','2026-07-06 23:59:53'),(12,1,'XUẤT KHO','Đã lập phiếu xuất kho #10 - Xuất 1 sản phẩm','2026-07-07 00:04:30'),(13,1,'XUẤT KHO','Đã lập phiếu xuất kho #12 - Xuất 1 sản phẩm','2026-07-07 00:05:23'),(14,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-01 23:35:28'),(15,1,'NHẬP KHO','Đã lập phiếu nhập kho #1 (1 sản phẩm, Tổng: 23.323.423đ)','2026-08-01 23:53:07'),(16,1,'NHẬP KHO','Đã lập phiếu nhập kho #2 (1 sản phẩm, Tổng: 1.500.000đ)','2026-08-01 23:55:35'),(18,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-02 13:03:35'),(19,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-02 13:10:18'),(20,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-03 11:35:04'),(22,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-03 11:37:20'),(23,2,'XUẤT KHO','Đã lập phiếu xuất kho #16 - Xuất 1 sản phẩm','2026-08-03 11:43:42'),(24,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-03 11:44:38'),(25,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 11:59:39'),(26,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 12:00:17'),(27,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 12:00:20'),(28,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 12:02:26'),(29,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 12:02:38'),(30,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 12:02:59'),(31,1,'XUẤT KHO','Đã lập phiếu xuất kho #17 - Xuất 1 sản phẩm','2026-08-03 12:13:51'),(32,1,'NHẬP KHO','Đã lập phiếu nhập kho #3 (1 sản phẩm, Tổng: 15.555.555đ)','2026-08-03 12:15:20'),(33,1,'CẬP NHẬT HỒ SƠ','Đã xóa ảnh đại diện','2026-08-03 12:45:31'),(34,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 12:45:33'),(35,1,'CẬP NHẬT HỒ SƠ','Đã cập nhật thông tin cá nhân','2026-08-03 12:47:05'),(36,1,'CẬP NHẬT ẢNH ĐẠI DIỆN','Đã thay đổi ảnh đại diện','2026-08-03 12:47:23'),(37,1,'CẬP NHẬT ẢNH ĐẠI DIỆN','Đã thay đổi ảnh đại diện','2026-08-03 12:53:41'),(38,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-03 12:56:30'),(39,2,'XUẤT KHO','Đã lập phiếu xuất kho #18 - Xuất 1 sản phẩm','2026-08-03 13:00:48'),(40,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-04 23:45:34'),(41,1,'CẬP NHẬT ẢNH ĐẠI DIỆN','Đã thay đổi ảnh đại diện','2026-08-04 23:46:31'),(42,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-06 14:54:37'),(43,1,'NHẬP KHO','Đã lập phiếu nhập kho #4 (1 sản phẩm, Tổng: 1.500.000đ)','2026-08-06 15:02:27'),(44,1,'XUẤT KHO','Đã lập phiếu xuất kho #19 - Xuất 1 sản phẩm','2026-08-06 15:04:47'),(45,1,'XUẤT KHO','Đã lập phiếu xuất kho #20 - Xuất 1 sản phẩm','2026-08-06 15:37:46'),(46,1,'NHẬP KHO','Đã lập phiếu nhập kho #5 (1 sản phẩm, Tổng: 1.500.000đ)','2026-08-06 15:39:50'),(47,1,'XUẤT KHO','Đã lập phiếu xuất kho #21 - Xuất 2 sản phẩm','2026-08-06 15:55:24'),(48,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-08 19:18:42'),(49,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #11 với tổng tiền 15.000.000đ','2026-08-08 19:34:33'),(50,1,'NHẬP KHO','Đã lập phiếu nhập kho #6 (1 sản phẩm, Tổng: 10.000.004đ)','2026-08-08 19:37:39'),(51,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-08 19:57:17'),(52,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #12 với tổng tiền 1đ','2026-08-08 20:09:17'),(53,1,'CẬP NHẬT ẢNH ĐẠI DIỆN','Đã thay đổi ảnh đại diện','2026-08-08 23:32:20'),(55,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-08 23:59:19'),(56,1,'NHẬP KHO','Đã lập phiếu nhập kho #7 (2 sản phẩm, Tổng: 16.577.877đ)','2026-08-09 00:09:52'),(57,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu nhập #7','2026-08-09 00:10:27'),(59,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-09 00:18:01'),(60,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-09 12:57:31'),(61,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-09 13:00:09'),(62,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-09 13:17:16'),(63,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #13 với tổng tiền 15.000.000đ','2026-08-09 13:18:13'),(64,1,'NHẬP KHO','Đã lập phiếu nhập kho #8 (1 sản phẩm, Tổng: 16.660.000đ)','2026-08-09 13:29:51'),(65,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu nhập #8','2026-08-09 13:30:49'),(66,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-09 14:30:28'),(67,1,'XUẤT KHO','Đã lập phiếu xuất kho #30 - Xuất 1 sản phẩm','2026-08-09 14:31:03'),(68,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu xuất #30','2026-08-09 14:33:57'),(69,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #14 với tổng tiền 85.000.000đ','2026-08-09 14:45:11'),(70,1,'XUẤT KHO','Đã lập phiếu xuất kho #31 - Xuất 1 sản phẩm','2026-08-09 14:45:26'),(71,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu xuất #31','2026-08-09 14:46:06'),(72,1,'TẠO BẢO TRÍ','Lập phiếu tiếp nhận bảo trì #1 cho hóa đơn #6','2026-08-09 16:54:39'),(73,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu tiếp nhận bảo trì #1','2026-08-09 16:54:44'),(74,1,'YÊU CẦU BẢO TRÍ','Yêu cầu nhập kho bảo trì phiếu #1','2026-08-09 16:55:36'),(75,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu nhập kho bảo trì #1','2026-08-09 16:55:43'),(76,1,'YÊU CẦU BẢO TRÍ','Yêu cầu xuất hãng bảo trì phiếu #1','2026-08-09 16:55:58'),(77,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu xuất hãng bảo trì #1','2026-08-09 16:56:00'),(78,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-11 00:33:44'),(79,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 11:36:05'),(80,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #15 với tổng tiền 370.000.000đ','2026-08-12 11:39:45'),(81,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 12:05:01'),(82,4,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 12:05:38'),(83,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 12:08:16'),(84,4,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 12:08:51'),(85,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 12:09:30'),(86,5,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 12:09:51'),(87,5,'CẬP NHẬT ẢNH ĐẠI DIỆN','Đã thay đổi ảnh đại diện','2026-08-12 12:11:45'),(88,5,'HOÀN THÀNH BẢO TRÍ','Đã hoàn thành phiếu bảo trì #1','2026-08-12 12:26:20'),(89,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 12:26:59'),(90,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #17 với tổng tiền 8.000.000đ','2026-08-12 13:54:41'),(91,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 13:56:57'),(92,2,'LẬP HÓA ĐƠN','Đã lập hóa đơn #18 với tổng tiền 25.000.000đ','2026-08-12 13:57:16'),(93,2,'NHẬP KHO','Đã lập phiếu nhập kho #9 (1 sản phẩm, Tổng: 20.000.000đ)','2026-08-12 13:58:59'),(94,2,'NHẬP KHO','Đã SỬA phiếu nhập kho #9 (1 sản phẩm, Tổng: 20.000.000đ)','2026-08-12 14:03:26'),(95,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-12 14:03:38'),(96,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu nhập #9','2026-08-12 14:10:50'),(97,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-16 12:27:31'),(98,1,'TẠO BẢO TRÍ','Lập phiếu tiếp nhận bảo trì #2 cho hóa đơn #14','2026-08-16 12:42:25'),(99,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu tiếp nhận bảo trì #2','2026-08-16 12:42:31'),(100,1,'YÊU CẦU BẢO TRÍ','Yêu cầu nhập kho bảo trì phiếu #2','2026-08-16 12:42:37'),(101,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu nhập kho bảo trì #2','2026-08-16 12:42:48'),(102,1,'YÊU CẦU BẢO TRÍ','Yêu cầu xuất hãng bảo trì phiếu #2','2026-08-16 12:43:07'),(103,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu xuất hãng bảo trì #2','2026-08-16 12:43:09'),(104,1,'HOÀN THÀNH BẢO TRÍ','Đã hoàn thành phiếu bảo trì #2','2026-08-16 12:43:11'),(105,1,'XUẤT KHO','Đã lập phiếu xuất kho #35 - Xuất 1 sản phẩm','2026-08-16 12:57:25'),(106,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu xuất #35','2026-08-16 12:57:39'),(107,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-16 23:34:13'),(108,1,'SỬA HÓA ĐƠN','Đã sửa hóa đơn #18','2026-08-17 00:08:37'),(109,1,'SỬA HÓA ĐƠN','Đã sửa hóa đơn #18','2026-08-17 00:08:48'),(110,1,'SỬA HÓA ĐƠN','Đã sửa hóa đơn #18','2026-08-17 00:09:10'),(111,1,'NHẬP KHO','Đã lập phiếu nhập kho #10 (1 sản phẩm, Tổng: 11.500.000đ)','2026-08-17 00:35:54'),(112,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 00:45:20'),(113,2,'LẬP HÓA ĐƠN','Đã lập hóa đơn #19 với tổng tiền 140.000.000đ','2026-08-17 00:46:49'),(114,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 00:48:56'),(115,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 00:55:52'),(116,2,'LẬP HÓA ĐƠN','Đã lập hóa đơn #20 với tổng tiền 15.000.000đ','2026-08-17 00:58:23'),(117,2,'XUẤT KHO','Đã lập phiếu xuất kho #40 - Xuất 1 sản phẩm','2026-08-17 01:01:59'),(118,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 01:02:26'),(119,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 01:02:33'),(120,1,'XUẤT KHO','Đã lập phiếu xuất kho #42 - Xuất 1 sản phẩm','2026-08-17 01:03:06'),(121,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 23:42:43'),(122,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 23:45:17'),(123,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-17 23:47:15'),(124,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-21 12:45:26'),(125,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-21 12:48:50'),(126,1,'XUẤT KHO','Đã lập phiếu xuất kho #54 - Xuất 2 sản phẩm','2026-08-21 13:21:52'),(127,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-21 13:29:24'),(128,1,'XUẤT KHO','Đã lập phiếu xuất kho #55 - Xuất 1 sản phẩm','2026-08-21 13:30:56'),(129,1,'XUẤT KHO','Đã lập phiếu xuất kho #56 - Xuất 1 sản phẩm','2026-08-21 13:45:44'),(130,1,'SỬA HÓA ĐƠN','Đã sửa hóa đơn #6','2026-08-21 13:48:31'),(131,1,'XUẤT KHO','Đã lập phiếu xuất kho #57 - Xuất 1 sản phẩm','2026-08-21 13:48:50'),(132,1,'SỬA HÓA ĐƠN','Đã sửa hóa đơn #11','2026-08-21 13:51:54'),(133,1,'XUẤT KHO','Đã lập phiếu xuất kho #58 - Xuất 1 sản phẩm','2026-08-21 13:52:36'),(134,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-21 14:00:13'),(135,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-21 14:39:19'),(136,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-25 12:40:58'),(137,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-25 12:40:59'),(138,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-25 12:57:38'),(139,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-08-25 13:12:14'),(140,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-04 14:19:57'),(141,1,'XUẤT KHO','Đã lập phiếu xuất kho #60 - Xuất 1 sản phẩm','2026-09-04 14:22:37'),(142,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-04 14:23:38'),(143,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-04 14:34:07'),(144,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-04 14:37:10'),(145,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-05 13:00:14'),(146,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-06 00:36:04'),(147,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-06 00:44:29'),(148,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu nhập #10','2026-09-06 01:12:03'),(149,1,'NIÊM YẾT GIÁ','Admin cập nhật giá bán Yamaha F310 (Serial: trungxoimua) thành 16.100.000 đ','2026-09-06 01:18:12'),(150,1,'NIÊM YẾT GIÁ','Admin cập nhật giá bán Cordoba C5 (Serial: adsasdas) thành 21.780.000 đ','2026-09-06 01:32:16'),(151,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-09 10:18:09'),(152,1,'NIÊM YẾT GIÁ','Admin cập nhật giá bán hàng loạt cho mẫu Cordoba C5 (3 sản phẩm) thành 150.000.000 đ','2026-09-09 10:43:52'),(153,1,'NHẬP KHO','Đã lập phiếu nhập kho #11 (1 sản phẩm, Tổng: 15.000.000đ)','2026-09-09 10:54:36'),(154,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu nhập #11','2026-09-09 10:55:21'),(155,1,'XUẤT KHO','Tự động lập phiếu xuất #61 kèm theo hóa đơn #22','2026-09-09 11:02:57'),(156,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #22 với tổng tiền 150.000.000đ','2026-09-09 11:02:57'),(157,1,'DUYỆT PHIẾU','Admin đã TỪ CHỐI Phiếu xuất #61','2026-09-09 11:14:16'),(158,1,'SỬA HÓA ĐƠN','Đã sửa hóa đơn #22','2026-09-09 11:14:58'),(159,1,'XUẤT KHO','Đã lập phiếu xuất kho #62 - Xuất 1 sản phẩm','2026-09-09 11:15:16'),(160,1,'XUẤT KHO','Đã SỬA phiếu xuất kho #62 - Xuất 1 sản phẩm','2026-09-09 11:18:17'),(161,1,'TẠO BẢO TRÍ','Lập phiếu tiếp nhận bảo trì #3 cho hóa đơn #15','2026-09-09 11:32:57'),(162,1,'TỪ CHỐI BẢO TRÍ','Admin đã từ chối Phiếu tiếp nhận bảo trì #3','2026-09-09 11:33:30'),(163,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-09 11:33:48'),(164,2,'TẠO BẢO TRÍ','Lập phiếu tiếp nhận bảo trì #4 cho hóa đơn #15','2026-09-09 11:34:55'),(165,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-09 11:35:18'),(166,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu tiếp nhận bảo trì #4','2026-09-09 11:35:25'),(167,1,'YÊU CẦU BẢO TRÍ','Yêu cầu nhập kho bảo trì phiếu #4','2026-09-09 11:35:37'),(168,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu nhập kho bảo trì #4','2026-09-09 11:35:45'),(169,1,'YÊU CẦU BẢO TRÍ','Yêu cầu nhập kho bảo trì phiếu #4','2026-09-09 11:37:00'),(170,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu nhập kho bảo trì #4','2026-09-09 11:37:02'),(171,1,'YÊU CẦU BẢO TRÍ','Yêu cầu xuất hãng bảo trì phiếu #4','2026-09-09 11:37:07'),(172,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu xuất hãng bảo trì #4','2026-09-09 11:37:09'),(173,1,'HOÀN THÀNH BẢO TRÍ','Đã hoàn thành phiếu bảo trì #4','2026-09-09 11:40:56'),(174,1,'TẠO BẢO TRÍ','Lập phiếu tiếp nhận bảo trì #5 cho hóa đơn #15','2026-09-09 11:41:20'),(175,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu tiếp nhận bảo trì #5','2026-09-09 11:41:22'),(176,1,'YÊU CẦU BẢO TRÍ','Yêu cầu nhập kho bảo trì phiếu #5','2026-09-09 11:41:27'),(177,1,'TỪ CHỐI BẢO TRÍ','Admin đã từ chối Yêu cầu nhập kho bảo trì #5','2026-09-09 11:41:41'),(178,1,'YÊU CẦU BẢO TRÍ','Yêu cầu nhập Kho 2 bảo trì phiếu #5','2026-09-09 11:46:38'),(179,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu nhập kho bảo trì #5','2026-09-09 11:46:39'),(180,1,'YÊU CẦU BẢO TRÍ','Yêu cầu xuất hãng bảo trì phiếu #5','2026-09-09 11:46:44'),(181,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu xuất hãng bảo trì #5','2026-09-09 11:46:46'),(182,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-10 13:03:12'),(183,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-10 13:20:36'),(184,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-10 13:26:46'),(185,1,'CẬP NHẬT ẢNH ĐẠI DIỆN','Đã thay đổi ảnh đại diện','2026-09-10 13:28:41'),(186,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-16 14:07:47'),(187,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-16 21:10:18'),(188,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-21 15:42:51'),(189,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-22 15:18:08'),(190,1,'TẠO BẢO TRÍ','Lập phiếu tiếp nhận bảo trì #6 cho hóa đơn #22','2026-09-23 00:43:21'),(191,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu tiếp nhận bảo trì #6','2026-09-23 00:43:31'),(192,1,'YÊU CẦU BẢO TRÍ','Yêu cầu nhập Kho 1 bảo trì phiếu #6','2026-09-23 00:44:59'),(193,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu nhập kho bảo trì #6','2026-09-23 00:45:03'),(194,1,'YÊU CẦU BẢO TRÍ','Yêu cầu xuất hãng bảo trì phiếu #6','2026-09-23 00:45:55'),(195,1,'DUYỆT BẢO TRÍ','Admin đã duyệt Phiếu xuất hãng bảo trì #6','2026-09-23 00:45:57'),(196,1,'HOÀN THÀNH BẢO TRÍ','Đã hoàn thành phiếu bảo trì #6','2026-09-23 00:46:05'),(197,1,'HOÀN THÀNH BẢO TRÍ','Đã hoàn thành phiếu bảo trì #5','2026-09-23 09:03:16'),(198,1,'ĐIỀU CHUYỂN','Đã lập phiếu điều chuyển #3 - Chuyển 1 sản phẩm','2026-09-23 09:08:18'),(199,1,'DUYỆT PHIẾU','Admin đã DUYỆT Phiếu điều chuyển #3','2026-09-23 09:09:42'),(200,2,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-24 00:24:58'),(201,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-24 00:42:19'),(202,1,'XUẤT KHO','Tự động lập phiếu xuất #63 kèm theo hóa đơn #23','2026-09-24 00:51:18'),(203,1,'LẬP HÓA ĐƠN','Đã lập hóa đơn #23 với tổng tiền 25.000.000đ','2026-09-24 00:51:18'),(204,1,'ĐĂNG NHẬP','Đăng nhập vào hệ thống thành công','2026-09-24 01:23:54');
/*!40000 ALTER TABLE `nhatkyhethong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieubaohanh`
--

DROP TABLE IF EXISTS `phieubaohanh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phieubaohanh` (
  `maBaoHanh` int(11) NOT NULL AUTO_INCREMENT,
  `maSerial` int(11) DEFAULT NULL,
  `maNCC` int(11) DEFAULT NULL,
  `maNhanVien` int(11) DEFAULT NULL,
  `ngayGui` datetime DEFAULT NULL,
  `ngayNhan` datetime DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT NULL,
  `moTaLoi` text DEFAULT NULL,
  `ketQua` text DEFAULT NULL,
  PRIMARY KEY (`maBaoHanh`),
  KEY `fk_pbh_ds` (`maSerial`),
  KEY `fk_pbh_ncc` (`maNCC`),
  KEY `fk_pbh_nv` (`maNhanVien`),
  CONSTRAINT `fk_pbh_ds` FOREIGN KEY (`maSerial`) REFERENCES `danserial` (`maSerial`),
  CONSTRAINT `fk_pbh_ncc` FOREIGN KEY (`maNCC`) REFERENCES `nhacungcap` (`maNCC`),
  CONSTRAINT `fk_pbh_nv` FOREIGN KEY (`maNhanVien`) REFERENCES `nhanvien` (`maNhanVien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phieubaohanh`
--

LOCK TABLES `phieubaohanh` WRITE;
/*!40000 ALTER TABLE `phieubaohanh` DISABLE KEYS */;
/*!40000 ALTER TABLE `phieubaohanh` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieubaotri`
--

DROP TABLE IF EXISTS `phieubaotri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phieubaotri` (
  `maPhieuBT` int(11) NOT NULL AUTO_INCREMENT,
  `maHoaDon` int(11) NOT NULL,
  `maKhachHang` int(11) NOT NULL,
  `maSerial` int(11) NOT NULL,
  `maNhanVienLap` int(11) NOT NULL,
  `ngayTiepNhan` datetime NOT NULL,
  `moTaLoi` text DEFAULT NULL,
  `maHang` int(11) DEFAULT NULL,
  `maKho` int(11) DEFAULT NULL,
  `trangThai` enum('Chờ duyệt tiếp nhận','Đã tiếp nhận','Chờ duyệt nhập kho','Đã nhập kho','Chờ duyệt xuất hãng','Đã xuất hãng','Hoàn thành','Đã hủy') NOT NULL DEFAULT 'Chờ duyệt tiếp nhận',
  `ngayCapNhat` datetime DEFAULT NULL,
  PRIMARY KEY (`maPhieuBT`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phieubaotri`
--

LOCK TABLES `phieubaotri` WRITE;
/*!40000 ALTER TABLE `phieubaotri` DISABLE KEYS */;
INSERT INTO `phieubaotri` VALUES (1,6,2,25,1,'2026-08-09 16:54:39','gẫy chân đàn',6,NULL,'Hoàn thành','2026-08-12 12:26:20'),(2,14,4,33,1,'2026-08-16 12:42:25','đàn bị lỗi âm thanh',6,NULL,'Hoàn thành','2026-08-16 12:43:11'),(3,15,5,101,1,'2026-09-09 11:32:57','đàn gãy chan',NULL,NULL,'Đã hủy','2026-09-09 11:33:30'),(4,15,5,101,2,'2026-09-09 11:34:55','bảo hành định kì',7,NULL,'Hoàn thành','2026-09-09 11:40:56'),(5,15,5,21,1,'2026-09-09 11:41:20','hỏng',6,2,'Hoàn thành','2026-09-23 09:03:16'),(6,22,2,97,1,'2026-09-23 00:43:21','bị nuyg',4,1,'Hoàn thành','2026-09-23 00:46:05');
/*!40000 ALTER TABLE `phieubaotri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieudieuchuyen`
--

DROP TABLE IF EXISTS `phieudieuchuyen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phieudieuchuyen` (
  `maPhieuDC` int(11) NOT NULL AUTO_INCREMENT,
  `maKhoXuat` int(11) NOT NULL,
  `maKhoNhap` int(11) NOT NULL,
  `ngayTao` datetime DEFAULT current_timestamp(),
  `maNhanVienLap` int(11) NOT NULL,
  `trangThai` enum('Chờ duyệt','Hoàn thành','Đã hủy') DEFAULT 'Chờ duyệt',
  `ghiChu` text DEFAULT NULL,
  PRIMARY KEY (`maPhieuDC`),
  KEY `maKhoXuat` (`maKhoXuat`),
  KEY `maKhoNhap` (`maKhoNhap`),
  KEY `maNhanVienLap` (`maNhanVienLap`),
  CONSTRAINT `phieudieuchuyen_ibfk_1` FOREIGN KEY (`maKhoXuat`) REFERENCES `kho` (`maKho`),
  CONSTRAINT `phieudieuchuyen_ibfk_2` FOREIGN KEY (`maKhoNhap`) REFERENCES `kho` (`maKho`),
  CONSTRAINT `phieudieuchuyen_ibfk_3` FOREIGN KEY (`maNhanVienLap`) REFERENCES `nhanvien` (`maNhanVien`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phieudieuchuyen`
--

LOCK TABLES `phieudieuchuyen` WRITE;
/*!40000 ALTER TABLE `phieudieuchuyen` DISABLE KEYS */;
INSERT INTO `phieudieuchuyen` VALUES (3,1,2,'2026-09-23 09:08:18',1,'Hoàn thành','');
/*!40000 ALTER TABLE `phieudieuchuyen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieunhap`
--

DROP TABLE IF EXISTS `phieunhap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phieunhap` (
  `maPhieuNhap` int(11) NOT NULL AUTO_INCREMENT,
  `maNCC` int(11) DEFAULT NULL,
  `soHoaDonNCC` varchar(50) DEFAULT NULL,
  `nguoiGiaoHang` varchar(100) DEFAULT NULL,
  `ngayHoaDon` date DEFAULT NULL,
  `ghiChu` text DEFAULT NULL,
  `maNhanVien` int(11) DEFAULT NULL,
  `maKho` int(11) DEFAULT NULL,
  `ngayNhap` datetime DEFAULT current_timestamp(),
  `soLuong` int(11) DEFAULT NULL,
  `trangThai` enum('Chờ duyệt','Hoàn thành','Đã hủy') NOT NULL DEFAULT 'Chờ duyệt',
  `tongTienNhap` decimal(18,2) DEFAULT NULL,
  PRIMARY KEY (`maPhieuNhap`),
  KEY `fk_pn_ncc` (`maNCC`),
  KEY `fk_pn_nv` (`maNhanVien`),
  KEY `idx_pn_ngayNhap` (`ngayNhap`),
  KEY `idx_pn_trangThai` (`trangThai`),
  CONSTRAINT `fk_pn_ncc` FOREIGN KEY (`maNCC`) REFERENCES `nhacungcap` (`maNCC`),
  CONSTRAINT `fk_pn_nv` FOREIGN KEY (`maNhanVien`) REFERENCES `nhanvien` (`maNhanVien`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phieunhap`
--

LOCK TABLES `phieunhap` WRITE;
/*!40000 ALTER TABLE `phieunhap` DISABLE KEYS */;
INSERT INTO `phieunhap` VALUES (1,1,NULL,NULL,NULL,'fdgdfg',1,2,'2026-08-01 23:53:07',1,'Hoàn thành',23323423.00),(2,2,NULL,NULL,NULL,'',1,2,'2026-08-01 23:55:35',1,'Hoàn thành',1500000.00),(3,2,NULL,NULL,NULL,'fdgdfg',1,2,'2026-08-03 12:15:20',1,'Hoàn thành',15555555.00),(4,1,NULL,NULL,NULL,'fdgdfg',1,2,'2026-08-06 15:02:27',1,'Hoàn thành',1500000.00),(5,1,'sdfsadfsd','trung','2026-08-07','sadfsd',1,1,'2026-08-06 15:39:50',1,'Hoàn thành',1500000.00),(6,2,'112312312','tùng dương','2026-08-07','mua hàng',1,2,'2026-08-08 19:37:39',1,'Hoàn thành',10000004.00),(7,2,'12213','nguyeenx đức trung','2026-08-10','công ty n hập hàng',1,2,'2026-08-09 00:09:52',2,'Hoàn thành',16577877.00),(8,1,'12213','nguyeenx đức trung','2026-08-10','mua hàng',1,2,'2026-08-09 13:29:51',1,'Hoàn thành',16660000.00),(9,1,'','tùng dương','2026-08-10','mua hàng',2,2,'2026-08-12 13:58:59',1,'Hoàn thành',20000000.00),(10,1,'','tùng dương','2026-08-18','mua hàng',1,1,'2026-08-17 00:35:54',1,'Hoàn thành',11500000.00),(11,2,'','Lê minh hoàng','2026-09-08','công ty nhập hàng',1,1,'2026-09-09 10:54:36',1,'Hoàn thành',15000000.00);
/*!40000 ALTER TABLE `phieunhap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phieuxuat`
--

DROP TABLE IF EXISTS `phieuxuat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phieuxuat` (
  `maPhieuXuat` int(11) NOT NULL AUTO_INCREMENT,
  `maNhanVien` int(11) DEFAULT NULL,
  `ngayXuat` datetime DEFAULT current_timestamp(),
  `lyDoXuat` varchar(100) DEFAULT NULL,
  `maKho` int(11) DEFAULT NULL,
  `maHoaDon` int(11) DEFAULT NULL,
  `nguoiNhanHang` varchar(100) DEFAULT NULL,
  `soChungTu` varchar(100) DEFAULT NULL,
  `ngayChungTu` date DEFAULT NULL,
  `donViNhan` varchar(200) DEFAULT NULL,
  `trangThai` enum('Chờ duyệt','Hoàn thành','Đã hủy') NOT NULL DEFAULT 'Chờ duyệt',
  PRIMARY KEY (`maPhieuXuat`),
  KEY `fk_px_nv` (`maNhanVien`),
  KEY `fk_px_kho` (`maKho`),
  KEY `fk_px_hoadon` (`maHoaDon`),
  KEY `idx_px_ngayXuat` (`ngayXuat`),
  KEY `idx_px_trangThai` (`trangThai`),
  CONSTRAINT `fk_px_hoadon` FOREIGN KEY (`maHoaDon`) REFERENCES `hoadon` (`maHoaDon`),
  CONSTRAINT `fk_px_kho` FOREIGN KEY (`maKho`) REFERENCES `kho` (`maKho`),
  CONSTRAINT `fk_px_nv` FOREIGN KEY (`maNhanVien`) REFERENCES `nhanvien` (`maNhanVien`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phieuxuat`
--

LOCK TABLES `phieuxuat` WRITE;
/*!40000 ALTER TABLE `phieuxuat` DISABLE KEYS */;
INSERT INTO `phieuxuat` VALUES (2,3,'2026-07-06 23:50:16','Xu?t bßn hÓng theo h¾a don #3',1,3,NULL,NULL,NULL,NULL,''),(3,1,'2026-07-06 23:53:15','fsdfsdf',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(4,1,'2026-07-06 23:55:13','g',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(5,1,'2026-07-06 23:58:34','fsdfsdf',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(7,1,'2026-07-06 23:59:53','trungxoiddofi  x',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(10,1,'2026-07-07 00:04:30','dfsfsd',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(12,1,'2026-07-07 00:05:23','sdaff',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(16,2,'2026-08-03 11:43:42','trungxoi đòi xuất',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(17,1,'2026-08-03 12:13:51','g',2,4,NULL,NULL,NULL,NULL,'Hoàn thành'),(18,2,'2026-08-03 13:00:48','bán hàng',2,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(19,1,'2026-08-06 15:04:47','xxz',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(20,1,'2026-08-06 15:37:46','xxz',1,NULL,NULL,NULL,NULL,NULL,'Hoàn thành'),(21,1,'2026-08-06 15:55:24','bán hàng',1,8,'duong tút','','2026-08-09','nhà riêng','Hoàn thành'),(30,1,'2026-08-09 14:31:03','a',2,13,'s','s','2026-08-09','s','Hoàn thành'),(31,1,'2026-08-09 14:45:26','ban hang',1,14,'tủng xoi','12232','2026-08-10','d','Hoàn thành'),(35,1,'2026-08-16 12:57:25','ban hang',2,10,'tung','','2026-08-16','d','Hoàn thành'),(40,2,'2026-08-17 01:01:59','bán hàng',1,20,'','',NULL,'','Chờ duyệt'),(42,1,'2026-08-17 01:03:06','ban hang',2,19,'','','2026-09-02','','Chờ duyệt'),(54,1,'2026-08-21 13:21:52','Xuất hàng theo hóa đơn số #15',1,15,'nguyễn quang minh','HĐ15','2026-08-12','công ty nguyễn minh','Chờ duyệt'),(55,1,'2026-08-21 13:30:56','Xuất hàng theo hóa đơn số #18',2,18,'nguyễn đức trung','HĐ18','2026-08-12','','Chờ duyệt'),(56,1,'2026-08-21 13:45:44','Xuất hàng theo hóa đơn số #17',NULL,17,'nguyễn quang minh','HĐ17','2026-08-12','','Chờ duyệt'),(57,1,'2026-08-21 13:48:49','Xuất hàng theo hóa đơn số #6',NULL,6,'Đặng Văn Khách','HĐ6','2026-08-06','','Chờ duyệt'),(58,1,'2026-08-21 13:52:36','Xuất hàng theo hóa đơn số #11',NULL,11,'Khách lẻ vãng lai','HĐ11','2026-08-08','trung xoi','Chờ duyệt'),(60,1,'2026-09-04 14:22:37','Xuất hàng theo hóa đơn số #12',NULL,12,'Đặng Văn Khách','HĐ12','2026-08-08','trung xoi','Chờ duyệt'),(61,1,'2026-09-09 11:02:57','Xuất bán theo hóa đơn #22',NULL,22,'Đặng Văn Khách','HĐ22',NULL,NULL,'Đã hủy'),(62,1,'2026-09-09 11:15:16','Xuất hàng theo hóa đơn số #22',NULL,22,'Đặng Văn Khách','HĐ22','2026-09-09','','Chờ duyệt'),(63,1,'2026-09-24 00:51:18','Xuất bán theo hóa đơn #23',NULL,23,'Đặng Văn Khách','HĐ23',NULL,NULL,'Chờ duyệt');
/*!40000 ALTER TABLE `phieuxuat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taikhoan`
--

DROP TABLE IF EXISTS `taikhoan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taikhoan` (
  `maTaiKhoan` int(11) NOT NULL AUTO_INCREMENT,
  `tenDangNhap` varchar(50) NOT NULL,
  `matKhau` varchar(255) NOT NULL,
  `maNhanVien` int(11) DEFAULT NULL,
  `maVaiTro` int(11) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT 'Hoạt động',
  `ngayTao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`maTaiKhoan`),
  UNIQUE KEY `tenDangNhap` (`tenDangNhap`),
  KEY `fk_tk_nv` (`maNhanVien`),
  KEY `fk_tk_vt` (`maVaiTro`),
  CONSTRAINT `fk_tk_nv` FOREIGN KEY (`maNhanVien`) REFERENCES `nhanvien` (`maNhanVien`),
  CONSTRAINT `fk_tk_vt` FOREIGN KEY (`maVaiTro`) REFERENCES `vaitro` (`maVaiTro`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taikhoan`
--

LOCK TABLES `taikhoan` WRITE;
/*!40000 ALTER TABLE `taikhoan` DISABLE KEYS */;
INSERT INTO `taikhoan` VALUES (1,'admin','1',1,1,'Hoạt động','2026-04-22 10:48:39'),(2,'thukho','1',2,2,'Hoạt động','2026-04-22 10:48:39'),(4,'thukho2','1',4,2,'Hoạt động','2026-08-12 12:04:49'),(5,'thukho3','1',5,2,'Hoạt động','2026-08-12 12:09:22');
/*!40000 ALTER TABLE `taikhoan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thongbao`
--

DROP TABLE IF EXISTS `thongbao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thongbao` (
  `maThongBao` int(11) NOT NULL AUTO_INCREMENT,
  `maTaiKhoan` int(11) DEFAULT NULL,
  `maVaiTro` int(11) DEFAULT NULL,
  `noiDung` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `daDoc` tinyint(4) DEFAULT 0,
  `ngayTao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`maThongBao`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thongbao`
--

LOCK TABLES `thongbao` WRITE;
/*!40000 ALTER TABLE `thongbao` DISABLE KEYS */;
INSERT INTO `thongbao` VALUES (1,1,NULL,'Xuất kho thành công! Phiếu #5 - 1 sản phẩm đã xuất','duyet_phieu.php',0,'2026-07-06 23:58:34'),(2,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #5','duyet_phieu.php',0,'2026-07-06 23:58:34'),(3,1,NULL,'Xuất kho thành công! Phiếu #7 - 1 sản phẩm đã xuất','duyet_phieu.php',0,'2026-07-06 23:59:53'),(4,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #7','duyet_phieu.php',0,'2026-07-06 23:59:53'),(5,1,NULL,'Xuất kho thành công! Phiếu #10 - 1 sản phẩm đã xuất','duyet_phieu.php',0,'2026-07-07 00:04:30'),(6,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #10','duyet_phieu.php',0,'2026-07-07 00:04:30'),(7,1,NULL,'Xuất kho thành công! Phiếu #12 - 1 sản phẩm đã xuất','duyet_phieu.php',0,'2026-07-07 00:05:23'),(8,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #12','duyet_phieu.php',0,'2026-07-07 00:05:23'),(9,1,NULL,'Nhập kho thành công! Phiếu #1 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-01 23:53:07'),(10,1,NULL,'Nhập kho thành công! Phiếu #2 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-01 23:55:35'),(11,2,NULL,'Xuất kho thành công! Phiếu #16 - 1 sản phẩm đã xuất','phieuxuat.php',0,'2026-08-03 11:43:42'),(12,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #16','duyet_phieu.php',0,'2026-08-03 11:43:42'),(13,1,NULL,'Xuất kho thành công! Phiếu #17 - 1 sản phẩm đã xuất','duyet_phieu.php',0,'2026-08-03 12:13:51'),(14,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #17','duyet_phieu.php',0,'2026-08-03 12:13:51'),(15,1,NULL,'Nhập kho thành công! Phiếu #3 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-03 12:15:20'),(16,2,NULL,'Xuất kho thành công! Phiếu #18 - 1 sản phẩm đã xuất','phieuxuat.php',0,'2026-08-03 13:00:48'),(17,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #18','duyet_phieu.php',0,'2026-08-03 13:00:48'),(18,1,NULL,'Nhập kho thành công! Phiếu #4 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-06 15:02:27'),(19,1,NULL,'Xuất kho thành công! Phiếu #19 - 1 sản phẩm đã xuất','duyet_phieu.php',0,'2026-08-06 15:04:47'),(20,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #19','duyet_phieu.php',0,'2026-08-06 15:04:47'),(21,1,NULL,'Xuất kho thành công! Phiếu #20 - 1 sản phẩm đã xuất','duyet_phieu.php',0,'2026-08-06 15:37:46'),(22,NULL,1,'Thủ kho đã xuất 1 sản phẩm - Phiếu xuất #20','duyet_phieu.php',0,'2026-08-06 15:37:46'),(23,1,NULL,'Nhập kho thành công! Phiếu #5 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-06 15:39:50'),(24,1,NULL,'Xuất kho thành công! Phiếu #21 - 2 sản phẩm đã xuất','duyet_phieu.php',0,'2026-08-06 15:55:24'),(25,NULL,1,'Thủ kho đã xuất 2 sản phẩm - Phiếu xuất #21','duyet_phieu.php',0,'2026-08-06 15:55:24'),(26,1,NULL,'Đã lập hóa đơn thành công #11','hoadon_action.php?id=11',0,'2026-08-08 19:34:33'),(27,NULL,3,'Nhập đơn xuất cho hóa đơn #11','phieuxuat.php',0,'2026-08-08 19:34:33'),(28,NULL,1,'Hóa đơn mới được lập #11','hoadon_moi.php',0,'2026-08-08 19:34:33'),(29,1,NULL,'Nhập kho thành công! Phiếu #6 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-08 19:37:39'),(30,1,NULL,'Đã lập hóa đơn thành công #12','hoadon_action.php?id=12',0,'2026-08-08 20:09:17'),(31,NULL,3,'Nhập đơn xuất cho hóa đơn #12','hoadon_action.php?id=12',0,'2026-08-08 20:09:17'),(32,NULL,1,'Hóa đơn mới được lập #12','hoadon_action.php?id=12',0,'2026-08-08 20:09:17'),(33,1,NULL,'Lập phiếu nhập thành công (Chờ duyệt)! Phiếu #7 - 2 sản phẩm','duyet_phieu.php',0,'2026-08-09 00:09:52'),(34,1,NULL,'Đã lập hóa đơn thành công #13','hoadon_action.php?id=13',0,'2026-08-09 13:18:13'),(35,NULL,3,'Nhập đơn xuất cho hóa đơn #13','hoadon_action.php?id=13',0,'2026-08-09 13:18:13'),(36,NULL,1,'Hóa đơn mới được lập #13','hoadon_action.php?id=13',0,'2026-08-09 13:18:13'),(37,1,NULL,'Lập phiếu nhập thành công (Chờ duyệt)! Phiếu #8 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-09 13:29:51'),(38,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #30 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-09 14:31:03'),(39,NULL,1,'Có phiếu xuất kho mới #30 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-09 14:31:03'),(40,1,NULL,'Đã lập hóa đơn thành công #14','hoadon_action.php?id=14',0,'2026-08-09 14:45:11'),(41,NULL,3,'Nhập đơn xuất cho hóa đơn #14','hoadon_action.php?id=14',0,'2026-08-09 14:45:11'),(42,NULL,1,'Hóa đơn mới được lập #14','hoadon_action.php?id=14',0,'2026-08-09 14:45:11'),(43,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #31 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-09 14:45:26'),(44,NULL,1,'Có phiếu xuất kho mới #31 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-09 14:45:26'),(45,1,NULL,'Đã lập hóa đơn thành công #15','hoadon_action.php?id=15',0,'2026-08-12 11:39:45'),(46,NULL,3,'Nhập đơn xuất cho hóa đơn #15','hoadon_action.php?id=15',0,'2026-08-12 11:39:45'),(47,NULL,1,'Hóa đơn mới được lập #15','hoadon_action.php?id=15',0,'2026-08-12 11:39:45'),(48,NULL,1,'Có một yêu cầu đăng ký tài khoản mới từ nguyen quang minh.','quanly_taikhoan.php',0,'2026-08-12 12:04:49'),(49,NULL,1,'Có một yêu cầu đăng ký tài khoản mới từ Trung Nguyễn.','quanly_taikhoan.php',0,'2026-08-12 12:09:22'),(50,1,NULL,'Đã lập hóa đơn thành công #17','hoadon_action.php?id=17',0,'2026-08-12 13:54:41'),(51,NULL,3,'Nhập đơn xuất cho hóa đơn #17','hoadon_action.php?id=17',0,'2026-08-12 13:54:41'),(52,NULL,1,'Hóa đơn mới được lập #17','hoadon_action.php?id=17',0,'2026-08-12 13:54:41'),(53,2,NULL,'Đã lập hóa đơn thành công #18','hoadon_action.php?id=18',0,'2026-08-12 13:57:16'),(54,NULL,3,'Nhập đơn xuất cho hóa đơn #18','hoadon_action.php?id=18',0,'2026-08-12 13:57:16'),(55,NULL,1,'Hóa đơn mới được lập #18','hoadon_action.php?id=18',0,'2026-08-12 13:57:16'),(56,2,NULL,'Lập phiếu nhập thành công (Chờ duyệt)! Phiếu #9 - 1 sản phẩm','phieunhap.php',0,'2026-08-12 13:58:59'),(57,NULL,1,'Có phiếu nhập kho mới #9 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-12 13:58:59'),(58,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #35 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-16 12:57:25'),(59,NULL,1,'Có phiếu xuất kho mới #35 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-16 12:57:25'),(60,1,NULL,'Lập phiếu nhập thành công (Chờ duyệt)! Phiếu #10 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-17 00:35:54'),(61,2,NULL,'Đã lập hóa đơn thành công #19','hoadon_action.php?id=19',0,'2026-08-17 00:46:49'),(62,NULL,3,'Nhập đơn xuất cho hóa đơn #19','hoadon_action.php?id=19',0,'2026-08-17 00:46:49'),(63,NULL,1,'Hóa đơn mới được lập #19','hoadon_action.php?id=19',0,'2026-08-17 00:46:49'),(64,2,NULL,'Đã lập hóa đơn thành công #20','hoadon_action.php?id=20',0,'2026-08-17 00:58:23'),(65,NULL,3,'Nhập đơn xuất cho hóa đơn #20','hoadon_action.php?id=20',0,'2026-08-17 00:58:23'),(66,NULL,1,'Hóa đơn mới được lập #20','hoadon_action.php?id=20',0,'2026-08-17 00:58:23'),(67,2,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #40 - 1 sản phẩm','phieuxuat.php',0,'2026-08-17 01:01:59'),(68,NULL,1,'Có phiếu xuất kho mới #40 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-17 01:01:59'),(69,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #42 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-17 01:03:06'),(70,NULL,1,'Có phiếu xuất kho mới #42 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-17 01:03:06'),(71,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #54 - 2 sản phẩm','duyet_phieu.php',0,'2026-08-21 13:21:52'),(72,NULL,1,'Có phiếu xuất kho mới #54 cần được phê duyệt (2 SP)','duyet_phieu.php',0,'2026-08-21 13:21:52'),(73,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #55 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-21 13:30:56'),(74,NULL,1,'Có phiếu xuất kho mới #55 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-21 13:30:56'),(75,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #56 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-21 13:45:44'),(76,NULL,1,'Có phiếu xuất kho mới #56 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-21 13:45:44'),(77,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #57 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-21 13:48:50'),(78,NULL,1,'Có phiếu xuất kho mới #57 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-21 13:48:50'),(79,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #58 - 1 sản phẩm','duyet_phieu.php',0,'2026-08-21 13:52:36'),(80,NULL,1,'Có phiếu xuất kho mới #58 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-08-21 13:52:36'),(81,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #60 - 1 sản phẩm','duyet_phieu.php',0,'2026-09-04 14:22:37'),(82,NULL,1,'Có phiếu xuất kho mới #60 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-09-04 14:22:37'),(83,1,NULL,'Lập phiếu nhập thành công (Chờ duyệt)! Phiếu #11 - 1 sản phẩm','duyet_phieu.php',0,'2026-09-09 10:54:36'),(84,1,NULL,'Đã lập hóa đơn thành công #22','hoadon_action.php?id=22',0,'2026-09-09 11:02:57'),(85,NULL,1,'Hóa đơn #22 và Phiếu xuất #61 đang chờ duyệt.','duyet_phieu.php',0,'2026-09-09 11:02:57'),(86,1,NULL,'Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #62 - 1 sản phẩm','duyet_phieu.php',0,'2026-09-09 11:15:16'),(87,NULL,1,'Có phiếu xuất kho mới #62 cần được phê duyệt (1 SP)','duyet_phieu.php',0,'2026-09-09 11:15:16'),(88,1,NULL,'Lập phiếu điều chuyển thành công (Chờ duyệt)! Phiếu #3 - 1 SP','duyet_phieu.php',0,'2026-09-23 09:08:18'),(89,NULL,1,'Có phiếu điều chuyển mới #3 cần phê duyệt (1 SP)','duyet_phieu.php',0,'2026-09-23 09:08:18'),(90,1,NULL,'Đã lập hóa đơn thành công #23','hoadon_action.php?id=23',0,'2026-09-24 00:51:18'),(91,NULL,1,'Hóa đơn #23 và Phiếu xuất #63 đang chờ duyệt.','duyet_phieu.php',0,'2026-09-24 00:51:18');
/*!40000 ALTER TABLE `thongbao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaitro`
--

DROP TABLE IF EXISTS `vaitro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vaitro` (
  `maVaiTro` int(11) NOT NULL AUTO_INCREMENT,
  `tenVaiTro` varchar(50) NOT NULL,
  PRIMARY KEY (`maVaiTro`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaitro`
--

LOCK TABLES `vaitro` WRITE;
/*!40000 ALTER TABLE `vaitro` DISABLE KEYS */;
INSERT INTO `vaitro` VALUES (1,'Quản trị viên'),(2,'Nhân viên Thủ kho'),(3,'Nhân viên Bán hàng');
/*!40000 ALTER TABLE `vaitro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vieccanlam`
--

DROP TABLE IF EXISTS `vieccanlam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vieccanlam` (
  `maViec` int(11) NOT NULL AUTO_INCREMENT,
  `maTaiKhoan` int(11) NOT NULL,
  `noiDung` varchar(255) NOT NULL,
  `trangThai` tinyint(1) DEFAULT 0,
  `ngayTao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`maViec`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vieccanlam`
--

LOCK TABLES `vieccanlam` WRITE;
/*!40000 ALTER TABLE `vieccanlam` DISABLE KEYS */;
INSERT INTO `vieccanlam` VALUES (1,1,'xuất hóa đơn',1,'2026-08-25 12:41:26');
/*!40000 ALTER TABLE `vieccanlam` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-24  1:51:28
