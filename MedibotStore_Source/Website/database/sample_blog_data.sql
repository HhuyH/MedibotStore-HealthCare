-- Sample data for blog system
-- Chạy file này sau khi đã tạo các bảng blog

-- Insert blog categories
INSERT INTO blog_categories (name, description, slug) VALUES
('Chăm sóc sức khỏe', 'Các bài viết về chăm sóc sức khỏe hàng ngày', 'cham-soc-suc-khoe'),
('Dinh dưỡng', 'Kiến thức về dinh dưỡng và chế độ ăn uống', 'dinh-duong'),
('Thể dục', 'Hướng dẫn tập thể dục và vận động', 'the-duc'),
('Y học', 'Thông tin y khoa và y tế', 'y-hoc'),
('Sức khỏe tinh thần', 'Chăm sóc sức khỏe tinh thần và tâm lý', 'suc-khoe-tinh-than');

-- Insert blog authors
INSERT INTO blog_authors (name, email, bio, avatar) VALUES
('BS. Nguyễn Văn A', 'bs.nguyenvana@medisync.vn', 'Bác sĩ có 15 năm kinh nghiệm trong lĩnh vực tim mạch', 'assets/images/default-doctor.jpg'),
('TS. Trần Thị B', 'ts.tranthib@medisync.vn', 'Tiến sĩ dinh dưỡng, chuyên gia tư vấn chế độ ăn', 'assets/images/default-doctor.jpg'),
('BS. Phạm Văn C', 'bs.phamvanc@medisync.vn', 'Bác sĩ chuyên khoa thần kinh', 'assets/images/default-doctor.jpg');

-- Insert blog posts
INSERT INTO blog_posts (author_id, category_id, title, slug, content, excerpt, featured_image, status, is_featured, view_count, published_at) VALUES

-- Post 1: Chăm sóc sức khỏe
(1, 1, '10 Cách Tăng Cường Hệ Miễn Dịch Tự Nhiên', '10-cach-tang-cuong-he-mien-dich-tu-nhien', 
'<h2>Giới thiệu về hệ miễn dịch</h2>
<p>Hệ miễn dịch là hệ thống phòng vệ tự nhiên của cơ thể, giúp chúng ta chống lại các vi khuẩn, virus và các tác nhân gây bệnh khác. Việc tăng cường hệ miễn dịch một cách tự nhiên không chỉ giúp phòng ngừa bệnh tật mà còn cải thiện sức khỏe tổng thể.</p>

<h3>1. Ngủ đủ giấc</h3>
<p>Giấc ngủ chất lượng là yếu tố quan trọng nhất để duy trì hệ miễn dịch khỏe mạnh. Khi ngủ, cơ thể sản xuất các protein bảo vệ gọi là cytokines, giúp chống lại nhiễm trùng và viêm.</p>
<ul>
<li>Ngủ 7-9 tiếng mỗi đêm</li>
<li>Tạo môi trường ngủ thoải mái</li>
<li>Tránh caffeine trước khi ngủ</li>
</ul>

<h3>2. Ăn uống cân bằng</h3>
<p>Chế độ ăn giàu vitamin và khoáng chất giúp tăng cường miễn dịch hiệu quả:</p>
<ul>
<li>Vitamin C: Cam, chanh, kiwi, ớt chuông</li>
<li>Vitamin D: Cá hồi, trứng, nấm</li>
<li>Kẽm: Hạt bí, đậu lăng, thịt nạc</li>
<li>Probiotics: Sữa chua, kimchi, miso</li>
</ul>

<h3>3. Tập thể dục đều đặn</h3>
<p>Hoạt động thể chất vừa phải giúp tăng cường lưu thông máu và lymph, cải thiện chức năng miễn dịch.</p>

<h3>4. Quản lý stress</h3>
<p>Stress mزمن có thể làm suy yếu hệ miễn dịch. Các kỹ thuật giảm stress hiệu quả:</p>
<ul>
<li>Thiền định</li>
<li>Yoga</li>
<li>Hít thở sâu</li>
<li>Nghe nhạc thư giãn</li>
</ul>

<h3>5. Uống đủ nước</h3>
<p>Nước giúp vận chuyển chất dinh dưỡng đến các tế bào và loại bỏ độc tố khỏi cơ thể.</p>

<h3>6. Tránh rượu bia và thuốc lá</h3>
<p>Rượu bia và thuốc lá làm suy yếu hệ miễn dịch và tăng nguy cơ mắc bệnh.</p>

<h3>7. Tắm nắng hợp lý</h3>
<p>Ánh nắng mặt trời giúp cơ thể sản xuất vitamin D, cần thiết cho hệ miễn dịch.</p>

<h3>8. Duy trì mối quan hệ xã hội tích cực</h3>
<p>Các mối quan hệ tốt giúp giảm stress và cải thiện sức khỏe tinh thần.</p>

<h3>9. Vệ sinh cá nhân</h3>
<p>Rửa tay thường xuyên và vệ sinh cá nhân tốt giúp ngăn ngừa vi khuẩn xâm nhập.</p>

<h3>10. Bổ sung thảo dược tự nhiên</h3>
<p>Một số thảo dược có tác dụng tăng cường miễn dịch:</p>
<ul>
<li>Tỏi</li>
<li>Gừng</li>
<li>Nghệ</li>
<li>Trà xanh</li>
</ul>

<h2>Kết luận</h2>
<p>Tăng cường hệ miễn dịch tự nhiên là quá trình lâu dài đòi hỏi sự kiên trì. Hãy bắt đầu từ những thay đổi nhỏ trong sinh hoạt hàng ngày để có một cơ thể khỏe mạnh.</p>', 
'Khám phá 10 cách tự nhiên và hiệu quả để tăng cường hệ miễn dịch, giúp cơ thể chống lại bệnh tật và duy trì sức khỏe tốt.', 
'assets/images/thiet_bi_y_te.jpg', 'published', 1, 30, NOW()),

