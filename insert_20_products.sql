INSERT INTO loaidan (tenLoai) VALUES ('Grand Piano');
INSERT INTO loaidan (tenLoai) VALUES ('Upright Piano');
INSERT INTO hangdan (tenHang, xuatXu) VALUES ('Martin', 'Mỹ');

INSERT INTO maudan (tenMau, maLoai, maHang, moTa, hinhAnh) VALUES 
('Yamaha CFX Grand Piano', (SELECT maLoai FROM loaidan WHERE tenLoai='Grand Piano' LIMIT 1), 1, 'Đàn Grand Piano cao cấp dành cho biểu diễn chuyên nghiệp.', 'grand_piano.png'),
('Kawai K-300', (SELECT maLoai FROM loaidan WHERE tenLoai='Upright Piano' LIMIT 1), 6, 'Đàn Upright Piano chuẩn mực cho luyện tập và giảng dạy.', 'upright_piano.png'),
('Fender Stratocaster Player', 3, 3, 'Cây guitar điện huyền thoại với âm thanh linh hoạt, chuẩn mực.', 'electric_guitar.png'),
('Martin D-28', 1, (SELECT maHang FROM hangdan WHERE tenHang='Martin' LIMIT 1), 'Huyền thoại Acoustic Guitar, âm bass trầm ấm uy lực.', 'acoustic_guitar.png'),
('Fender Jazz Bass', 4, 3, 'Bass guitar tiêu chuẩn cho các tay chơi chuyên nghiệp.', 'bass_guitar.png');

INSERT INTO danserial (soSerial, maMau, maKho, maNCC, tinhTrang, trangThai, giaNhap, giaBan) VALUES 
('YAM-CFX-001', (SELECT maMau FROM maudan WHERE tenMau='Yamaha CFX Grand Piano' LIMIT 1), 1, 1, 'Mới 100%', 'Trong kho', 300000000, 350000000),
('YAM-CFX-002', (SELECT maMau FROM maudan WHERE tenMau='Yamaha CFX Grand Piano' LIMIT 1), 1, 1, 'Mới 100%', 'Trong kho', 300000000, 350000000),
('YAM-CFX-003', (SELECT maMau FROM maudan WHERE tenMau='Yamaha CFX Grand Piano' LIMIT 1), 2, 1, 'Mới 100%', 'Trong kho', 300000000, 350000000),
('YAM-CFX-004', (SELECT maMau FROM maudan WHERE tenMau='Yamaha CFX Grand Piano' LIMIT 1), 2, 1, 'Mới 100%', 'Trong kho', 300000000, 350000000),

('KAW-K300-001', (SELECT maMau FROM maudan WHERE tenMau='Kawai K-300' LIMIT 1), 1, 2, 'Mới 100%', 'Trong kho', 120000000, 140000000),
('KAW-K300-002', (SELECT maMau FROM maudan WHERE tenMau='Kawai K-300' LIMIT 1), 1, 2, 'Mới 100%', 'Trong kho', 120000000, 140000000),
('KAW-K300-003', (SELECT maMau FROM maudan WHERE tenMau='Kawai K-300' LIMIT 1), 2, 2, 'Mới 100%', 'Trong kho', 120000000, 140000000),
('KAW-K300-004', (SELECT maMau FROM maudan WHERE tenMau='Kawai K-300' LIMIT 1), 2, 2, 'Mới 100%', 'Trong kho', 120000000, 140000000),

('FEN-STRAT-001', (SELECT maMau FROM maudan WHERE tenMau='Fender Stratocaster Player' LIMIT 1), 1, 2, 'Mới 100%', 'Trong kho', 18000000, 22000000),
('FEN-STRAT-002', (SELECT maMau FROM maudan WHERE tenMau='Fender Stratocaster Player' LIMIT 1), 1, 2, 'Mới 100%', 'Trong kho', 18000000, 22000000),
('FEN-STRAT-003', (SELECT maMau FROM maudan WHERE tenMau='Fender Stratocaster Player' LIMIT 1), 2, 2, 'Mới 100%', 'Trong kho', 18000000, 22000000),
('FEN-STRAT-004', (SELECT maMau FROM maudan WHERE tenMau='Fender Stratocaster Player' LIMIT 1), 2, 2, 'Mới 100%', 'Trong kho', 18000000, 22000000),

('MAR-D28-001', (SELECT maMau FROM maudan WHERE tenMau='Martin D-28' LIMIT 1), 1, 1, 'Mới 100%', 'Trong kho', 70000000, 85000000),
('MAR-D28-002', (SELECT maMau FROM maudan WHERE tenMau='Martin D-28' LIMIT 1), 1, 1, 'Mới 100%', 'Trong kho', 70000000, 85000000),
('MAR-D28-003', (SELECT maMau FROM maudan WHERE tenMau='Martin D-28' LIMIT 1), 2, 1, 'Mới 100%', 'Trong kho', 70000000, 85000000),
('MAR-D28-004', (SELECT maMau FROM maudan WHERE tenMau='Martin D-28' LIMIT 1), 2, 1, 'Mới 100%', 'Trong kho', 70000000, 85000000),

('FEN-JBASS-001', (SELECT maMau FROM maudan WHERE tenMau='Fender Jazz Bass' LIMIT 1), 1, 2, 'Mới 100%', 'Trong kho', 20000000, 25000000),
('FEN-JBASS-002', (SELECT maMau FROM maudan WHERE tenMau='Fender Jazz Bass' LIMIT 1), 1, 2, 'Mới 100%', 'Trong kho', 20000000, 25000000),
('FEN-JBASS-003', (SELECT maMau FROM maudan WHERE tenMau='Fender Jazz Bass' LIMIT 1), 2, 2, 'Mới 100%', 'Trong kho', 20000000, 25000000),
('FEN-JBASS-004', (SELECT maMau FROM maudan WHERE tenMau='Fender Jazz Bass' LIMIT 1), 2, 2, 'Mới 100%', 'Trong kho', 20000000, 25000000);
