-- Insert Brands
INSERT INTO hangdan (tenHang, xuatXu) VALUES ('Gibson', 'Mỹ');
INSERT INTO hangdan (tenHang, xuatXu) VALUES ('Ibanez', 'Nhật Bản');

-- Insert Models
INSERT INTO maudan (tenMau, maLoai, maHang, moTa) VALUES ('Gibson Les Paul Standard', 3, (SELECT maHang FROM hangdan WHERE tenHang='Gibson'), 'Huyền thoại nhạc Rock');
INSERT INTO maudan (tenMau, maLoai, maHang, moTa) VALUES ('Ibanez RG550', 3, (SELECT maHang FROM hangdan WHERE tenHang='Ibanez'), 'Đàn dành cho shredder');
INSERT INTO maudan (tenMau, maLoai, maHang, moTa) VALUES ('Taylor 114ce', 1, 2, 'Acoustic Guitar cao cấp');
INSERT INTO maudan (tenMau, maLoai, maHang, moTa) VALUES ('Cordoba C5', 2, 4, 'Classic Guitar chuẩn mực');

-- Insert Serials (Inventory)
INSERT INTO danserial (soSerial, maMau, maKho, maNCC, tinhTrang, trangThai, giaNhap, giaBan) VALUES 
('GIB-LP-001', (SELECT maMau FROM maudan WHERE tenMau='Gibson Les Paul Standard'), 1, 2, 'Mới 100%', 'Trong kho', 40000000, 50000000),
('GIB-LP-002', (SELECT maMau FROM maudan WHERE tenMau='Gibson Les Paul Standard'), 1, 2, 'Mới 100%', 'Trong kho', 40000000, 50000000),
('IBA-RG-001', (SELECT maMau FROM maudan WHERE tenMau='Ibanez RG550'), 1, 2, 'Mới 100%', 'Trong kho', 20000000, 25000000),
('IBA-RG-002', (SELECT maMau FROM maudan WHERE tenMau='Ibanez RG550'), 2, 2, 'Mới 100%', 'Trong kho', 20000000, 25000000),
('TAY-114-001', (SELECT maMau FROM maudan WHERE tenMau='Taylor 114ce'), 1, 1, 'Mới 100%', 'Trong kho', 15000000, 18000000),
('TAY-114-002', (SELECT maMau FROM maudan WHERE tenMau='Taylor 114ce'), 2, 1, 'Mới 100%', 'Trong kho', 15000000, 18000000),
('COR-C5-001', (SELECT maMau FROM maudan WHERE tenMau='Cordoba C5'), 1, 2, 'Mới 100%', 'Trong kho', 6000000, 8000000),
('COR-C5-002', (SELECT maMau FROM maudan WHERE tenMau='Cordoba C5'), 2, 2, 'Mới 100%', 'Trong kho', 6000000, 8000000);