-- Post 2: Dinh dưỡng  
(2, 2, 'Chế Độ Ăn Uống Lành Mạnh Cho Tim Mạch', 'che-do-an-uong-lanh-manh-cho-tim-mach',
'<h2>Tầm quan trọng của dinh dưỡng với tim mạch</h2>
<p>Tim mạch là hệ thống quan trọng nhất của cơ thể, và chế độ ăn uống có ảnh hưởng trực tiếp đến sức khỏe tim mạch. Một chế độ ăn lành mạnh có thể giảm nguy cơ mắc bệnh tim, đột quỵ và các vấn đề tim mạch khác.</p>

<h3>Thực phẩm tốt cho tim mạch</h3>

<h4>1. Cá giàu omega-3</h4>
<p>Các loại cá như cá hồi, cá thu, cá sardine chứa nhiều omega-3, giúp giảm viêm và cải thiện chức năng tim.</p>

<h4>2. Rau xanh đậm màu</h4>
<ul>
<li>Rau bina: Giàu folate và nitrate</li>
<li>Cải xoăn: Chứa vitamin K và antioxidants</li>
<li>Bông cải xanh: Giàu vitamin C và fiber</li>
</ul>

<h4>3. Quả berry</h4>
<p>Blueberry, strawberry, raspberry chứa anthocyanins - chất chống oxy hóa mạnh.</p>

<h4>4. Hạt và đậu</h4>
<ul>
<li>Hạt óc chó: Omega-3 thực vật</li>
<li>Hạnh nhân: Vitamin E và magie</li>
<li>Đậu đen: Protein và fiber</li>
</ul>

<h3>Thực phẩm nên hạn chế</h3>
<ul>
<li>Thực phẩm chế biến sẵn</li>
<li>Đồ ăn nhiều muối</li>
<li>Đường tinh luyện</li>
<li>Chất béo trans</li>
</ul>

<h3>Mẹo thực hành</h3>
<ol>
<li>Nấu ăn tại nhà nhiều hơn</li>
<li>Đọc nhãn thực phẩm cẩn thận</li>
<li>Ăn nhiều bữa nhỏ trong ngày</li>
<li>Uống đủ nước</li>
</ol>

<h2>Thực đơn mẫu một ngày</h2>
<h4>Sáng:</h4>
<p>Yến mạch với quả berry và hạt óc chó</p>

<h4>Trưa:</h4>
<p>Salad cá hồi với rau xanh và dầu olive</p>

<h4>Chiều:</h4>
<p>Một nắm hạnh nhân hoặc trái cây</p>

<h4>Tối:</h4>
<p>Cá nướng với rau củ hấp và quinoa</p>',
'Tìm hiểu về chế độ ăn uống lành mạnh giúp bảo vệ tim mạch, bao gồm các thực phẩm nên ăn và nên tránh.',
'assets/images/thuc_pham_chuc_nang.jpg', 'published', 0, 25, NOW()),

