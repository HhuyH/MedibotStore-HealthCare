----------------------------------------------USERS----------------------------------------------------------------------------------------------------------------
INSERT INTO users (username, email, password_hash, role_id, created_at)
VALUES
('admin', 'admin@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 1
 1, NOW()),

('huy', 'hoanhuy12@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 2
 1, NOW()),

('dr.hanh', 'docter@example.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 3
 2, NOW());

('nguyenvana', 'vana@example.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 4
 3, NOW());

('linh', 'linh@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC',--id 6
 2, NOW()), 

-- Thêm vài tài khoản cho bác sĩ
INSERT INTO users (username, email, password, role_id, created_at)
VALUES
('dr.huong', 'huong.derma@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 2, NOW()), -- id 7

('dr.khoa', 'khoa.neuro@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 2, NOW()), -- id 8

('dr.trang', 'trang.pedia@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 2, NOW()), -- id 9

('dr.long', 'long.surgery@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 2, NOW()), -- id 10

('dr.ha', 'ha.cardiology@gmail.com',
 '$2b$12$KIX9W96S6PvuYcM1vHtrKuu6LSDuCMUCylKBD8eEkF2ZQDfMBzJwC', 2, NOW()); -- id 11


----------------------------------------------GUEST_USERS----------------------------------------------------------------------------------------------------------------
INSERT INTO guest_users (full_name, phone, email)
VALUES
('Nguyễn Văn A', '0909123456', 'nva@example.com'),
('Trần Thị B', '0911234567', 'ttb@example.com'),
('Lê Văn C', '0922345678', 'lvc@example.com');

----------------------------------------------USERS_info----------------------------------------------------------------------------------------------------------------
INSERT INTO users_info (user_id, full_name, gender, date_of_birth, phone)
VALUES
(1, 'Quản trị viên', 'Nam', '1990-01-01', '0123456789'),
(2, 'Huy', 'Nam', '1985-06-15','0999999999'),
(3, 'Dr.Hand', 'nữ', '2000-12-01', '0888888888');
(4, 'Nguyễn Văn A', 'Nam', '1995-08-15', '0901234567');
(6, 'Dr.Linh', 'Nữ', '1995-08-15', '0123466789');

INSERT INTO users_info (user_id, full_name, gender, date_of_birth, phone)
VALUES
(7, 'BS. Nguyễn Thị Hương', 'Nữ', '1980-05-12', '0912345678'),
(8, 'BS. Trần Văn Khoa', 'Nam', '1978-09-20', '0987654321'),
(9, 'BS. Lê Thị Trang', 'Nữ', '1985-11-03', '0901122334'),
(10, 'BS. Phạm Văn Long', 'Nam', '1975-02-18', '0933445566'),
(11, 'BS. Đỗ Thị Hà', 'Nữ', '1982-07-25', '0977554433');


----------------------------------------------USERS_ADDRESSES----------------------------------------------------------------------------------------------------------------
INSERT INTO user_addresses (
    user_id, address_line, ward, district, city, postal_code, country, is_default
)
VALUES
-- Quản trị viên (user_id = 1)
(1, '123 Trần Hưng Đạo', 'Nguyễn Cư Trinh', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', TRUE),

-- Hòa Huy (user_id = 2)
(2, '456 Lê Lợi', 'Bến Nghé', 'Quận 1', 'TP.HCM', '700000', 'Vietnam', TRUE),
(2, '111 Đường long', 'Bến Nghé', 'Quận 11', 'TP.HCM', '110000', 'Vietnam', TRUE),

-- John Doe (user_id = 3)
(3, '789 Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '700000', 'Vietnam', TRUE);

-- Nguyễn văn A (user_id=4)
(4, '123 Đường Lý Thường Kiệt', 'Phường 7', 'Quận 10', 'TP.HCM', '70000', TRUE);

-------------------------------------------------------medical_categories--------------------------------------------------------------------------------------------------------------
INSERT INTO medical_categories (name, description) VALUES
('Tim mạch', 'Chuyên khoa liên quan đến tim và mạch máu'),
('Hô hấp', 'Chuyên khoa về phổi và hệ hô hấp'),
('Tiêu hóa', 'Chuyên khoa về dạ dày, ruột, gan...'),
('Thần kinh', 'Chuyên khoa về não và hệ thần kinh'),
('Da liễu', 'Chuyên khoa về da, tóc và móng');


-------------------------------------------------------diseases--------------------------------------------------------------------------------------------------------------
INSERT INTO diseases (disease_id, name, description)
VALUES (-1, 'Chưa rõ', 'Dự đoán từ GPT nhưng chưa có trong cơ sở dữ liệu');

INSERT INTO diseases (name, description, treatment_guidelines, category_id, severity) VALUES
('Tăng huyết áp', 'Huyết áp cao mãn tính', 'Theo dõi huyết áp thường xuyên, dùng thuốc hạ áp', 1, 'trung bình'), --1
('Đột quỵ', 'Rối loạn tuần hoàn não nghiêm trọng', 'Can thiệp y tế khẩn cấp, phục hồi chức năng', 1, 'nghiêm trọng'), --2
('Hen suyễn', 'Bệnh mãn tính ảnh hưởng đến đường thở', 'Sử dụng thuốc giãn phế quản và kiểm soát dị ứng', 2, 'trung bình'), --3
('Viêm phổi', 'Nhiễm trùng phổi do vi khuẩn hoặc virus', 'Kháng sinh, nghỉ ngơi và điều trị hỗ trợ', 2, 'nghiêm trọng'), --4
('Viêm dạ dày', 'Viêm lớp niêm mạc dạ dày', 'Tránh thức ăn cay, dùng thuốc kháng acid', 3, 'nhẹ'), --5
('Xơ gan', 'Tổn thương gan mạn tính', 'Kiểm soát nguyên nhân, chế độ ăn và theo dõi y tế', 3, 'nghiêm trọng'), --6
('Động kinh', 'Rối loạn thần kinh gây co giật lặp lại', 'Dùng thuốc chống động kinh, theo dõi điện não đồ', 4, 'nghiêm trọng'), --7
('Trầm cảm', 'Rối loạn tâm trạng kéo dài', 'Liệu pháp tâm lý và thuốc chống trầm cảm', 4, 'trung bình'), --8
('Viêm da cơ địa', 'Bệnh da mãn tính gây ngứa và phát ban', 'Dưỡng ẩm, thuốc bôi chống viêm', 5, 'nhẹ'), --9
('Nấm da', 'Nhiễm trùng da do nấm', 'Thuốc kháng nấm dạng bôi hoặc uống', 5, 'nhẹ'), --10
('Viêm đa cơ', 'Bệnh tự miễn ảnh hưởng đến cơ', 'Dùng thuốc ức chế miễn dịch, vật lý trị liệu', 4, 'trung bình'), --11
('Tiểu đường tuýp 2', 'Tình trạng rối loạn chuyển hóa đường máu mạn tính', 'Kiểm soát chế độ ăn, tập luyện, dùng thuốc hạ đường huyết', 1, 'trung bình'), --12
('Suy tim', 'Tình trạng tim không bơm đủ máu cho cơ thể', 'Dùng thuốc lợi tiểu, ức chế men chuyển, theo dõi sát', 1, 'nghiêm trọng'), --13
('Viêm phế quản', 'Tình trạng viêm đường thở lớn (phế quản)', 'Nghỉ ngơi, dùng thuốc giảm viêm và long đờm', 2, 'trung bình'), --14
('Viêm họng cấp', 'Viêm niêm mạc họng do virus hoặc vi khuẩn', 'Súc miệng nước muối, thuốc giảm đau, kháng sinh nếu cần', 2, 'nhẹ'), --15
('Loét dạ dày tá tràng', 'Tổn thương niêm mạc dạ dày hoặc tá tràng', 'Thuốc ức chế acid, tránh rượu bia, stress', 3, 'trung bình'), --16
('Viêm gan B mạn tính', 'Nhiễm HBV kéo dài, gây tổn thương gan', 'Theo dõi chức năng gan, dùng thuốc kháng virus nếu cần', 3, 'trung bình'), --17
('Thiếu máu', 'Giảm số lượng hồng cầu hoặc hemoglobin', 'Bổ sung sắt, acid folic hoặc điều trị nguyên nhân nền', 1, 'nhẹ'), --18
('Gút', 'Tình trạng viêm khớp do tinh thể urat', 'Dùng colchicine, allopurinol, hạn chế đạm', 4, 'trung bình'), --19
('Viêm khớp dạng thấp', 'Bệnh tự miễn gây viêm nhiều khớp', 'Dùng DMARDs, thuốc chống viêm và vật lý trị liệu', 4, 'nghiêm trọng'), --20
('Trào ngược dạ dày thực quản', 'Dịch dạ dày trào lên thực quản gây kích ứng', 'Nâng đầu giường, hạn chế ăn đêm, dùng thuốc PPI', 3, 'nhẹ'), --21
('Rối loạn lo âu', 'Tình trạng tâm lý gây lo lắng kéo dài', 'Liệu pháp hành vi nhận thức, thuốc chống lo âu', 4, 'trung bình'), --22
('Cảm cúm', 'Nhiễm virus cúm gây mệt, sốt, đau họng', 'Nghỉ ngơi, hạ sốt, uống nhiều nước', 2, 'nhẹ'), --23
('Đau thần kinh tọa', 'Đau do chèn ép dây thần kinh hông lớn', 'Dùng thuốc giảm đau, vật lý trị liệu, nghỉ ngơi', 4, 'trung bình'), --24
('Viêm kết mạc', 'Viêm màng mắt ngoài do vi khuẩn, virus hoặc dị ứng', 'Thuốc nhỏ mắt kháng sinh hoặc chống dị ứng', 5, 'nhẹ'), --25
('Chàm (eczema)', 'Bệnh da mãn tính gây ngứa, khô và viêm', 'Dưỡng ẩm, thuốc bôi corticoid, tránh dị nguyên', 5, 'nhẹ'); --26

-------------------------------------------------------symptoms--------------------------------------------------------------------------------------------------------------
-- Lưu ý khi thêm dữ liệu followup_question để không nhắc đến triệu chứng khác. và nếu triệu chứng là 1 dạng chung chung thì tách ra từng loại chi tiết

INSERT INTO symptoms (name, alias, description, followup_question) VALUES
('Đau đầu', 'đau đầu,căng đầu,nhức đầu', 'Cảm giác đau ở vùng đầu hoặc cổ', 'Cơn đau đầu xuất hiện vào lúc nào trong ngày (sáng, trưa, tối)? Mức độ đau từ nhẹ đến dữ dội ra sao?'), -- 1
('Khó thở', 'khó hít thở,ngộp thở,thở không ra hơi', 'Khó khăn trong việc hít thở bình thường', 'Bạn thấy khó thở khi nghỉ ngơi, khi vận động hay vào ban đêm?'), -- 2
('Buồn nôn', 'muốn ói,nôn nao,ói mửa,khó chịu bụng', 'Cảm giác muốn nôn mửa', 'Bạn cảm thấy buồn nôn vào thời điểm nào trong ngày? Có thường xảy ra sau khi ăn hoặc khi ngửi mùi mạnh không?'), -- 3
('Sốt', 'nóng sốt,sốt cao,sốt nhẹ,thân nhiệt cao', 'Nhiệt độ cơ thể cao hơn bình thường', 'Bạn bị sốt liên tục hay theo từng cơn? Nhiệt độ cao nhất bạn đo được là bao nhiêu?'), -- 4
('Tức ngực', 'đau ngực,nặng ngực,ép ngực', 'Cảm giác đau hoặc áp lực ở ngực', 'Bạn cảm thấy tức ngực vào lúc nào? Có thay đổi theo tư thế hoặc khi gắng sức không?'), -- 5
('Mệt mỏi', 'mệt,uể oải,đuối sức,yếu người', 'Cảm giác kiệt sức, thiếu năng lượng', 'Bạn cảm thấy mệt theo kiểu uể oải, buồn ngủ, hay kiệt sức sau khi làm gì đó? Tình trạng này kéo dài bao lâu rồi?'), -- 6
('Co giật', 'giật cơ,co rút,co cứng', 'Chuyển động không kiểm soát của cơ', 'Cơn co giật xảy ra đột ngột hay có dấu hiệu báo trước? Kéo dài bao lâu và bạn còn tỉnh táo không?'), -- 7
('Ngứa da', 'ngứa,ngứa ngáy,muốn gãi', 'Cảm giác châm chích khiến muốn gãi', 'Bạn bị ngứa ở vùng nào trên cơ thể (tay, chân, lưng…)? Có kèm nổi mẩn đỏ, bong tróc da hoặc lan rộng không?'), -- 8
('Phát ban', 'mẩn đỏ,nổi mẩn,da dị ứng', 'Vùng da bị nổi mẩn đỏ hoặc sưng', 'Phát ban xuất hiện lần đầu vào thời điểm nào? Có ngứa, đau hay lan rộng sang vùng da khác không?'), -- 9
('Chán ăn', 'không thèm ăn,bỏ ăn,ăn không ngon miệng', 'Mất cảm giác thèm ăn, không muốn ăn uống', 'Bạn chán ăn trong bao lâu? Có thay đổi khẩu vị hoặc cảm thấy đắng miệng không?'), -- 10
('Ho', 'ho khan,ho có đờm,ho dữ dội', 'Phản xạ đẩy không khí ra khỏi phổi để làm sạch đường hô hấp', 'Cơn ho xảy ra vào thời điểm nào trong ngày (sáng, trưa, tối)? Có tệ hơn khi bạn nằm xuống, vận động hoặc hít phải không khí lạnh không?'), -- 11
('Hắt hơi', 'hắt xì,hắt xì hơi,nhảy mũi', 'Phản xạ mạnh của mũi để đẩy chất gây kích ứng ra ngoài', 'Bạn hắt hơi thường xuyên vào thời gian nào? Có kèm theo chảy nước mũi hoặc ngứa mắt không?'), -- 12
('Chảy nước mũi', 'nước mũi,nước mũi chảy,chảy dịch mũi,sổ mũi', 'Dịch nhầy chảy ra từ mũi do viêm hoặc dị ứng', 'Dịch mũi có màu gì (trong, vàng, xanh)? Có kèm theo nghẹt mũi hoặc mùi lạ không?'), -- 13
('Đau họng', 'rát họng,viêm họng,ngứa họng', 'Cảm giác đau hoặc rát ở vùng họng', 'Bạn đau họng trong hoàn cảnh nào (nuốt, nói chuyện...)? Cảm giác đau kéo dài bao lâu?'), -- 14
('Khó nuốt', 'nuốt đau,khó ăn,vướng cổ họng', 'Cảm giác vướng hoặc đau khi nuốt thức ăn hoặc nước', 'Bạn cảm thấy khó nuốt với loại thức ăn nào (cứng, mềm, lỏng)? Cảm giác có bị nghẹn không?'), -- 15
('Đau bụng', 'đầy bụng,đau bụng dưới,đau bụng trên', 'Cảm giác khó chịu hoặc đau ở vùng bụng', 'Bạn đau bụng ở vùng nào (trên, dưới, bên trái, bên phải)? Cơn đau có lan sang nơi khác hoặc liên tục không?'), -- 16
('Tiêu chảy', 'tiêu lỏng,phân lỏng,đi cầu nhiều', 'Đi ngoài phân lỏng, thường xuyên', 'Bạn bị tiêu chảy bao nhiêu lần mỗi ngày? Phân có lẫn máu, chất nhầy hoặc có mùi bất thường không?'), -- 17
('Táo bón', 'bón,khó đi ngoài,ít đi cầu', 'Đi đại tiện khó khăn hoặc không thường xuyên', 'Bạn bị táo bón trong bao lâu? Có cảm thấy đau khi đi ngoài hoặc phân khô cứng không?'), -- 18
('Chóng mặt', 'chóng mặt,quay cuồng,mất thăng bằng,đầu quay,choáng,choáng váng', 'Cảm giác quay cuồng, mất thăng bằng hoặc như đang bị xoay vòng, thường kèm cảm giác muốn ngã.', 'Bạn cảm thấy chóng mặt vào thời điểm nào? Có xuất hiện khi thay đổi tư thế, đứng lâu, hoặc sau khi ngủ dậy không?'), -- 19
('Đổ mồ hôi nhiều', 'ra mồ hôi,nhiều mồ hôi,ướt người', 'Ra mồ hôi quá mức, không do vận động', 'Bạn đổ mồ hôi nhiều vào thời điểm nào? Tình trạng này có lặp đi lặp lại không?'), -- 20
('Run tay chân', 'tay chân run,rung người,run rẩy', 'Chuyển động không tự chủ ở tay hoặc chân', 'Tay chân bạn run khi nghỉ ngơi, khi thực hiện việc gì đó hay cả hai? Run có tăng khi lo lắng không?'), -- 21
('Khó ngủ', 'mất ngủ,khó ngủ,khó chợp mắt', 'Gặp vấn đề khi ngủ hoặc ngủ không ngon giấc', 'Bạn khó ngủ vì lý do gì (lo lắng, đau nhức, không rõ lý do)? Tình trạng này kéo dài bao lâu rồi?'), -- 22
('Thở gấp', 'thở nhanh,thở gấp,gấp gáp', 'Hơi thở nhanh, ngắn do thiếu oxy', 'Bạn cảm thấy thở gấp trong hoàn cảnh nào? Có xảy ra khi vận động hoặc khi hồi hộp không?'), -- 23
('Tim đập nhanh', 'tim nhanh,đánh trống ngực,tim đập mạnh', 'Nhịp tim tăng bất thường, có thể do lo âu hoặc bệnh lý', 'Bạn thường cảm nhận tim đập nhanh vào thời điểm nào trong ngày? Tình trạng kéo dài bao lâu?'), -- 24
('Tê tay chân', 'tê bì,châm chích,mất cảm giác tay chân', 'Mất cảm giác hoặc cảm giác châm chích ở tay hoặc chân', 'Bạn cảm thấy tê tay chân ở vùng nào? Có lan rộng ra các khu vực khác không?'), -- 25
('Hoa mắt', 'hoa mắt,choáng nhẹ,thoáng mờ mắt,mắt tối sầm', 'Cảm giác mờ mắt thoáng qua, mắt tối sầm hoặc mất thị lực tạm thời trong vài giây, thường liên quan đến huyết áp hoặc thiếu máu.', 'Bạn cảm thấy hoa mắt vào lúc nào? Có kèm theo mất tập trung, mệt mỏi, hoặc sau khi thay đổi tư thế không?'), -- 26 
('Nôn mửa', 'nôn ói,nôn nhiều', 'Hành động đẩy mạnh chất trong dạ dày ra ngoài qua đường miệng', 'Bạn nôn mửa bao nhiêu lần trong ngày? Có liên quan đến bữa ăn hay mùi vị nào không?'), -- 27 
('Khàn giọng', 'giọng khàn,khó nói', 'Sự thay đổi trong giọng nói, thường trở nên trầm và khô', 'Bạn bị khàn giọng trong bao lâu? Có ảnh hưởng đến việc nói chuyện hàng ngày không?'), -- 28
('Yếu cơ', 'yếu sức,yếu cơ,bại cơ', 'Giảm khả năng vận động hoặc sức mạnh cơ bắp', 'Bạn cảm thấy yếu ở tay, chân hay toàn thân? Có trở ngại khi làm các hoạt động thường ngày không?'), -- 29
('Chóng mặt khi đứng dậy', 'choáng khi đứng,chóng mặt tư thế', 'Cảm giác choáng váng khi thay đổi tư thế đứng lên', 'Bạn thường cảm thấy choáng khi đứng dậy hay ngồi dậy đột ngột không?'), -- 30
('Khò khè', 'thở rít,khò khè', 'Âm thanh rít khi thở, thường gặp khi đường thở bị hẹp', 'Bạn nghe tiếng khò khè vào lúc nào trong ngày hoặc khi làm gì?'), -- 31
('Ợ nóng', 'nóng rát ngực,ợ chua', 'Cảm giác nóng rát từ dạ dày lên cổ họng, thường sau ăn', 'Bạn có cảm thấy nóng rát ở ngực sau khi ăn không? Có bị vào ban đêm không?'), -- 32
('Vàng da', 'vàng da,vàng mắt', 'Da và mắt có màu vàng do rối loạn chức năng gan', 'Bạn có nhận thấy da hoặc lòng trắng mắt chuyển vàng trong thời gian gần đây không?'), -- 33
('Cảm giác vô vọng', 'chán nản,vô vọng', 'Tâm trạng tiêu cực kéo dài, mất niềm tin vào tương lai', 'Bạn có thường cảm thấy mọi thứ đều vô ích hoặc không có lối thoát không?'), -- 34
('Khát nước liên tục', 'khát nhiều,uống nhiều nước', 'Cảm giác khát nước kéo dài không rõ lý do', 'Bạn cảm thấy khát thường xuyên dù đã uống đủ nước chưa?'), -- 35
('Đau khớp đột ngột', 'đau khớp ngón chân,cơn gút', 'Đau dữ dội và sưng ở khớp, thường là ngón chân cái', 'Cơn đau bắt đầu ở khớp nào? Có sưng đỏ và đau nhiều vào ban đêm không?'), -- 36
('Cứng khớp buổi sáng', 'khớp cứng,khó cử động', 'Khó cử động khớp vào buổi sáng hoặc sau khi nghỉ ngơi', 'Bạn có bị cứng khớp vào sáng sớm không? Tình trạng kéo dài bao lâu?'), -- 37
('Đỏ mắt', 'mắt đỏ,viêm mắt', 'Mắt bị đỏ do giãn mạch máu kết mạc', 'Bạn bị đỏ mắt một bên hay hai bên? Có chảy ghèn hoặc cảm giác xốn cộm không?'), -- 38
('Đau cơ', 'đau bắp thịt,đau cơ', 'Cảm giác đau ở cơ bắp, đặc biệt khi vận động', 'Bạn đau cơ ở vùng nào? Cơn đau có giảm khi nghỉ ngơi không?'), -- 39
('Đau lan từ lưng xuống chân', 'đau lưng lan chân,thần kinh tọa', 'Cơn đau bắt nguồn từ lưng dưới và lan theo dây thần kinh xuống chân', 'Cơn đau có lan xuống mông, đùi, hoặc gót chân không? Có tê hay yếu cơ kèm theo không?'); -- 40

-------------------------------------------------------liên kết diseases với symptoms--------------------------------------------------------------------------------------------------------------
INSERT INTO disease_symptoms (disease_id, symptom_id) VALUES
-- Tăng huyết áp
(1, 1),  -- Đau đầu
(1, 5),  -- Tức ngực
(1, 24), -- Tim đập nhanh
(1, 20), -- Đổ mồ hôi nhiều
(1, 26), -- Hoa mắt

-- Đột quỵ
(2, 1),  -- Đau đầu
(2, 6),  -- Mệt mỏi
(2, 7),  -- Co giật
(2, 19), -- Hoa mắt chóng mặt
(2, 26), -- Hoa mắt

-- Hen suyễn
(3, 2),  -- Khó thở
(3, 11), -- Ho
(3, 23), -- Thở gấp
(3, 13), -- Chảy nước mũi

-- Viêm phổi
(4, 2),  -- Khó thở
(4, 4),  -- Sốt
(4, 11), -- Ho
(4, 14), -- Đau họng
(4, 28), -- Khàn giọng

-- Viêm dạ dày
(5, 3),  -- Buồn nôn
(5, 10), -- Chán ăn
(5, 16), -- Đau bụng
(5, 18), -- Táo bón
(5, 27), -- Nôn mữa

-- Xơ gan
(6, 6),  -- Mệt mỏi
(6, 10), -- Chán ăn
(6, 16), -- Đau bụng
(6, 17), -- Tiêu chảy

-- Động kinh
(7, 7),  -- Co giật
(7, 6),  -- Mệt mỏi
(7, 21), -- Run tay chân
(7, 19), -- Hoa mắt chóng mặt

-- Trầm cảm
(8, 6),  -- Mệt mỏi
(8, 22), -- Khó ngủ
(8, 10), -- Chán ăn
(8, 25), -- Tê tay chân

-- Viêm da cơ địa
(9, 8),  -- Ngứa da
(9, 9),  -- Phát ban

-- Nấm da
(10, 8), -- Ngứa da
(10, 9), -- Phát ban

-- Viêm đa cơ
(11, 29), -- Yếu cơ

-- Tiểu đường tuýp 2
(12, 6), 
(12, 10), 
(12, 35),

-- Suy tim
(13, 5), 
(13, 6), 
(13, 24), 
(13, 2),

-- Viêm phế quản
(14, 11), 
(14, 4), 
(14, 14),

-- Viêm họng cấp
(15, 14), 
(15, 12), 
(15, 13),

-- Loét dạ dày tá tràng
(16, 16), 
(16, 3), 
(16, 27), 
(16, 32),

-- Viêm gan B mạn tính
(17, 33), 
(17, 6), 
(17, 16), 
(17, 10),

-- Thiếu máu
(18, 6), 
(18, 25), 
(18, 26),

-- Gút
(19, 36), 
(19, 16),

-- Viêm khớp dạng thấp
(20, 37), 
(20, 29), 
(20, 16),

-- Trào ngược dạ dày thực quản
(21, 32), 
(21, 16), 
(21, 3),

-- Rối loạn lo âu
(22, 34), 
(22, 22), 
(22, 6),

-- Cảm cúm
(23, 4), 
(23, 11), 
(23, 12), 
(23, 13),

-- Đau thần kinh tọa
(24, 40), 
(24, 16), 
(24, 25),

-- Viêm kết mạc
(25, 38), 
(25, 13),

-- Chàm (eczema)
(26, 8), 
(26, 9);

GO
-------------------------------------------------------Lịch sử chiệu chứng của bênh nhân Nguyễn Văn A user_id = 4--------------------------------------------------------------------------------------------------------------
INSERT INTO user_symptom_history (user_id, symptom_id, record_date, notes) VALUES
(4, 1, '2025-06-01', 'Đau âm ỉ cả ngày, uống paracetamol thấy đỡ'),
(4, 4, '2025-06-01', 'Sốt nhẹ buổi chiều, khoảng 38°C'),
(4, 6, '2025-06-01', 'Cảm thấy mệt, không muốn làm việc'),

(4, 11, '2025-06-02', 'Ho khan, đặc biệt vào sáng sớm'),
(4, 14, '2025-06-02', 'Đau họng nhẹ, khó nuốt nước lạnh'),

(4, 2, '2025-06-03', 'Khó thở nhẹ khi leo cầu thang'),
(4, 23, '2025-06-03', 'Thở nhanh khi vận động nhẹ'),

(4, 5, '2025-06-04', 'Cảm giác tức ngực, không đau nhưng khó chịu'),
(4, 20, '2025-06-04', 'Đổ mồ hôi nhiều dù không vận động'),

(4, 6, '2025-06-05', 'Vẫn còn mệt mỏi, ngủ không ngon'),
(4, 22, '2025-06-05', 'Khó ngủ, trằn trọc đến 2 giờ sáng');


-------------------------------------------------------Phòng khám--------------------------------------------------------------------------------------------------------------
INSERT INTO clinics (name, address, phone, email, description) VALUES
('Phòng khám Đa khoa Hòa Hảo', '254 Hòa Hảo, Quận 10, TP.HCM', '02838553085', 'hoahao@example.com', 'Phòng khám tư nhân uy tín với nhiều chuyên khoa.'),
('Bệnh viện Chợ Rẫy', '201B Nguyễn Chí Thanh, Quận 5, TP.HCM', '02838554137', 'choray@hospital.vn', 'Bệnh viện tuyến trung ương chuyên điều trị các ca nặng.'),
('Phòng khám Quốc tế Victoria Healthcare', '79 Điện Biên Phủ, Quận 1, TP.HCM', '02839101717', 'info@victoriavn.com', 'Dịch vụ khám chữa bệnh theo tiêu chuẩn quốc tế.'),
('Bệnh viện Đại học Y Dược', '215 Hồng Bàng, Quận 5, TP.HCM', '02838552307', 'contact@umc.edu.vn', 'Bệnh viện trực thuộc Đại học Y Dược TP.HCM.'),
('Phòng khám đa khoa Pasteur', '27 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '02838232299', 'pasteurclinic@vnmail.com', 'Chuyên nội tổng quát, tim mạch, tiêu hóa.');

---------------------------------------------------------------------------------Khoa--------------------------------------------------------------------------------------------------------------
INSERT INTO specialties (name, description) VALUES
('Nội khoa', 'Chẩn đoán và điều trị không phẫu thuật các bệnh lý nội tạng.'),
('Ngoại khoa', 'Chẩn đoán và điều trị bệnh thông qua phẫu thuật.'),
('Tai - Mũi - Họng', 'Khám và điều trị các bệnh lý về tai, mũi và họng.'),
('Tim mạch', 'Chuyên điều trị bệnh về tim và hệ tuần hoàn.'),
('Nhi khoa', 'Chăm sóc và điều trị cho trẻ em từ sơ sinh đến 15 tuổi.'),
('Da liễu', 'Chẩn đoán và điều trị các bệnh về da, tóc và móng.'),
('Tiêu hóa', 'Chuyên về hệ tiêu hóa như dạ dày, gan, ruột.'),
('Thần kinh', 'Khám và điều trị các bệnh về hệ thần kinh trung ương và ngoại biên.');

---------------------------------------------------------------------------------Liên kết Khoa và phòng khám--------------------------------------------------------------------------------------------------------------
-- Phòng khám Đa khoa Hòa Hảo (clinic_id = 1)
INSERT INTO clinic_specialties (clinic_id, specialty_id) VALUES
(1, 1), -- Nội khoa
(1, 3), -- Tai - Mũi - Họng
(1, 4), -- Tim mạch
(1, 7); -- Tiêu hóa

-- Bệnh viện Chợ Rẫy (clinic_id = 2)
INSERT INTO clinic_specialties (clinic_id, specialty_id) VALUES
(2, 1), -- Nội khoa
(2, 2), -- Ngoại khoa
(2, 4), -- Tim mạch
(2, 8); -- Thần kinh

-- Phòng khám Quốc tế Victoria Healthcare (clinic_id = 3)
INSERT INTO clinic_specialties (clinic_id, specialty_id) VALUES
(3, 1), -- Nội khoa
(3, 5), -- Nhi khoa
(3, 6); -- Da liễu

-- Bệnh viện Đại học Y Dược (clinic_id = 4)
INSERT INTO clinic_specialties (clinic_id, specialty_id) VALUES
(4, 1), -- Nội khoa
(4, 2), -- Ngoại khoa
(4, 7), -- Tiêu hóa
(4, 8); -- Thần kinh

-- Phòng khám đa khoa Pasteur (clinic_id = 5)
INSERT INTO clinic_specialties (clinic_id, specialty_id) VALUES
(5, 1), -- Nội khoa
(5, 4), -- Tim mạch
(5, 7); -- Tiêu hóa


---------------------------------------------------------------------------------Bác sĩ---------------------------------------------------------------------------------------------------------------------
-- user_id = 3 là bác sĩ Nội khoa tại Phòng khám Đa khoa Hòa Hảo
-- user_id = 6 là bác sĩ Tim mạch tại Bệnh viện Chợ Rẫy

INSERT INTO doctors (user_id, specialty_id, clinic_id, biography)
VALUES
(3, 1, 1, 'Bác sĩ Nội khoa với hơn 10 năm kinh nghiệm trong điều trị tiểu đường, huyết áp. Tốt nghiệp Đại học Y Dược TP.HCM.'),
(6, 4, 2, 'Bác sĩ Tim mạch từng công tác tại Viện Tim TP.HCM. Có bằng Thạc sĩ Y khoa từ Đại học Paris, Pháp.');

INSERT INTO doctors (user_id, specialty_id, clinic_id, biography)
VALUES
(7, 6, 3, 'Bác sĩ Da liễu với hơn 15 năm kinh nghiệm, chuyên điều trị các bệnh về da liễu và thẩm mỹ da.'),
(8, 8, 4, 'Bác sĩ Thần kinh, từng công tác tại Bệnh viện Bạch Mai, có nhiều công trình nghiên cứu về động kinh.'),
(9, 5, 3, 'Bác sĩ Nhi khoa, nhiều năm làm việc trong chăm sóc sức khỏe trẻ em tại TP.HCM.'),
(10, 2, 2, 'Bác sĩ Ngoại khoa với 20 năm kinh nghiệm phẫu thuật tổng quát, từng học tập tại Nhật Bản.'),
(11, 4, 5, 'Bác sĩ Tim mạch, chuyên về tăng huyết áp và bệnh mạch vành, tham gia nhiều hội nghị quốc tế.');


---------------------------------------------------------------------------------Lịch làm việc bác sĩ---------------------------------------------------------------------------------------------------------------------
-- Lịch bác sĩ Nội khoa (doctor_id = 1) tại phòng khám 1
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(1, 1, '1', '08:00:00', '12:00:00'),
(1, 1, '4', '08:00:00', '12:00:00'),
(1, 1, '6', '13:30:00', '17:30:00');

-- Lịch bác sĩ Tim mạch (doctor_id = 2) tại phòng khám 2
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(2, 2, '3', '09:00:00', '12:00:00'),
(2, 2, '5', '14:00:00', '18:00:00'),
(2, 2, '7', '08:30:00', '11:30:00');

-- BS. Hương - Da liễu tại Victoria Healthcare
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(3, 3, '1', '08:00:00', '12:00:00'),
(3, 3, '5', '13:00:00', '17:00:00');

-- BS. Khoa - Thần kinh tại BV Đại học Y Dược
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(4, 4, '3', '08:30:00', '12:00:00'),
(4, 4, '6', '14:00:00', '18:00:00');

-- BS. Trang - Nhi khoa tại Victoria Healthcare
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(5, 3, '4', '09:00:00', '12:00:00'),
(5, 3, '7', '08:00:00', '11:30:00');

-- BS. Long - Ngoại khoa tại Chợ Rẫy
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(6, 2, '2', '13:00:00', '17:00:00'),
(6, 2, '5', '08:00:00', '12:00:00');

-- BS. Hà - Tim mạch tại Phòng khám Pasteur
INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time)
VALUES
(7, 5, '3', '09:00:00', '12:00:00'),
(7, 5, '6', '13:30:00', '17:00:00');

---------------------------------------------------------------------------------Đặt lịch khám---------------------------------------------------------------------------------------------------------------------

-- user_id = 4 đặt khám bác sĩ Nội khoa (user_id = 3, doctor_id = 1) tại Phòng khám Đa khoa Hòa Hảo
INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_time, reason, status)
VALUES 
(4, 1, 1, '2025-05-28 09:00:00', 'Khám huyết áp và mệt mỏi kéo dài', 'confirmed'),
(4, 1, 1, '2025-06-01 14:30:00', 'Theo dõi tiểu đường định kỳ', 'pending');

-- guest_id = 1 khám Nội khoa (doctor_id = 1) tại Phòng khám Đa khoa Hòa Hảo
-- guest_id = 2 khám Tim mạch (doctor_id = 2) tại Bệnh viện Chợ Rẫy
-- guest_id = 3 khám Tim mạch (doctor_id = 2) tại Bệnh viện Chợ Rẫy

INSERT INTO appointments (guest_id, doctor_id, clinic_id, appointment_time, reason, status)
VALUES
(1, 1, 1, '2025-05-25 10:00:00', 'Đau đầu và cao huyết áp gần đây', 'confirmed'),
(2, 2, 2, '2025-05-27 08:00:00', 'Khó thở, nghi ngờ bệnh tim', 'pending'),
(3, 2, 2, '2025-05-29 15:00:00', 'Đặt lịch kiểm tra tim định kỳ', 'canceled');

---------------------------------------------------------------------------------Đơn thuốc---------------------------------------------------------------------------------------------------------------------

-- Đơn thuốc cho lịch hẹn của user_id = 4 (appointment_id = 1 và 2)
INSERT INTO prescriptions (appointment_id, prescribed_date, medications, notes)
VALUES
(1, '2025-05-28', '[
  {"name": "Thuốc hạ áp Amlodipine", "dosage": "5mg", "frequency": "1 viên/ngày"},
  {"name": "Paracetamol", "dosage": "500mg", "frequency": "2 viên/ngày khi đau đầu"}
]', 'Uống vào buổi sáng sau ăn. Tránh dùng với rượu bia.'),

(2, '2025-06-01', '[
  {"name": "Metformin", "dosage": "500mg", "frequency": "2 lần/ngày"},
  {"name": "Glimepiride", "dosage": "2mg", "frequency": "1 lần/ngày trước ăn sáng"}
]', 'Kiểm tra đường huyết trước mỗi lần dùng thuốc.');

-- Đơn thuốc cho khách vãng lai guest_id = 1 (appointment_id = 3)
INSERT INTO prescriptions (appointment_id, prescribed_date, medications, notes)
VALUES
(3, '2025-05-25', '[
  {"name": "Losartan", "dosage": "50mg", "frequency": "1 viên mỗi sáng"},
  {"name": "Vitamin B1", "dosage": "100mg", "frequency": "1 viên/ngày"}
]', 'Tái khám sau 1 tuần nếu triệu chứng không giảm.');

---------------------------------------------------------------------------------Ghi chú của bác sĩ---------------------------------------------------------------------------------------------------------------------

-- Ghi chú khám của bác sĩ cho các lịch hẹn của user_id = 4
INSERT INTO medical_records (appointment_id, diagnosis, recommendations)
VALUES
(1, 'Tăng huyết áp giai đoạn 1', 'Cần điều chỉnh chế độ ăn và tập thể dục. Uống thuốc đều đặn.'),
(2, 'Tiểu đường tuýp 2', 'Kiểm tra HbA1c 3 tháng/lần. Hạn chế đường và tinh bột.');

-- Ghi chú khám cho khách guest_id = 1
INSERT INTO medical_records (appointment_id, diagnosis, recommendations)
VALUES
(3, 'Cao huyết áp do căng thẳng', 'Nghỉ ngơi hợp lý, tránh thức khuya. Theo dõi huyết áp hàng ngày.');

----------------------------------------------------------------4. Thương mại điện tử-------------------------------------------------------------------------------

--🗂️ product_categories: Danh mục sản phẩm
INSERT INTO product_categories (name, description) VALUES
('Thuốc điều trị', 'Các loại thuốc dùng để điều trị bệnh lý.'),
('Thực phẩm chức năng', 'Sản phẩm hỗ trợ tăng cường sức khỏe.'),
('Thiết bị y tế', 'Các thiết bị và dụng cụ y tế sử dụng trong chẩn đoán và điều trị.'),
('Vật tư tiêu hao', 'Găng tay, khẩu trang, bông băng,... sử dụng một lần.');
INSERT INTO product_categories (name, description) VALUES
('Chăm sóc da', 'Sản phẩm hỗ trợ điều trị và chăm sóc da.'),
('Tiêu hóa', 'Sản phẩm hỗ trợ hệ tiêu hóa.'),
('Miễn dịch', 'Sản phẩm tăng cường sức đề kháng.'),
('Giấc ngủ & thư giãn', 'Giúp cải thiện giấc ngủ và thư giãn.');


--📦 products: Danh sách sản phẩm
INSERT INTO products (category_id, name, description, price, stock, image_url)
VALUES
(1, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau thường dùng.', 15000, 100, 'assets/images/products/paracetamol.jpg'),
(1, 'Amoxicillin 500mg', 'Kháng sinh phổ rộng nhóm penicillin.', 28000, 60, 'assets/images/products/amoxicillin.jpg'),
(2, 'Vitamin C 1000mg', 'Hỗ trợ tăng cường đề kháng.', 50000, 200, 'assets/images/products/vitaminC.jpg'),
(3, 'Máy đo huyết áp điện tử', 'Thiết bị đo huyết áp tại nhà.', 650000, 15, 'assets/images/products/blood_pressure_monitor.jpg'),
(4, 'Khẩu trang y tế 4 lớp', 'Hộp 50 cái, đạt chuẩn kháng khuẩn.', 40000, 500, 'assets/images/products/face_mask.jpg');
-- Thuốc và thực phẩm chức năng
INSERT INTO products (category_id, name, description, price, stock, image_url)
VALUES
(1, 'Ibuprofen 200mg', 'Thuốc giảm đau, kháng viêm, hạ sốt.', 20000, 80, 'assets/images/products/ibuprofen.jpg'),
(2, 'Kẽm Gluconat 50mg', 'Hỗ trợ miễn dịch, chống viêm nhiễm.', 45000, 150, 'assets/images/products/zinc.jpg'),
(2, 'Men tiêu hóa Biolactyl', 'Giúp cân bằng hệ vi sinh đường ruột.', 70000, 90, 'assets/images/products/probiotic.jpg'),
(3, 'Máy xông mũi họng mini', 'Hỗ trợ điều trị viêm mũi, cảm cúm tại nhà.', 350000, 25, 'assets/images/products/nebulizer.jpg'),
(5, 'Kem dưỡng ẩm da nhạy cảm', 'Phục hồi và giữ ẩm cho da khô, kích ứng.', 120000, 50, 'assets/images/products/moisturizer.jpg'),
(6, 'Trà ngủ ngon Hoa Cúc', 'Giúp thư giãn, cải thiện giấc ngủ tự nhiên.', 65000, 70, 'assets/images/products/chamomile_tea.jpg');

UPDATE products SET is_medicine = TRUE WHERE product_id IN (1, 2, 6, 7, 8, 3);

-- Bổ sung 19 sản phẩm mới
INSERT INTO products (category_id, name, description, price, stock, image_url)
VALUES
-- Thuốc điều trị
(1, 'Azithromycin 250mg', 'Kháng sinh nhóm macrolid, điều trị nhiễm khuẩn.', 75000, 40, 'assets/images/products/azithromycin.jpg'),
(1, 'Loratadine 10mg', 'Thuốc kháng histamin, giảm dị ứng.', 30000, 100, 'assets/images/products/loratadine.jpg'),
(1, 'Metformin 500mg', 'Điều trị tiểu đường type 2.', 60000, 80, 'assets/images/products/metformin.jpg'),

-- Thực phẩm chức năng
(2, 'Omega-3 Fish Oil 1000mg', 'Hỗ trợ tim mạch, não bộ.', 150000, 120, 'assets/images/products/omega3.jpg'),
(2, 'Canxi + Vitamin D3', 'Tăng cường xương chắc khỏe.', 110000, 90, 'assets/images/products/calcium_d3.jpg'),
(2, 'Probiotic Kids', 'Men vi sinh hỗ trợ tiêu hóa cho trẻ em.', 95000, 60, 'assets/images/products/probiotic_kids.jpg'),

-- Thiết bị y tế
(3, 'Nhiệt kế hồng ngoại', 'Đo nhiệt độ nhanh chóng, chính xác.', 250000, 35, 'assets/images/products/thermometer.jpg'),
(3, 'Máy đo đường huyết', 'Thiết bị theo dõi đường huyết cá nhân.', 800000, 20, 'assets/images/products/glucometer.jpg'),
(3, 'Ống nghe y tế', 'Dụng cụ nghe tim phổi dành cho bác sĩ.', 180000, 50, 'assets/images/products/stethoscope.jpg'),

-- Vật tư tiêu hao
(4, 'Bơm tiêm 5ml vô trùng', 'Đóng gói 100 cái, sử dụng 1 lần.', 120000, 150, 'assets/images/products/syringe.jpg'),
(4, 'Dung dịch sát khuẩn tay 500ml', 'Chứa 70% cồn, diệt khuẩn hiệu quả.', 45000, 200, 'assets/images/products/hand_sanitizer.jpg'),
(4, 'Bông gòn y tế 500g', 'Dùng trong sơ cứu, chăm sóc vết thương.', 60000, 100, 'assets/images/products/cotton.jpg'),

-- Chăm sóc da
(5, 'Sữa rửa mặt dịu nhẹ', 'Làm sạch bụi bẩn, dịu da.', 95000, 70, 'assets/images/products/cleanser.jpg'),
(5, 'Kem chống nắng SPF50', 'Bảo vệ da trước tia UV.', 180000, 90, 'assets/images/products/sunscreen.jpg'),
(5, 'Serum Vitamin E', 'Dưỡng ẩm và chống lão hóa.', 220000, 50, 'assets/images/products/serum_vitaminE.jpg'),

-- Tiêu hóa
(6, 'Trà gừng túi lọc', 'Hỗ trợ tiêu hóa, giảm buồn nôn.', 75000, 80, 'assets/images/products/ginger_tea.jpg'),
(6, 'Enzyme tiêu hóa Papain', 'Hỗ trợ hấp thu dinh dưỡng.', 95000, 60, 'assets/images/products/papain.jpg'),

-- Miễn dịch
(7, 'Sâm Hàn Quốc dạng viên', 'Bổ sung năng lượng, tăng miễn dịch.', 450000, 40, 'assets/images/products/korean_ginseng.jpg'),
(7, 'Beta Glucan 500mg', 'Tăng sức đề kháng tự nhiên.', 160000, 70, 'assets/images/products/beta_glucan.jpg'),

-- Giấc ngủ & thư giãn
(8, 'Melatonin 3mg', 'Hỗ trợ ngủ ngon, điều chỉnh nhịp sinh học.', 180000, 65, 'assets/images/products/melatonin.jpg');


------------------------------------------------------------medicines: Thông tin chi tiết thuốc (chỉ áp dụng với sản phẩm là thuốc)------------------------------------------------------------------------------------
INSERT INTO medicines (
    product_id, active_ingredient, dosage_form, unit, medicine_type, usage_instructions, side_effects, contraindications
) VALUES
(1, 'Paracetamol', 'Viên nén', 'viên', 'OTC',
 'Uống 1–2 viên mỗi 4–6 giờ nếu cần. Không dùng quá 8 viên/ngày.',
 'Buồn nôn, phát ban nhẹ, rối loạn tiêu hoá (hiếm).',
 'Người bị bệnh gan, nghiện rượu nặng.'),

(2, 'Amoxicillin', 'Viên nang', 'viên', 'Kê đơn',
 'Uống 1 viên mỗi 8 giờ, duy trì trong 5–7 ngày.',
 'Tiêu chảy, nổi mẩn da, dị ứng.',
 'Người dị ứng với penicillin hoặc cephalosporin.'),

(3, 'Vitamin C', 'Viên nén sủi bọt', 'viên', 'Bổ sung',
 'Uống 1 viên mỗi ngày sau bữa ăn. Không dùng quá 2000mg/ngày.',
 'Buồn nôn, tiêu chảy nếu dùng liều cao.',
 'Người bị sỏi thận, thiếu men G6PD.'),

(6, 'Ibuprofen', 'Viên nén bao phim', 'viên', 'OTC',
 'Uống sau ăn. Người lớn uống 1 viên mỗi 6–8 giờ nếu cần. Không quá 6 viên/ngày.',
 'Đau bụng, buồn nôn, chóng mặt, loét dạ dày nếu lạm dụng.',
 'Người bị loét dạ dày tá tràng, suy gan/thận nặng.'),

(7, 'Zinc gluconate', 'Viên nén', 'viên', 'Bổ sung',
 'Uống 1 viên mỗi ngày sau bữa ăn. Không dùng quá 40mg kẽm/ngày.',
 'Buồn nôn, kích ứng tiêu hóa nhẹ.',
 'Không dùng đồng thời với tetracycline (kháng sinh)' ),

(8, 'Bacillus clausii', 'Gói bột', 'gói', 'Bổ sung',
 'Uống 1–2 gói/ngày, pha với nước nguội. Không uống chung với kháng sinh.',
 'Rất hiếm: đầy hơi, rối loạn nhẹ đường tiêu hóa.',
 'Không dùng cho người bị suy giảm miễn dịch nghiêm trọng.');


--------------------------------------------------- prescription_products: Sản phẩm thực tế được kê trong đơn thuốc------------------------------------------------------------------------------------
-- Đơn thuốc 1 (của user_id = 4, appointment_id = 1)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(1, 1, 10, '500mg', '2 viên/ngày khi đau đầu'),    -- Paracetamol
(1, NULL, 7, '5mg', '1 viên/ngày');                -- Amlodipine chưa có trong products, có thể là thuốc ngoài danh mục

-- Đơn thuốc 2 (của user_id = 4, appointment_id = 2)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(2, NULL, 14, '500mg', '2 lần/ngày'),              -- Metformin, không có trong bảng `products`
(2, NULL, 7, '2mg', '1 lần/ngày trước ăn sáng');   -- Glimepiride, cũng không có trong bảng `products`

-- Đơn thuốc 3 (của guest_id = 1, appointment_id = 3)
INSERT INTO prescription_products (prescription_id, product_id, quantity, dosage, usage_time)
VALUES
(3, NULL, 7, '50mg', '1 viên mỗi sáng'),           -- Losartan
(3, NULL, 7, '100mg', '1 viên/ngày');              -- Vitamin B1


-------------------------------------------------------------------------------------- product_reviews------------------------------------------------------------------------------------
-- Huy (user_id = 2) đánh giá Paracetamol (product_id = 1)
INSERT INTO product_reviews (product_id, user_id, rating, comment)
VALUES
(1, 2, 5, 'Thuốc giảm đau hiệu quả, ít tác dụng phụ.'),

-- Huy (user_id = 2) đánh giá Amoxicillin (product_id = 2)
(2, 2, 4, 'Tốt nhưng gây buồn nôn nhẹ.'),

-- Admin (user_id = 1) đánh giá máy đo huyết áp (product_id = 4)
(4, 1, 5, 'Dễ sử dụng và rất chính xác.'),

-- Người dùng "dr.hanh" (user_id = 3) đánh giá Vitamin C (product_id = 3)
(3, 3, 4, 'Khá ổn để tăng sức đề kháng. Đóng gói đẹp.');

----------------------------------------------------------------3. Chatbot AI-------------------------------------------------------------------------------

----------------------------------------------------------------5. Dịch vụ y tế-------------------------------------------------------------------------------

----------------------------------------------------------------Dữ liệu mẫu cho categories--------------------------------------------------------------------------------------------------------------------------
INSERT INTO service_categories (name, slug, icon, description) VALUES
('Khám Tổng Quát', 'kham-tong-quat', 'fas fa-stethoscope', 'Dịch vụ khám sức khỏe tổng quát và tầm soát bệnh'),
('Tim Mạch', 'tim-mach', 'fas fa-heartbeat', 'Chẩn đoán và điều trị các bệnh lý tim mạch'),
('Tiêu Hóa', 'tieu-hoa', 'fas fa-prescription-bottle-alt', 'Điều trị các bệnh về đường tiêu hóa'),
('Thần Kinh', 'than-kinh', 'fas fa-brain', 'Điều trị các bệnh lý thần kinh'),
('Chấn Thương Chỉnh Hình', 'chan-thuong-chinh-hinh', 'fas fa-bone', 'Điều trị chấn thương và bệnh lý xương khớp'),
('Cấp Cứu', 'cap-cuu', 'fas fa-ambulance', 'Dịch vụ cấp cứu 24/7');

----------------------------------------------------------------Dữ liệu mẫu cho services--------------------------------------------------------------------------------------------------------------------------
INSERT INTO services (category_id, name, slug, short_description, price_from, price_to, is_featured, is_emergency) VALUES
(1, 'Khám Tổng Quát', 'kham-tong-quat', 'Khám sức khỏe định kỳ và tầm soát các bệnh lý thường gặp', 200000, 500000, FALSE, FALSE),
(2, 'Khám Tim Mạch', 'kham-tim-mach', 'Chẩn đoán và điều trị các bệnh lý tim mạch với trang thiết bị hiện đại', 300000, 2000000, TRUE, FALSE),
(3, 'Khám Tiêu Hóa', 'kham-tieu-hoa', 'Chẩn đoán và điều trị các bệnh lý về đường tiêu hóa, gan mật', 250000, 1500000, FALSE, FALSE),
(6, 'Dịch Vụ Cấp Cứu', 'dich-vu-cap-cuu', 'Dịch vụ cấp cứu 24/7 với đội ngũ y bác sĩ luôn sẵn sàng', NULL, NULL, FALSE, TRUE);

----------------------------------------------------------------Dữ liệu mẫu cho service_features----------------------------------------------------------------
INSERT INTO service_features (service_id, feature_name) VALUES
(1, 'Khám lâm sàng toàn diện'),
(1, 'Xét nghiệm máu cơ bản'),
(1, 'Đo huyết áp, nhịp tim'),
(1, 'Tư vấn dinh dưỡng'),
(2, 'Siêu âm tim'),
(2, 'Điện tim'),
(2, 'Holter 24h'),
(2, 'Thăm dò chức năng tim');

----------------------------------------------------------------Dữ liệu mẫu cho service_packages----------------------------------------------------------------
INSERT INTO service_packages (name, slug, description, price, duration, is_featured) VALUES
('Gói Cơ Bản', 'goi-co-ban', 'Gói khám sức khỏe cơ bản', 1500000, '/lần', FALSE),
('Gói Nâng Cao', 'goi-nang-cao', 'Gói khám sức khỏe nâng cao', 3500000, '/lần', TRUE),
('Gói Cao Cấp', 'goi-cao-cap', 'Gói khám sức khỏe cao cấp', 6500000, '/lần', FALSE);
----------------------------------------------------------------Dữ liệu mẫu cho --------------------------------------------------------------------------------------------------------------------------------
INSERT INTO package_features (package_id, feature_name) VALUES
(1, 'Khám lâm sàng tổng quát'),
(1, 'Xét nghiệm máu cơ bản'),
(1, 'Xét nghiệm nước tiểu'),
(1, 'X-quang phổi'),
(1, 'Điện tim'),
(1, 'Tư vấn kết quả'),
(2, 'Tất cả gói cơ bản'),
(2, 'Siêu âm bụng tổng quát'),
(2, 'Siêu âm tim');