-- Post 3: Y học
(3, 4, 'Lợi Ích Của Việc Tập Thể Dục Đều Đặn', 'loi-ich-cua-viec-tap-the-duc-deu-dan',
'<h2>Tại sao tập thể dục quan trọng?</h2>
<p>Tập thể dục đều đặn không chỉ giúp duy trì cân nặng lý tưởng mà còn mang lại nhiều lợi ích về mặt thể chất và tinh thần. Đây là một trong những cách hiệu quả nhất để cải thiện chất lượng cuộc sống.</p>

<h3>Lợi ích về thể chất</h3>

<h4>1. Tăng cường sức khỏe tim mạch</h4>
<p>Thể dục giúp tim làm việc hiệu quả hơn, giảm huyết áp và cải thiện lưu thông máu.</p>

<h4>2. Xây dựng cơ bắp và xương chắc khỏe</h4>
<ul>
<li>Tăng khối lượng cơ</li>
<li>Cải thiện mật độ xương</li>
<li>Tăng cường độ bền</li>
</ul>

<h4>3. Kiểm soát cân nặng</h4>
<p>Đốt cháy calories và tăng tốc độ trao đổi chất.</p>

<h3>Lợi ích về tinh thần</h3>

<h4>1. Giảm stress và lo âu</h4>
<p>Thể dục giúp giải phóng endorphins - hormone hạnh phúc tự nhiên.</p>

<h4>2. Cải thiện giấc ngủ</h4>
<p>Hoạt động thể chất giúp điều hòa chu kỳ ngủ-thức.</p>

<h4>3. Tăng cường sự tự tin</h4>
<p>Cải thiện hình thể và đạt được mục tiêu tập luyện.</p>

<h3>Các loại hình tập luyện</h3>

<h4>Aerobic</h4>
<ul>
<li>Đi bộ nhanh</li>
<li>Chạy bộ</li>
<li>Bơi lội</li>
<li>Đạp xe</li>
</ul>

<h4>Tập sức mạnh</h4>
<ul>
<li>Nâng tạ</li>
<li>Tập với trọng lượng cơ thể</li>
<li>Sử dụng dây kháng lực</li>
</ul>

<h4>Tập dẻo dai</h4>
<ul>
<li>Yoga</li>
<li>Pilates</li>
<li>Stretching</li>
</ul>

<h3>Lời khuyên để bắt đầu</h3>
<ol>
<li>Bắt đầu từ từ</li>
<li>Chọn hoạt động yêu thích</li>
<li>Đặt mục tiêu thực tế</li>
<li>Tìm bạn tập cùng</li>
<li>Lắng nghe cơ thể</li>
</ol>

<h2>Kết luận</h2>
<p>Tập thể dục đều đặn là khoản đầu tư tốt nhất cho sức khỏe. Hãy bắt đầu ngay hôm nay với những bước nhỏ và dần dần xây dựng thói quen lành mạnh này.</p>',
'Khám phá những lợi ích tuyệt vời của việc tập thể dục đều đặn đối với sức khỏe thể chất và tinh thần.',
'assets/images/h_p_1.jpg', 'published', 0, 15, NOW()),

-- Post 4: Sức khỏe tinh thần
(1, 5, 'Quản Lý Stress Hiệu Quả Trong Cuộc Sống', 'quan-ly-stress-hieu-qua-trong-cuoc-song',
'<h2>Stress là gì và tại sao cần quản lý?</h2>
<p>Stress là phản ứng tự nhiên của cơ thể trước các áp lực trong cuộc sống. Tuy nhiên, khi stress kéo dài có thể gây hại cho sức khỏe thể chất và tinh thần. Việc học cách quản lý stress hiệu quả là kỹ năng quan trọng trong cuộc sống hiện đại.</p>

<h3>Dấu hiệu nhận biết stress</h3>

<h4>Về thể chất:</h4>
<ul>
<li>Đau đầu thường xuyên</li>
<li>Căng thẳng cơ bắp</li>
<li>Mệt mỏi</li>
<li>Rối loạn giấc ngủ</li>
<li>Thay đổi khẩu vị</li>
</ul>

<h4>Về tâm lý:</h4>
<ul>
<li>Lo lắng, bồn chồn</li>
<li>Khó tập trung</li>
<li>Cáu kỉnh, dễ nổi giận</li>
<li>Cảm giác quá tải</li>
<li>Tâm trạng buồn bã</li>
</ul>

<h3>Nguyên nhân gây stress phổ biến</h3>
<ul>
<li>Áp lực công việc</li>
<li>Vấn đề tài chính</li>
<li>Mối quan hệ</li>
<li>Thay đổi lớn trong cuộc sống</li>
<li>Vấn đề sức khỏe</li>
</ul>

<h3>Kỹ thuật quản lý stress hiệu quả</h3>

<h4>1. Kỹ thuật thở sâu</h4>
<p>Hít vào 4 giây, giữ 4 giây, thở ra 6 giây. Lặp lại 5-10 lần.</p>

<h4>2. Thiền định và mindfulness</h4>
<ul>
<li>Dành 10-15 phút mỗi ngày để thiền</li>
<li>Tập trung vào hiện tại</li>
<li>Quan sát suy nghĩ mà không phán xét</li>
</ul>

<h4>3. Tập thể dục</h4>
<p>Hoạt động thể chất giúp giải phóng căng thẳng và sản sinh endorphins.</p>

<h4>4. Quản lý thời gian</h4>
<ul>
<li>Lập danh sách việc cần làm</li>
<li>Ưu tiên nhiệm vụ quan trọng</li>
<li>Học cách nói "không"</li>
<li>Nghỉ ngơi đầy đủ</li>
</ul>

<h4>5. Kết nối xã hội</h4>
<p>Chia sẻ với bạn bè, gia đình hoặc chuyên gia khi cần thiết.</p>

<h3>Thói quen hàng ngày giảm stress</h3>

<h4>Buổi sáng:</h4>
<ul>
<li>Thức dậy sớm hơn 15 phút</li>
<li>Tập thở sâu</li>
<li>Lập kế hoạch cho ngày</li>
</ul>

<h4>Trong ngày:</h4>
<ul>
<li>Nghỉ ngơi ngắn giữa các công việc</li>
<li>Đi bộ ngoài trời</li>
<li>Uống đủ nước</li>
</ul>

<h4>Buổi tối:</h4>
<ul>
<li>Tắt điện thoại trước khi ngủ 1 giờ</li>
<li>Đọc sách hoặc nghe nhạc nhẹ</li>
<li>Viết nhật ký biết ơn</li>
</ul>

<h3>Khi nào cần tìm kiếm sự giúp đỡ?</h3>
<p>Nếu stress ảnh hưởng nghiêm trọng đến cuộc sống hàng ngày, công việc hoặc các mối quan hệ, hãy tìm đến sự hỗ trợ từ:</p>
<ul>
<li>Bác sĩ gia đình</li>
<li>Chuyên gia tâm lý</li>
<li>Nhóm hỗ trợ</li>
</ul>

<h2>Kết luận</h2>
<p>Quản lý stress là một kỹ năng quan trọng có thể học được. Bằng cách áp dụng các kỹ thuật phù hợp và xây dựng thói quen lành mạnh, chúng ta có thể giảm thiểu tác động tiêu cực của stress và sống một cuộc sống cân bằng, hạnh phúc hơn.</p>',
'Học cách nhận biết và quản lý stress hiệu quả để duy trì sức khỏe tinh thần và cải thiện chất lượng cuộc sống.',
'assets/images/h_p_2.jpg', 'published', 1, 45, NOW()),

-- Post 5: Dinh dưỡng
(2, 2, 'Vai Trò Của Vitamin D Đối Với Sức Khỏe', 'vai-tro-cua-vitamin-d-doi-voi-suc-khoe',
'<h2>Vitamin D - "Vitamin của ánh nắng"</h2>
<p>Vitamin D được biết đến như "vitamin của ánh nắng" vì cơ thể có thể tự sản xuất khi da tiếp xúc với ánh nắng mặt trời. Đây là một trong những vitamin quan trọng nhất đối với sức khỏe con người.</p>

<h3>Tầm quan trọng của Vitamin D</h3>

<h4>1. Sức khỏe xương và răng</h4>
<p>Vitamin D giúp cơ thể hấp thụ canxi và phosphate từ thức ăn, cần thiết cho:</p>
<ul>
<li>Xây dựng và duy trì xương chắc khỏe</li>
<li>Phòng ngừa loãng xương ở người lớn tuổi</li>
<li>Phát triển xương và răng ở trẻ em</li>
</ul>

<h4>2. Tăng cường hệ miễn dịch</h4>
<p>Vitamin D đóng vai trò quan trọng trong việc:</p>
<ul>
<li>Điều hòa phản ứng miễn dịch</li>
<li>Chống lại các tác nhân gây bệnh</li>
<li>Giảm nguy cơ nhiễm trùng đường hô hấp</li>
</ul>

<h4>3. Sức khỏe tim mạch</h4>
<ul>
<li>Điều hòa huyết áp</li>
<li>Giảm nguy cơ bệnh tim</li>
<li>Cải thiện chức năng tim</li>
</ul>

<h3>Nguồn cung cấp Vitamin D</h3>

<h4>1. Ánh nắng mặt trời</h4>
<p>Cách tự nhiên và hiệu quả nhất:</p>
<ul>
<li>Phơi nắng 15-30 phút mỗi ngày</li>
<li>Thời gian tốt nhất: 6-9h sáng hoặc 4-6h chiều</li>
<li>Không cần kem chống nắng trong thời gian ngắn</li>
</ul>

<h4>2. Thực phẩm giàu Vitamin D</h4>
<ul>
<li><strong>Cá béo:</strong> Cá hồi, cá thu, cá sardine</li>
<li><strong>Trứng:</strong> Đặc biệt là lòng đỏ trứng</li>
<li><strong>Nấm:</strong> Nấm maitake, nấm UV-treated</li>
<li><strong>Thực phẩm tăng cường:</strong> Sữa, ngũ cốc, nước cam</li>
</ul>

<h4>3. Thực phẩm bổ sung</h4>
<p>Khi không thể đáp ứng đủ qua ánh nắng và thức ăn.</p>

<h3>Dấu hiệu thiếu Vitamin D</h3>
<ul>
<li>Đau xương và cơ</li>
<li>Mệt mỏi thường xuyên</li>
<li>Hay bị ốm</li>
<li>Chậm lành vết thương</li>
<li>Rụng tóc</li>
<li>Trầm cảm</li>
</ul>

<h3>Nhóm nguy cơ thiếu Vitamin D</h3>
<ul>
<li>Người ít ra ngoài trời</li>
<li>Da tối màu</li>
<li>Người lớn tuổi</li>
<li>Phụ nữ mang thai và cho con bú</li>
<li>Người béo phì</li>
<li>Sống ở vùng ít nắng</li>
</ul>

<h3>Mức độ Vitamin D cần thiết</h3>
<ul>
<li><strong>Trẻ em (0-12 tháng):</strong> 400 IU/ngày</li>
<li><strong>Trẻ em (1-18 tuổi):</strong> 600 IU/ngày</li>
<li><strong>Người lớn (19-70 tuổi):</strong> 600 IU/ngày</li>
<li><strong>Người trên 70 tuổi:</strong> 800 IU/ngày</li>
</ul>

<h3>Lưu ý khi bổ sung Vitamin D</h3>
<ul>
<li>Kiểm tra mức Vitamin D trong máu trước khi bổ sung</li>
<li>Không tự ý dùng liều cao</li>
<li>Tham khảo ý kiến bác sĩ</li>
<li>Kết hợp với magie để hấp thụ tốt hơn</li>
</ul>

<h2>Mẹo tăng cường Vitamin D tự nhiên</h2>
<ol>
<li>Tạo thói quen đi bộ ngoài trời hàng ngày</li>
<li>Ăn cá béo 2-3 lần/tuần</li>
<li>Chọn thực phẩm được tăng cường Vitamin D</li>
<li>Kiểm tra sức khỏe định kỳ</li>
</ol>

<h2>Kết luận</h2>
<p>Vitamin D đóng vai trò thiết yếu trong việc duy trì sức khỏe toàn diện. Việc đảm bảo đủ Vitamin D thông qua ánh nắng, chế độ ăn và bổ sung hợp lý sẽ giúp cơ thể khỏe mạnh và phòng ngừa nhiều bệnh tật.</p>',
'Tìm hiểu về tầm quan trọng của Vitamin D, nguồn cung cấp tự nhiên và cách bổ sung hiệu quả cho cơ thể.',
'assets/images/thuoc_1.1.jpg', 'published', 0, 20, NOW());

-- Update view counts to make it more realistic
UPDATE blog_posts SET view_count = FLOOR(RAND() * 100) + 10 WHERE view_count < 50; 