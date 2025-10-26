-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 27, 2025 at 05:06 AM
-- Server version: 8.0.36
-- PHP Version: 8.1.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `diemdanh_literature`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `id_assignment` int NOT NULL,
  `id_setting_detail` int NOT NULL,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_id` int NOT NULL,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`class_id`, `class_name`, `created_at`, `updated_at`) VALUES
(1, 'Lớp 10A1', '2025-10-25 01:15:28', '2025-10-25 01:15:28');

-- --------------------------------------------------------

--
-- Table structure for table `class_member`
--

CREATE TABLE `class_member` (
  `class_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('admin','teacher','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `added_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_member`
--

INSERT INTO `class_member` (`class_id`, `user_id`, `role`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'teacher', 1, '2025-10-24 22:45:44', '2025-10-25 00:48:33'),
(1, 357, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 358, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 359, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 360, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 361, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 362, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 363, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 364, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 365, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 366, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 367, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 368, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 369, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 370, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 371, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 372, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 373, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 374, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 375, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 376, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 377, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 378, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 379, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 380, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 381, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 382, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 383, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 384, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 385, 'student', 2, '2025-10-25 09:59:55', '2025-10-25 09:59:55'),
(1, 386, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 387, 'student', 2, '2025-10-25 09:59:56', '2025-10-25 09:59:56'),
(1, 388, 'student', 2, '2025-10-25 11:02:49', '2025-10-25 11:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `comment_id` int NOT NULL,
  `content_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `task_id` int NOT NULL,
  `reply_comment` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`comment_id`, `content_id`, `user_id`, `comment_text`, `created_at`, `task_id`, `reply_comment`) VALUES
(1, 36, 388, 'Bài thơ là cuộc đời về Hồ Tây', '2025-10-26 12:49:42', 5, 0),
(2, 36, 388, 'Hồ Tây đẹp', '2025-10-26 12:51:48', 5, 1),
(7, 53, 378, 'Nhiều quá', '2025-10-26 13:46:57', 5, 0),
(8, 36, 2, 'đúng', '2025-10-27 03:44:26', 6, 2),
(9, 36, 388, '1', '2025-10-27 04:15:50', 5, 2),
(10, 36, 388, '2', '2025-10-27 04:38:54', 7, 2);

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` int NOT NULL,
  `response_id` int DEFAULT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `order` int NOT NULL,
  `type_create` varchar(500) DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`content_id`, `response_id`, `content`, `order`, `type_create`, `added_by`, `created_at`, `updated_at`) VALUES
(34, 4, '# TẢN MẠN TÂY HỒ', 1, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(35, 4, '**Từ khóa:** Tây Hồ, hoài niệm, tình yêu, mùa thu, sen, chia ly, ký ức, thời gian', 2, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(36, 4, '## Nỗi nhớ và sự hoài niệm về tình yêu đã qua\nBài thơ là dòng tâm sự của một người đang hồi tưởng về mối tình xưa tại Hồ Tây. Nỗi nhớ không chỉ là về một người cụ thể mà còn là nỗi nhớ về không gian, thời gian và những cảm xúc mãnh liệt đã từng có. Thời gian trôi đi, mọi vật đổi thay nhưng ký ức về tình yêu đầu vẫn còn vẹn nguyên, ám ảnh trong tâm trí, trở thành một phần không thể thiếu trong đời sống tinh thần của nhân vật trữ tình.', 3, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(37, 4, '## Sự hữu hạn của thời gian và vẻ đẹp vĩnh cửu của ký ức\nThời gian được nhắc đến qua các cụm từ như \"thuở xưa\", \"thu ấy\", \"bây giờ tóc đã hoa râm\", cho thấy sự chảy trôi khắc nghiệt của thời gian, mang theo tuổi tác và sự đổi thay. Tuy nhiên, những ký ức về tình yêu, hương sen, nụ hôn lại mang một vẻ đẹp vĩnh cửu, không phai mờ theo năm tháng, trở thành điểm tựa tinh thần, nguồn an ủi và nỗi day dứt thường trực cho nhân vật trữ tình.', 4, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(38, 4, '## Hồ Tây – Không gian nghệ thuật biểu trưng\nHồ Tây không chỉ là bối cảnh mà còn là một biểu tượng xuyên suốt bài thơ, gợi lên vẻ đẹp cổ kính, lãng mạn và là chứng nhân cho mối tình của nhân vật. Sự vắng bóng của Sâm Cầm, khúc nhạc sầu, tiếng chuông chùa, gió lay cành liễu đều góp phần tạo nên một không gian trữ tình mang đậm dấu ấn hoài niệm, suy tư và nỗi buồn man mác, đồng điệu với tâm trạng của chủ thể.', 5, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(39, 4, '## Ngôn ngữ & Biểu tượng\n\n**Mô-típ lặp lại:**\n- Mô-típ \"Hồ Tây\" (Tây Hồ, Hồ thu, lối cũ): Không gian trung tâm, nơi neo đậu và lưu giữ ký ức, là chứng nhân cho những kỷ niệm đã qua.\n- Mô-típ \"Mùa thu\" (thuở xưa, chiều thu ấy, thu): Gắn liền với sự chia ly, nỗi buồn man mác, sự chuyển giao của thời gian và những cảm xúc lắng đọng.\n- Mô-típ \"Ký ức\" (hương sen, nụ hôn, áo ướt mưa, hương thầm ngày xưa): Sự sống động của quá khứ trong hiện tại, thể hiện nỗi nhớ da diết và sự trân trọng những kỷ niệm.\n**Biểu tượng & Ẩn dụ:**\n- \"Sâm Cầm vắng bóng\": Biểu tượng cho sự mất mát, những điều đẹp đẽ, quen thuộc của quá khứ đã không còn, gợi nỗi tiếc nuối.\n- \"Sóng miên man khúc nhạc sầu thuở xưa\": Sóng nước gợi nỗi buồn lan tỏa, dường như cả không gian cũng đang đồng điệu trong nỗi nhớ về quá khứ.\n- \"Hương sen trong tóc ướp say tình người\": Hương sen là biểu tượng của tình yêu trong sáng, thuần khiết và sự say đắm, được bảo lưu trong ký ức như một vật ướp hương cho tình yêu vĩnh cửu.\n- \"Tóc đã hoa râm\": Biểu tượng cho sự trôi chảy của thời gian, sự già đi của con người, đối lập với sự tươi trẻ của tình yêu trong ký ức.\n**Yếu tố Thời gian:** Sự vận động thời gian trong bài thơ là phi tuyến tính, đan xen giữa quá khứ (\"thuở xưa\", \"thu ấy\", \"ngày xưa\") và hiện tại (\"bây giờ\"). Quá khứ được hồi tưởng qua hình ảnh, cảm xúc cụ thể (\"hương sen trong tóc\", \"nụ hôn\"), tạo nên một dòng chảy ký ức mạnh mẽ và ám ảnh. Thời gian hiện tại chứng kiến sự già đi của nhân vật (\"tóc đã hoa râm\") nhưng không làm phai mờ nỗi nhớ, ngược lại còn làm sâu sắc thêm sự day dứt và hoài niệm.\n**Thiên nhiên & Phong cảnh:** Không gian chính là Hồ Tây với các yếu tố \"sóng\", \"chuông chùa\", \"gió\", \"cành liễu\", \"lá bay\". Thiên nhiên mang sắc thái buồn man mác của mùa thu, gợi không khí trầm mặc, hoài niệm. \"Gió lay cành liễu\" tạo cảm giác xao xuyến, lay động lòng người, gắn với câu hỏi tu từ \"ai vừa qua đây?\", thể hiện sự kiếm tìm trong vô vọng. \"Hồ thu\" không chỉ là cảnh sắc mà còn là không gian nội tâm, phản chiếu tâm trạng u hoài của nhân vật trữ tình, trở thành một người bạn tri âm.', 6, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(40, 4, '## Gieo vần trong thơ\nBài thơ sử dụng vần lưng và vần chân linh hoạt, tạo nhạc tính và sự hài hòa. Các cặp vần như \"lâu - sầu\", \"đưa - xưa\", \"đây - bay\", \"người - trời\", \"râm - thầm\", \"mưa - xưa\", \"về - mê\" được gieo một cách tự nhiên. Vần trắc xen kẽ vần bằng tạo sự luân chuyển, không gây cảm giác đơn điệu, mà nhấn nhá những cảm xúc riêng, làm cho lời thơ trở nên uyển chuyển và dễ đi vào lòng người.', 7, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(41, 4, '## Mạch cảm xúc\nCảm xúc diễn biến từ nỗi tiếc nuối chung về sự vắng bóng, thay đổi của cảnh vật và thời gian (\"Sâm Cầm vắng bóng\") đến nỗi nhớ cụ thể, mãnh liệt về một mối tình (\"hương sen trong tóc\", \"nụ hôn\"). Sau đó là sự đối diện với hiện tại (\"tóc đã hoa râm\") và khẳng định sự bền vững của ký ức, tình cảm vẫn còn vẹn nguyên (\"vẫn còn vương\", \"vẫn còn thương\"). Cung bậc cảm xúc sâu lắng, trữ tình, thấm đượm nỗi buồn lãng mạn và sự day dứt khôn nguôi.', 8, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(42, 4, '## Cấu trúc bài thơ\nBài thơ gồm 3 khổ, mỗi khổ 4 câu theo thể thơ 4 chữ hoặc biến thể, tạo nên một cấu trúc chặt chẽ và mạch lạc. Khổ 1 mở đầu bằng không gian Hồ Tây tĩnh lặng, buồn bã, gợi sự mất mát và nỗi nhớ mơ hồ. Khổ 2 đi sâu vào hồi ức tình yêu, tái hiện khoảnh khắc lãng mạn của quá khứ. Khổ 3 đối diện với hiện tại và khẳng định sự bền vững của ký ức, sự gắn bó với không gian Hồ Tây, khép lại bằng một dư vị hoài niệm sâu sắc.', 9, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(43, 4, '## Hình ảnh trong thơ\nBài thơ sử dụng đa dạng các hình ảnh: thị giác (\"Sâm Cầm vắng bóng\", \"sóng miên man\", \"gió lay cành liễu\", \"lá bay\", \"tóc đã hoa râm\", \"áo ướt mưa\"), thính giác (\"khúc nhạc sầu\", \"chuông chùa văng vẳng xa đưa\"), khứu giác (\"hương sen trong tóc\", \"hương thầm ngày xưa\"). Các hình ảnh này được phối hợp hài hòa, tạo nên một bức tranh thu lãng mạn, cổ điển và nhuốm màu hoài niệm, giàu tính biểu cảm, chạm đến chiều sâu cảm xúc của người đọc.', 10, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(44, 4, '## Phân tích Nhịp điệu\nNhịp thơ chủ yếu là 2/2 hoặc 4 chữ đều đặn, tạo sự êm đềm, chậm rãi, phù hợp với dòng chảy của ký ức và cảm xúc trầm lắng, hoài niệm. Ngắt nhịp linh hoạt ở các dấu phẩy, dấu chấm lửng (\"Để rồi xa vắng, để rồi…trầm ngâm…\") càng làm tăng tính biểu cảm, gợi sự ngập ngừng, day dứt, sự luyến tiếc không nói hết lời trong nỗi nhớ của nhân vật trữ tình.', 11, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(45, 4, '## Tổ chức không gian & thời gian\nKhông gian nghệ thuật vận động từ không gian rộng lớn của Hồ Tây (sóng, chuông chùa, cành liễu) đến không gian nhỏ hẹp, thân mật của ký ức (hương sen trong tóc, nụ hôn), rồi lại trở về không gian thực tại của \"hồ thu, lối cũ\". Thời gian di chuyển từ quá khứ chung (\"thuở xưa\") đến một khoảnh khắc cụ thể trong quá khứ (\"một chiều thu ấy\") rồi quay về hiện tại (\"bây giờ\") nhưng luôn mang theo dư âm của quá khứ. Sự đan xen này tạo nên một không gian ký ức đa chiều, chồng lấn và vĩnh cửu trong tâm tưởng.', 12, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(46, 4, '## Biện pháp tu từ\nCác biện pháp tu từ được sử dụng hiệu quả: Điệp ngữ \"Để rồi xa vắng, để rồi…trầm ngâm…\", \"Vẫn còn vương chút\", \"Vẫn còn thương áo\" nhấn mạnh sự day dứt, nuối tiếc và sự bền bỉ của tình cảm. Câu hỏi tu từ \"ai vừa qua đây?\" thể hiện sự tìm kiếm, nỗi nhớ bâng khuâng, tạo điểm nhấn suy tư. Ẩn dụ được dùng khéo léo như \"khúc nhạc sầu\", \"hương sen trong tóc ướp say tình người\", \"tóc đã hoa râm\" làm tăng tính gợi cảm và chiều sâu ý nghĩa của tác phẩm.', 13, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(47, 4, '## Bài học\nBài thơ cho thấy sức mạnh của ký ức và sự vĩnh cửu của tình yêu trong tâm hồn con người, dù thời gian và cuộc sống có đổi thay. Nỗi nhớ không chỉ là sự nuối tiếc về những gì đã mất mà còn là nguồn mạch nuôi dưỡng cảm xúc, làm phong phú thêm đời sống nội tâm, giúp con người tìm thấy ý nghĩa và vẻ đẹp trong những trải nghiệm đã qua.', 14, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(48, 4, '## Thông điệp\nThông điệp về giá trị của những kỷ niệm đẹp trong cuộc đời. Dù tình yêu có thể không còn hiện hữu hay người xưa đã xa vắng, nhưng những dấu ấn của nó vẫn sống mãi trong ký ức, trở thành một phần không thể thiếu của bản ngã, nhắc nhở chúng ta về vẻ đẹp của những gì đã từng có và nuôi dưỡng tâm hồn.', 15, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(49, 4, '## Phân tích Liên văn bản\nBài thơ Tản Mạn Tây Hồ là một khúc ca hoài niệm, trầm lắng về tình yêu và ký ức qua không gian Hồ Tây lãng mạn. Chủ đề chính là nỗi nhớ về quá khứ, sự tiếc nuối những gì đã mất và sự trân trọng những kỷ niệm còn vương vấn. Phong cách thơ trữ tình, giàu hình ảnh gợi cảm và cảm xúc sâu lắng, thể hiện tâm trạng bâng khuâng, day dứt của chủ thể trữ tình.\n\n**So sánh với:** Lời Ký Gửi Tình Yêu - Xuân Quỳnh\n\n**Điểm tương đồng:** Cả hai bài thơ đều khắc họa tình yêu và nỗi nhớ da diết. Chúng đều sử dụng những hình ảnh thiên nhiên gần gũi (Hồ Tây, sóng, sen, cành liễu trong Tản Mạn Tây Hồ; biển, sóng, cát trong Lời Ký Gửi Tình Yêu) để biểu đạt cảm xúc nội tâm. Cả hai tác giả đều có giọng điệu trữ tình, sâu lắng, thể hiện sự chân thành trong tình yêu và nỗi nhớ, dù ở những cung bậc khác nhau.\n\n**Điểm khác biệt:** Tản Mạn Tây Hồ mang nặng tính hoài niệm, hướng về một tình yêu đã qua, gắn liền với không gian Hồ Tây cổ kính và một vẻ buồn man mác. Nỗi nhớ ở đây là sự tiếc nuối và níu giữ quá khứ. Trong khi đó, Lời Ký Gửi Tình Yêu của Xuân Quỳnh thể hiện một tình yêu hiện tại mãnh liệt, cháy bỏng, mang tính dâng hiến và khao khát được sẻ chia. Xuân Quỳnh khao khát tình yêu được \"ký gửi\", được trường tồn, mang tính vĩnh cửu và vượt không gian, thời gian. Tản Mạn Tây Hồ mang sắc thái trầm buồn, nội tâm hơn, còn Lời Ký Gửi Tình Yêu mạnh mẽ, bộc lộ trực tiếp và nồng nàn hơn với mong muốn tình yêu không bao giờ mất đi.\n\n**Hiệu quả:** Sự so sánh giúp làm nổi bật chiều sâu của nỗi nhớ trong Tản Mạn Tây Hồ – một nỗi nhớ đã hóa thành một phần của cảnh vật và thời gian, thể hiện sự chấp nhận và trân trọng quá khứ. Đồng thời, nó cũng cho thấy sự đa dạng trong cách thể hiện chủ đề tình yêu và ký ức trong thơ ca Việt Nam, từ những cung bậc hoài niệm thầm kín đến những khát khao mãnh liệt, vĩnh cửu. Tản Mạn Tây Hồ gợi lên sự day dứt, nuối tiếc và vẻ đẹp của sự thanh khiết trong ký ức, trong khi Lời Ký Gửi Tình Yêu mang lại cảm giác về sức sống, sự mãnh liệt và ước vọng bền chặt của tình yêu đôi lứa.', 16, 'user', 388, '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(52, 5, '# TẢN MẠN TÂY HỒ', 1, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(53, 5, '**Từ khóa:** Tây Hồ, hoài niệm, tình yêu, mùa thu, chia ly, ký ức, thời gian, lãng mạn, nỗi buồn', 2, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(54, 5, '## Nỗi Buồn Lãng Mạn Và Hoài Niệm\nBài thơ khắc họa một nỗi buồn sâu lắng, man mác của nhân vật trữ tình khi hồi tưởng về những kỷ niệm đã qua, đặc biệt là một mối tình gắn liền với không gian Hồ Tây. Nỗi buồn không bi lụy mà mang vẻ đẹp lãng mạn, thanh thoát, như một phần tất yếu của dòng chảy cuộc đời và ký ức. Sự vắng bóng của Sâm Cầm, khúc nhạc sầu của sóng, chuông chùa văng vẳng đều là những biểu tượng cho sự tiếc nuối, ngậm ngùi trước cảnh cũ người xưa.', 3, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(55, 5, '## Dấu Ấn Thời Gian Và Ký Ức\nThời gian là một chủ đề trung tâm, được thể hiện rõ nét qua sự đối lập giữa quá khứ tươi đẹp (thuở xưa, một chiều thu ấy) và hiện tại (bây giờ tóc đã hoa râm). Dù thời gian đã khiến con người già đi, cảnh vật có phần thay đổi, nhưng những ký ức về tình yêu, về hương sen trong tóc và nụ hôn thuở nào vẫn còn vẹn nguyên, ám ảnh và dẫn lối về \'lối cũ\'. Ký ức không chỉ là hoài niệm mà còn là một phần sống động, không thể tách rời của hiện tại.', 4, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(56, 5, '## Tình Yêu Và Sự Chia Xa\nBài thơ gợi mở một câu chuyện tình yêu lãng mạn nhưng dang dở, với những khoảnh khắc ngọt ngào (hương sen trong tóc ướp say tình người, nụ hôn trao giữa mây trời) rồi lại chia xa (để rồi xa vắng, để rồi…trầm ngâm). Sự chia xa không được diễn đạt trực tiếp mà thấm đẫm trong nỗi \'trầm ngâm\', \'thương\', \'vương chút hương thầm\', cho thấy tình yêu ấy vẫn còn đó, là một vết hằn sâu trong tâm hồn nhân vật trữ tình, bất chấp dòng chảy của thời gian và số phận.', 5, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(57, 5, '## Ngôn ngữ & Biểu tượng\n\n**Mô-típ lặp lại:**\n- Sóng miên man\n- Chuông chùa\n- Gió lay cành liễu\n- Lá bay\n- Hương sen\n- Áo ướt mưa\n- Hồ thu\n- Lối cũ\n**Biểu tượng & Ẩn dụ:**\n- Sâm Cầm vắng bóng: Biểu tượng cho sự mất mát, đổi thay, những điều đẹp đẽ đã qua đi, hoặc sự cô đơn, trống vắng.\n- Khúc nhạc sầu: Ẩn dụ cho nỗi buồn nội tại, được ngoại hóa qua âm thanh của sóng.\n- Hương sen trong tóc ướp say tình người: Biểu tượng cho ký ức tình yêu tinh khiết, say đắm, và sự lưu giữ kỷ niệm.\n- Nụ hôn trao giữa mây trời: Biểu tượng cho khoảnh khắc thăng hoa, lãng mạn, sự hòa quyện tuyệt đối của tình yêu.\n- Tóc đã hoa râm: Biểu tượng cho dấu ấn của thời gian, tuổi già, sự trải nghiệm và chiêm nghiệm cuộc đời.\n**Yếu tố Thời gian:** Thời gian trong bài thơ vận động theo dòng hồi ức, từ \'thuở xưa\' không xác định đến \'một chiều thu ấy\' đầy kỷ niệm và hiện tại \'bây giờ\' với \'tóc đã hoa râm\'. Sự đan xen giữa quá khứ và hiện tại tạo nên một dòng chảy cảm xúc liên tục, nơi ký ức không ngừng vọng về, chi phối tâm trạng nhân vật trữ tình. Cụm \'để rồi xa vắng, để rồi…trầm ngâm\' gợi một khoảng thời gian dài của nỗi buồn và sự chiêm nghiệm.\n**Thiên nhiên & Phong cảnh:** Không gian Hồ Tây hiện lên với những hình ảnh quen thuộc và giàu sức gợi: sóng, chuông chùa, gió, cành liễu, lá bay, mây trời, hồ thu, lối cũ. Các yếu tố thiên nhiên không chỉ là phông nền mà còn tham gia vào việc biểu đạt cảm xúc: sóng \'miên man khúc nhạc sầu\', gió \'lay cành liễu\' như một câu hỏi bỏ lửng, \'lá bay\' gợi sự tàn phai, \'hồ thu\' và \'lối cũ\' gợi vẻ đẹp u hoài, nơi chốn của những kỷ niệm. Tất cả tạo nên một không gian trữ tình mang vẻ đẹp cô liêu, trầm mặc, thấm đượm nỗi buồn và sự hoài niệm.', 6, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(58, 5, '## Nhân vật\nTuy không có nhân vật rõ ràng, bài thơ tập trung vào nhân vật trữ tình – một cái tôi ẩn danh nhưng giàu nội tâm. Đây là một con người nhạy cảm, lãng mạn, luôn sống trong dòng hồi ức và nỗi buồn man mác về quá khứ. Nhân vật này thể hiện sự trưởng thành qua \'tóc đã hoa râm\' nhưng tâm hồn vẫn vương vấn những kỷ niệm thanh xuân, đặc biệt là tình yêu đầu. Nhân vật là hiện thân của sự hoài niệm, trân trọng những giá trị tinh thần đã qua, chấp nhận nỗi buồn như một phần của cuộc sống.', 7, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(59, 5, '## Không gian\nKhông gian nghệ thuật trung tâm là Hồ Tây, được miêu tả qua các hình ảnh quen thuộc như sóng, chuông chùa, cành liễu, hồ thu, lối cũ. Đây không chỉ là một địa danh mà còn là không gian của ký ức, nơi lưu giữ những kỷ niệm tình yêu \'thuở xưa\'. Hồ Tây trong thơ mang vẻ đẹp cổ kính, lãng mạn nhưng cũng thấm đẫm nỗi buồn, sự cô đơn. \'Lối cũ\' là biểu tượng cho con đường quay về quá khứ, là nơi trú ngụ của những hoài niệm, tạo nên một không gian nội tâm sâu sắc, gắn liền với tâm hồn nhân vật trữ tình.', 8, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(60, 5, '## Thời gian\nThời gian trong bài thơ vận động từ quá khứ xa xôi đến hiện tại, được thể hiện rõ qua các mốc \'thuở xưa\', \'một chiều thu ấy\', và \'bây giờ\'. Thời gian không tuyến tính mà có sự đan xen giữa dòng hồi ức và thực tại. Quá khứ được sống dậy một cách sống động trong ký ức của nhân vật, đối lập với sự thay đổi của cảnh vật và con người ở hiện tại (\'tóc đã hoa râm\'). Dòng thời gian trôi đi, nhưng những kỷ niệm lại được \'ướp say\', \'vương chút hương thầm\', khẳng định sức mạnh và sự bền bỉ của ký ức trước quy luật của tạo hóa. Sự \'xa vắng\' và \'trầm ngâm\' cũng gợi một khoảng thời gian dài của nỗi buồn và sự chiêm nghiệm.', 9, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(61, 5, '## Gieo vần trong thơ\nBài thơ sử dụng chủ yếu vần chân bằng và trắc kết hợp, tạo nên sự uyển chuyển và liền mạch. Cụ thể, các cặp vần như lâu – sầu (câu 1, 2), đưa – vừa (câu 3, 4 khổ 1), bay – say – trời (câu 1, 2, 3 khổ 2), râm – thầm – mưa – đưa – về (vần liên tiếp từ cuối khổ 2 sang khổ 3) cho thấy sự linh hoạt trong việc gieo vần. Nhịp thơ thường là 4/3 hoặc 2/2/3, tạo tiết tấu chậm rãi, trầm buồn, phù hợp với dòng hồi ức và chiêm nghiệm.', 10, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(62, 5, '## Cấu trúc bài thơ\nBài thơ được tổ chức thành ba khổ thơ bốn câu, mỗi khổ là một lát cắt cảm xúc và thời gian, tạo nên một mạch tự sự trữ tình rõ ràng. Khổ 1 mở ra không gian Hồ Tây với nỗi buồn hoài niệm chung. Khổ 2 hồi tưởng chi tiết về một kỷ niệm tình yêu đã qua. Khổ 3 quay về hiện tại, khẳng định sự bền bỉ của ký ức và nỗi vương vấn. Cấu trúc này giúp dẫn dắt cảm xúc người đọc một cách tự nhiên, từ khái quát đến cụ thể, rồi lại trở về hiện tại với những lắng đọng sâu sắc, tạo sự thống nhất trong cảm xúc và chủ đề.', 11, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(63, 5, '## Phân tích Nhịp điệu\nNhịp điệu thơ chủ yếu là 4/3 hoặc 2/2/3 trong các câu 7 chữ, mang lại một tiết tấu chậm rãi, đều đặn, tạo cảm giác nhẹ nhàng, suy tư và hoài niệm. Sự ngắt nhịp ở giữa câu (\'Gió lay cành liễu, - ai vừa qua đây?\', \'Để rồi xa vắng, để rồi…trầm ngâm…\') tạo ra những khoảng lặng, nhấn mạnh cảm xúc bâng khuâng, day dứt của nhân vật trữ tình, góp phần tạo nên nhạc tính trầm lắng, sâu lắng cho bài thơ.', 12, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(64, 5, '## Tổ chức không gian & thời gian\nKhông gian trữ tình là Hồ Tây – một không gian vừa cụ thể vừa mang tính biểu tượng, gắn liền với Hà Nội và những hoài niệm. Không gian này được tái hiện qua các hình ảnh: sóng, chuông chùa, cành liễu, hồ thu, lối cũ, tạo nên một khung cảnh cổ kính, lãng mạn và u hoài. Thời gian vận động từ quá khứ xa xôi (\'thuở xưa\', \'một chiều thu ấy\') đến hiện tại (\'bây giờ tóc đã hoa râm\'), thể hiện sự chảy trôi của cuộc đời nhưng cũng là sự lưu giữ bền bỉ của ký ức. Sự đan xen giữa cảnh sắc Hồ Tây và dòng chảy thời gian tạo nên một không gian – thời gian nghệ thuật mang tính chiều sâu, nơi ký ức và hiện tại giao thoa.', 13, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(65, 5, '## Bài học\nCuộc đời là một dòng chảy không ngừng, nơi thời gian sẽ làm phai nhạt nhiều thứ nhưng không thể xóa nhòa những ký ức sâu sắc về tình yêu và tuổi trẻ. Nỗi hoài niệm không chỉ là sự tiếc nuối mà còn là cách con người trân trọng quá khứ, tìm thấy sự an ủi và ý nghĩa trong những gì đã qua, để rồi từ đó thấu hiểu hơn về bản thân và hành trình sống của mình. Ký ức, dù buồn, vẫn là một kho báu tinh thần quý giá.', 14, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(66, 5, '## Thông điệp\nBài thơ truyền tải thông điệp về giá trị vĩnh cửu của ký ức và tình yêu trong dòng chảy của thời gian. Nỗi buồn hoài niệm không phải là sự yếu đuối mà là một trạng thái cảm xúc nhân văn, giúp con người kết nối với quá khứ, sống chậm lại để chiêm nghiệm những giá trị tinh thần. Dù cảnh vật đổi thay, con người già đi, nhưng những cảm xúc chân thành, những kỷ niệm đẹp đẽ vẫn là điểm tựa tinh thần, là con đường để tâm hồn tìm về chốn bình yên, dù chỉ trong hồi ức.', 15, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(67, 5, '## Phân tích Liên văn bản\nTản mạn Tây Hồ là bài thơ đậm chất trữ tình, khai thác chủ đề tình yêu, ký ức và sự hoài niệm trước những đổi thay của thời gian và cảnh vật. Cảm xúc chủ đạo là nỗi buồn man mác, tiếc nuối một mối tình đã qua, được thể hiện qua các hình ảnh thiên nhiên và không gian quen thuộc của Hồ Tây. Phong cách thơ giản dị nhưng sâu lắng, giàu tính biểu cảm, thấm đẫm chất tự sự trữ tình.\n\n**So sánh với:** Vội Vàng - Xuân Diệu\n\n**Điểm tương đồng:** Cả hai bài thơ đều thể hiện một cảm thức sâu sắc về thời gian và sự trôi chảy của nó, đồng thời gắn kết chặt chẽ cảm xúc con người với thiên nhiên. Nỗi buồn, sự tiếc nuối trước sự vô thường là điểm chung trong tâm trạng chủ đạo của hai tác phẩm, mặc dù cách biểu hiện và thái độ có sự khác biệt rõ rệt.\n\n**Điểm khác biệt:** Trong khi \'Tản mạn Tây Hồ\' mang giọng điệu trầm buồn, hoài niệm, tập trung vào sự tiếc nuối những gì đã mất và sự kiên trì của ký ức, với thời gian quá khứ ám ảnh hiện tại. \'Vội Vàng\' của Xuân Diệu lại thể hiện một cảm xúc gấp gáp, khao khát sống mãnh liệt, sợ hãi trước sự trôi chảy của thời gian và sự mất mát của tuổi trẻ, muốn níu giữ từng khoảnh khắc hiện tại. Thời gian trong \'Vội Vàng\' là thời gian hiện tại đầy rẫy sự vô thường. \'Tản mạn Tây Hồ\' chấp nhận nỗi buồn để sống cùng ký ức, trong khi \'Vội Vàng\' lại muốn chống lại thời gian để tận hưởng hết mình, đẩy lùi sự già nua và cái chết.\n\n**Hiệu quả:** Việc so sánh giúp làm nổi bật chiều sâu và đa dạng của cảm xúc trữ tình trong văn học Việt Nam. \'Tản mạn Tây Hồ\' khẳng định giá trị của ký ức và sự hoài niệm như một cách để con người tìm thấy ý nghĩa trong cuộc sống, một vẻ đẹp trầm lắng, chấp nhận sự đổi thay. Ngược lại, \'Vội Vàng\' lại thúc giục người đọc sống trọn vẹn, khai thác tận cùng vẻ đẹp của cuộc sống trước khi nó vụt mất, thể hiện một vẻ đẹp rực rỡ, bùng cháy và thái độ chủ động trước sinh mệnh. Hai tác phẩm, tuy khác biệt về thái độ trước thời gian, nhưng đều chạm đến những khía cạnh sâu sắc của tâm hồn con người và tình yêu cuộc sống.', 16, 'user', 378, '2025-10-26 13:45:53', '2025-10-26 13:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `deliver`
--

CREATE TABLE `deliver` (
  `id_deliver` int NOT NULL,
  `task_id` int NOT NULL,
  `type` enum('class','group','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int NOT NULL COMMENT 'Phân công đối tượng nào thì sẽ truyền vào value đối tượng đó',
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliver`
--

INSERT INTO `deliver` (`id_deliver`, `task_id`, `type`, `value`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 5, 'user', 358, 2, '2025-10-25 15:24:55', '2025-10-25 15:26:50'),
(2, 5, 'user', 371, 2, '2025-10-25 15:24:55', '2025-10-25 15:26:52'),
(4, 5, 'class', 1, 2, '2025-10-25 15:24:55', '2025-10-25 15:26:55'),
(7, 5, 'group', 2, 2, '2025-10-26 22:37:44', '2025-10-26 22:37:44'),
(8, 5, 'group', 3, 2, '2025-10-26 22:37:44', '2025-10-26 22:37:44'),
(9, 6, 'class', 1, 2, '2025-10-27 04:16:17', '2025-10-27 04:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `detail_evaluate`
--

CREATE TABLE `detail_evaluate` (
  `id_detail_eval` int NOT NULL,
  `tc_id` int NOT NULL,
  `diem` int NOT NULL,
  `id_evaluate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluate`
--

CREATE TABLE `evaluate` (
  `id_evaluate` int NOT NULL,
  `task_id` int NOT NULL,
  `evaluate_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_score` decimal(6,2) NOT NULL DEFAULT '10.00',
  `id_assignment` int NOT NULL,
  `response_id` int DEFAULT NULL,
  `number` int DEFAULT NULL,
  `object` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluate_type`
--

CREATE TABLE `evaluate_type` (
  `id_eval_type` int NOT NULL,
  `name_eval_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generate`
--

CREATE TABLE `generate` (
  `id_gen` int NOT NULL,
  `name_gen` varchar(255) DEFAULT NULL,
  `prompt_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE `group` (
  `group_id` int NOT NULL,
  `class_id` int NOT NULL,
  `group_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group`
--

INSERT INTO `group` (`group_id`, `class_id`, `group_name`, `created_at`, `updated_at`) VALUES
(2, 1, 'Nhóm 2', '2025-10-25 02:23:29', '2025-10-25 02:23:29'),
(3, 1, 'Nhóm 3', '2025-10-26 08:18:53', '2025-10-26 08:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `group_member`
--

CREATE TABLE `group_member` (
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `added_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `group_member`
--

INSERT INTO `group_member` (`group_id`, `user_id`, `added_by`, `created_at`, `updated_at`) VALUES
(2, 362, 2, '2025-10-26 12:45:26', '2025-10-26 12:45:26'),
(2, 377, 2, '2025-10-26 12:45:26', '2025-10-26 12:45:26'),
(2, 386, 2, '2025-10-26 22:58:38', '2025-10-26 22:58:38'),
(3, 359, 2, '2025-10-26 12:45:48', '2025-10-26 12:45:48'),
(3, 371, 2, '2025-10-26 12:45:48', '2025-10-26 12:45:48'),
(3, 381, 2, '2025-10-26 12:45:48', '2025-10-26 12:45:48');

-- --------------------------------------------------------

--
-- Table structure for table `main_phase`
--

CREATE TABLE `main_phase` (
  `m_phase_id` int NOT NULL,
  `m_phase_name` varchar(100) NOT NULL,
  `phase_code` varchar(100) NOT NULL,
  `AI` tinyint(1) NOT NULL,
  `Comment` tinyint(1) NOT NULL,
  `Edit` tinyint(1) NOT NULL,
  `teacher` tinyint(1) NOT NULL,
  `student` tinyint(1) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `main_phase`
--

INSERT INTO `main_phase` (`m_phase_id`, `m_phase_name`, `phase_code`, `AI`, `Comment`, `Edit`, `teacher`, `student`, `description`) VALUES
(1, 'Thực hiện nhiệm vụ (sử dụng công cụ AI và tự phản biện)', 'PHASE_START', 1, 1, 0, 0, 1, 'Người học sử dụng công cụ AI để tạo ra nội dung phân tích văn học. Sau đó người học sẽ tự phản biện các nội dung được tạo ra'),
(2, 'Chữa bài', 'PHASE_CHUABAI', 0, 1, 0, 1, 1, 'Người học và giáo viên chữa bài bằng cách bình luận vào các nội dung cần sửa'),
(3, 'Làm bài tập về nhà (công cụ AI)', 'PHASE_BTVN', 1, 1, 1, 0, 1, 'Người học thực hiện yêu cầu được giao'),
(4, 'Đánh giá đồng đẳng', 'PHASE_DANHGIANGUOIHOC', 0, 1, 0, 0, 1, 'Người được phân công đánh giá sẽ đánh giá điểm cho từng bài được chỉ định'),
(5, 'Chấm điểm', 'PHASE_CHAMDIEM', 0, 1, 0, 1, 0, 'Giáo viên chấm điểm kết luận cho các bài');

-- --------------------------------------------------------

--
-- Table structure for table `nhom_tc`
--

CREATE TABLE `nhom_tc` (
  `nhom_tc_id` int NOT NULL,
  `ten_nhom_tc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `object_review`
--

CREATE TABLE `object_review` (
  `id_obj_review` int NOT NULL,
  `target_type` enum('user','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int NOT NULL,
  `id_evaluate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phase`
--

CREATE TABLE `phase` (
  `phase_id` int NOT NULL,
  `phase_number` int DEFAULT NULL,
  `phase_name` varchar(100) DEFAULT NULL,
  `m_phase_id` int DEFAULT NULL,
  `topic_id` int DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `flag_active` tinyint DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `phase`
--

INSERT INTO `phase` (`phase_id`, `phase_number`, `phase_name`, `m_phase_id`, `topic_id`, `start_time`, `end_time`, `flag_active`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Phản biện nội dung tìm hiểu về thơ ABC', 1, 8, '2025-10-25 12:10:00', '2025-10-25 12:15:00', 0, 2, '2025-10-25 12:10:49', '2025-10-25 12:28:01'),
(2, 2, 'Chữa nội dung phản biện', 2, 8, '2025-10-25 12:32:00', '2025-10-25 12:52:00', 0, 2, '2025-10-25 12:30:11', '2025-10-25 12:32:28'),
(3, 3, 'Làm bài tập về nhà', 3, 8, '2025-10-25 12:48:00', '2025-10-25 12:59:00', 0, 2, '2025-10-25 12:33:02', '2025-10-25 12:37:30'),
(4, 4, 'Đánh giá chéo bài tập', 4, 8, '2025-10-10 23:07:00', '2025-11-01 23:07:00', 0, 2, '2025-10-25 23:07:28', '2025-10-25 23:07:44'),
(5, 5, 'Chấm điểm', 5, 8, '2025-10-26 13:43:00', '2025-10-30 13:46:00', 0, 2, '2025-10-26 13:41:39', '2025-10-26 13:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `response`
--

CREATE TABLE `response` (
  `id_response` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `owner_user_id` int DEFAULT NULL,
  `parent_response` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `json_ai` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `response`
--

INSERT INTO `response` (`id_response`, `user_id`, `owner_user_id`, `parent_response`, `task_id`, `json_ai`, `created_at`, `updated_at`) VALUES
(4, 388, NULL, NULL, 5, '{\"stats\": {\"topWords\": [[\"ng\", 15], [\"n\", 10], [\"m\", 10], [\"v\", 10], [\"t\", 9]], \"charCount\": 395, \"wordCount\": 138}, \"title\": \"TẢN MẠN TÂY HỒ\", \"keywords\": [\"Tây Hồ\", \"hoài niệm\", \"tình yêu\", \"mùa thu\", \"sen\", \"chia ly\", \"ký ức\", \"thời gian\"], \"poetryAnalysis\": {\"rhythm\": \"Nhịp thơ chủ yếu là 2/2 hoặc 4 chữ đều đặn, tạo sự êm đềm, chậm rãi, phù hợp với dòng chảy của ký ức và cảm xúc trầm lắng, hoài niệm. Ngắt nhịp linh hoạt ở các dấu phẩy, dấu chấm lửng (\\\"Để rồi xa vắng, để rồi…trầm ngâm…\\\") càng làm tăng tính biểu cảm, gợi sự ngập ngừng, day dứt, sự luyến tiếc không nói hết lời trong nỗi nhớ của nhân vật trữ tình.\", \"imagery\": \"Bài thơ sử dụng đa dạng các hình ảnh: thị giác (\\\"Sâm Cầm vắng bóng\\\", \\\"sóng miên man\\\", \\\"gió lay cành liễu\\\", \\\"lá bay\\\", \\\"tóc đã hoa râm\\\", \\\"áo ướt mưa\\\"), thính giác (\\\"khúc nhạc sầu\\\", \\\"chuông chùa văng vẳng xa đưa\\\"), khứu giác (\\\"hương sen trong tóc\\\", \\\"hương thầm ngày xưa\\\"). Các hình ảnh này được phối hợp hài hòa, tạo nên một bức tranh thu lãng mạn, cổ điển và nhuốm màu hoài niệm, giàu tính biểu cảm, chạm đến chiều sâu cảm xúc của người đọc.\", \"rhyming\": \"Bài thơ sử dụng vần lưng và vần chân linh hoạt, tạo nhạc tính và sự hài hòa. Các cặp vần như \\\"lâu - sầu\\\", \\\"đưa - xưa\\\", \\\"đây - bay\\\", \\\"người - trời\\\", \\\"râm - thầm\\\", \\\"mưa - xưa\\\", \\\"về - mê\\\" được gieo một cách tự nhiên. Vần trắc xen kẽ vần bằng tạo sự luân chuyển, không gây cảm giác đơn điệu, mà nhấn nhá những cảm xúc riêng, làm cho lời thơ trở nên uyển chuyển và dễ đi vào lòng người.\", \"structure\": \"Bài thơ gồm 3 khổ, mỗi khổ 4 câu theo thể thơ 4 chữ hoặc biến thể, tạo nên một cấu trúc chặt chẽ và mạch lạc. Khổ 1 mở đầu bằng không gian Hồ Tây tĩnh lặng, buồn bã, gợi sự mất mát và nỗi nhớ mơ hồ. Khổ 2 đi sâu vào hồi ức tình yêu, tái hiện khoảnh khắc lãng mạn của quá khứ. Khổ 3 đối diện với hiện tại và khẳng định sự bền vững của ký ức, sự gắn bó với không gian Hồ Tây, khép lại bằng một dư vị hoài niệm sâu sắc.\", \"emotionalFlow\": \"Cảm xúc diễn biến từ nỗi tiếc nuối chung về sự vắng bóng, thay đổi của cảnh vật và thời gian (\\\"Sâm Cầm vắng bóng\\\") đến nỗi nhớ cụ thể, mãnh liệt về một mối tình (\\\"hương sen trong tóc\\\", \\\"nụ hôn\\\"). Sau đó là sự đối diện với hiện tại (\\\"tóc đã hoa râm\\\") và khẳng định sự bền vững của ký ức, tình cảm vẫn còn vẹn nguyên (\\\"vẫn còn vương\\\", \\\"vẫn còn thương\\\"). Cung bậc cảm xúc sâu lắng, trữ tình, thấm đượm nỗi buồn lãng mạn và sự day dứt khôn nguôi.\", \"rhetoricalDevices\": \"Các biện pháp tu từ được sử dụng hiệu quả: Điệp ngữ \\\"Để rồi xa vắng, để rồi…trầm ngâm…\\\", \\\"Vẫn còn vương chút\\\", \\\"Vẫn còn thương áo\\\" nhấn mạnh sự day dứt, nuối tiếc và sự bền bỉ của tình cảm. Câu hỏi tu từ \\\"ai vừa qua đây?\\\" thể hiện sự tìm kiếm, nỗi nhớ bâng khuâng, tạo điểm nhấn suy tư. Ẩn dụ được dùng khéo léo như \\\"khúc nhạc sầu\\\", \\\"hương sen trong tóc ướp say tình người\\\", \\\"tóc đã hoa râm\\\" làm tăng tính gợi cảm và chiều sâu ý nghĩa của tác phẩm.\", \"spaceTimeOrganization\": \"Không gian nghệ thuật vận động từ không gian rộng lớn của Hồ Tây (sóng, chuông chùa, cành liễu) đến không gian nhỏ hẹp, thân mật của ký ức (hương sen trong tóc, nụ hôn), rồi lại trở về không gian thực tại của \\\"hồ thu, lối cũ\\\". Thời gian di chuyển từ quá khứ chung (\\\"thuở xưa\\\") đến một khoảnh khắc cụ thể trong quá khứ (\\\"một chiều thu ấy\\\") rồi quay về hiện tại (\\\"bây giờ\\\") nhưng luôn mang theo dư âm của quá khứ. Sự đan xen này tạo nên một không gian ký ức đa chiều, chồng lấn và vĩnh cửu trong tâm tưởng.\"}, \"characteristics\": {\"Phong cách\": \"Trữ tình, hoài niệm, lãng mạn\", \"Thể loại\": \"Thơ – Thơ hiện đại, trữ tình\", \"Giọng điệu\": \"Trầm buồn, tiếc nuối, suy tư, hoài cổ\"}, \"generalAnalysis\": {\"thematicAnalysis\": [{\"content\": \"Bài thơ là dòng tâm sự của một người đang hồi tưởng về mối tình xưa tại Hồ Tây. Nỗi nhớ không chỉ là về một người cụ thể mà còn là nỗi nhớ về không gian, thời gian và những cảm xúc mãnh liệt đã từng có. Thời gian trôi đi, mọi vật đổi thay nhưng ký ức về tình yêu đầu vẫn còn vẹn nguyên, ám ảnh trong tâm trí, trở thành một phần không thể thiếu trong đời sống tinh thần của nhân vật trữ tình.\", \"heading\": \"Nỗi nhớ và sự hoài niệm về tình yêu đã qua\"}, {\"content\": \"Thời gian được nhắc đến qua các cụm từ như \\\"thuở xưa\\\", \\\"thu ấy\\\", \\\"bây giờ tóc đã hoa râm\\\", cho thấy sự chảy trôi khắc nghiệt của thời gian, mang theo tuổi tác và sự đổi thay. Tuy nhiên, những ký ức về tình yêu, hương sen, nụ hôn lại mang một vẻ đẹp vĩnh cửu, không phai mờ theo năm tháng, trở thành điểm tựa tinh thần, nguồn an ủi và nỗi day dứt thường trực cho nhân vật trữ tình.\", \"heading\": \"Sự hữu hạn của thời gian và vẻ đẹp vĩnh cửu của ký ức\"}, {\"content\": \"Hồ Tây không chỉ là bối cảnh mà còn là một biểu tượng xuyên suốt bài thơ, gợi lên vẻ đẹp cổ kính, lãng mạn và là chứng nhân cho mối tình của nhân vật. Sự vắng bóng của Sâm Cầm, khúc nhạc sầu, tiếng chuông chùa, gió lay cành liễu đều góp phần tạo nên một không gian trữ tình mang đậm dấu ấn hoài niệm, suy tư và nỗi buồn man mác, đồng điệu với tâm trạng của chủ thể.\", \"heading\": \"Hồ Tây – Không gian nghệ thuật biểu trưng\"}], \"linguisticInsights\": {\"motifs\": [\"Mô-típ \\\"Hồ Tây\\\" (Tây Hồ, Hồ thu, lối cũ): Không gian trung tâm, nơi neo đậu và lưu giữ ký ức, là chứng nhân cho những kỷ niệm đã qua.\", \"Mô-típ \\\"Mùa thu\\\" (thuở xưa, chiều thu ấy, thu): Gắn liền với sự chia ly, nỗi buồn man mác, sự chuyển giao của thời gian và những cảm xúc lắng đọng.\", \"Mô-típ \\\"Ký ức\\\" (hương sen, nụ hôn, áo ướt mưa, hương thầm ngày xưa): Sự sống động của quá khứ trong hiện tại, thể hiện nỗi nhớ da diết và sự trân trọng những kỷ niệm.\"], \"natureAndScenery\": \"Không gian chính là Hồ Tây với các yếu tố \\\"sóng\\\", \\\"chuông chùa\\\", \\\"gió\\\", \\\"cành liễu\\\", \\\"lá bay\\\". Thiên nhiên mang sắc thái buồn man mác của mùa thu, gợi không khí trầm mặc, hoài niệm. \\\"Gió lay cành liễu\\\" tạo cảm giác xao xuyến, lay động lòng người, gắn với câu hỏi tu từ \\\"ai vừa qua đây?\\\", thể hiện sự kiếm tìm trong vô vọng. \\\"Hồ thu\\\" không chỉ là cảnh sắc mà còn là không gian nội tâm, phản chiếu tâm trạng u hoài của nhân vật trữ tình, trở thành một người bạn tri âm.\", \"temporalElements\": \"Sự vận động thời gian trong bài thơ là phi tuyến tính, đan xen giữa quá khứ (\\\"thuở xưa\\\", \\\"thu ấy\\\", \\\"ngày xưa\\\") và hiện tại (\\\"bây giờ\\\"). Quá khứ được hồi tưởng qua hình ảnh, cảm xúc cụ thể (\\\"hương sen trong tóc\\\", \\\"nụ hôn\\\"), tạo nên một dòng chảy ký ức mạnh mẽ và ám ảnh. Thời gian hiện tại chứng kiến sự già đi của nhân vật (\\\"tóc đã hoa râm\\\") nhưng không làm phai mờ nỗi nhớ, ngược lại còn làm sâu sắc thêm sự day dứt và hoài niệm.\", \"symbolsAndMetaphors\": [\"\\\"Sâm Cầm vắng bóng\\\": Biểu tượng cho sự mất mát, những điều đẹp đẽ, quen thuộc của quá khứ đã không còn, gợi nỗi tiếc nuối.\", \"\\\"Sóng miên man khúc nhạc sầu thuở xưa\\\": Sóng nước gợi nỗi buồn lan tỏa, dường như cả không gian cũng đang đồng điệu trong nỗi nhớ về quá khứ.\", \"\\\"Hương sen trong tóc ướp say tình người\\\": Hương sen là biểu tượng của tình yêu trong sáng, thuần khiết và sự say đắm, được bảo lưu trong ký ức như một vật ướp hương cho tình yêu vĩnh cửu.\", \"\\\"Tóc đã hoa râm\\\": Biểu tượng cho sự trôi chảy của thời gian, sự già đi của con người, đối lập với sự tươi trẻ của tình yêu trong ký ức.\"]}}, \"intertextuality\": {\"comparison\": {\"effects\": \"Sự so sánh giúp làm nổi bật chiều sâu của nỗi nhớ trong Tản Mạn Tây Hồ – một nỗi nhớ đã hóa thành một phần của cảnh vật và thời gian, thể hiện sự chấp nhận và trân trọng quá khứ. Đồng thời, nó cũng cho thấy sự đa dạng trong cách thể hiện chủ đề tình yêu và ký ức trong thơ ca Việt Nam, từ những cung bậc hoài niệm thầm kín đến những khát khao mãnh liệt, vĩnh cửu. Tản Mạn Tây Hồ gợi lên sự day dứt, nuối tiếc và vẻ đẹp của sự thanh khiết trong ký ức, trong khi Lời Ký Gửi Tình Yêu mang lại cảm giác về sức sống, sự mãnh liệt và ước vọng bền chặt của tình yêu đôi lứa.\", \"differences\": \"Tản Mạn Tây Hồ mang nặng tính hoài niệm, hướng về một tình yêu đã qua, gắn liền với không gian Hồ Tây cổ kính và một vẻ buồn man mác. Nỗi nhớ ở đây là sự tiếc nuối và níu giữ quá khứ. Trong khi đó, Lời Ký Gửi Tình Yêu của Xuân Quỳnh thể hiện một tình yêu hiện tại mãnh liệt, cháy bỏng, mang tính dâng hiến và khao khát được sẻ chia. Xuân Quỳnh khao khát tình yêu được \\\"ký gửi\\\", được trường tồn, mang tính vĩnh cửu và vượt không gian, thời gian. Tản Mạn Tây Hồ mang sắc thái trầm buồn, nội tâm hơn, còn Lời Ký Gửi Tình Yêu mạnh mẽ, bộc lộ trực tiếp và nồng nàn hơn với mong muốn tình yêu không bao giờ mất đi.\", \"similarities\": \"Cả hai bài thơ đều khắc họa tình yêu và nỗi nhớ da diết. Chúng đều sử dụng những hình ảnh thiên nhiên gần gũi (Hồ Tây, sóng, sen, cành liễu trong Tản Mạn Tây Hồ; biển, sóng, cát trong Lời Ký Gửi Tình Yêu) để biểu đạt cảm xúc nội tâm. Cả hai tác giả đều có giọng điệu trữ tình, sâu lắng, thể hiện sự chân thành trong tình yêu và nỗi nhớ, dù ở những cung bậc khác nhau.\"}, \"comparativeText\": {\"title\": \"Lời Ký Gửi Tình Yêu\", \"author\": \"Xuân Quỳnh\"}, \"initialAnalysis\": \"Bài thơ Tản Mạn Tây Hồ là một khúc ca hoài niệm, trầm lắng về tình yêu và ký ức qua không gian Hồ Tây lãng mạn. Chủ đề chính là nỗi nhớ về quá khứ, sự tiếc nuối những gì đã mất và sự trân trọng những kỷ niệm còn vương vấn. Phong cách thơ trữ tình, giàu hình ảnh gợi cảm và cảm xúc sâu lắng, thể hiện tâm trạng bâng khuâng, day dứt của chủ thể trữ tình.\"}, \"concludingThoughts\": {\"lesson\": \"Bài thơ cho thấy sức mạnh của ký ức và sự vĩnh cửu của tình yêu trong tâm hồn con người, dù thời gian và cuộc sống có đổi thay. Nỗi nhớ không chỉ là sự nuối tiếc về những gì đã mất mà còn là nguồn mạch nuôi dưỡng cảm xúc, làm phong phú thêm đời sống nội tâm, giúp con người tìm thấy ý nghĩa và vẻ đẹp trong những trải nghiệm đã qua.\", \"message\": \"Thông điệp về giá trị của những kỷ niệm đẹp trong cuộc đời. Dù tình yêu có thể không còn hiện hữu hay người xưa đã xa vắng, nhưng những dấu ấn của nó vẫn sống mãi trong ký ức, trở thành một phần không thể thiếu của bản ngã, nhắc nhở chúng ta về vẻ đẹp của những gì đã từng có và nuôi dưỡng tâm hồn.\"}}', '2025-10-26 11:24:19', '2025-10-26 11:24:19'),
(5, 378, NULL, NULL, 5, '{\"stats\": {\"topWords\": [[\"ng\", 15], [\"n\", 10], [\"m\", 10], [\"v\", 10], [\"t\", 9]], \"charCount\": 395, \"wordCount\": 138}, \"title\": \"TẢN MẠN TÂY HỒ\", \"keywords\": [\"Tây Hồ\", \"hoài niệm\", \"tình yêu\", \"mùa thu\", \"chia ly\", \"ký ức\", \"thời gian\", \"lãng mạn\", \"nỗi buồn\"], \"storyAnalysis\": {\"time\": \"Thời gian trong bài thơ vận động từ quá khứ xa xôi đến hiện tại, được thể hiện rõ qua các mốc \'thuở xưa\', \'một chiều thu ấy\', và \'bây giờ\'. Thời gian không tuyến tính mà có sự đan xen giữa dòng hồi ức và thực tại. Quá khứ được sống dậy một cách sống động trong ký ức của nhân vật, đối lập với sự thay đổi của cảnh vật và con người ở hiện tại (\'tóc đã hoa râm\'). Dòng thời gian trôi đi, nhưng những kỷ niệm lại được \'ướp say\', \'vương chút hương thầm\', khẳng định sức mạnh và sự bền bỉ của ký ức trước quy luật của tạo hóa. Sự \'xa vắng\' và \'trầm ngâm\' cũng gợi một khoảng thời gian dài của nỗi buồn và sự chiêm nghiệm.\", \"space\": \"Không gian nghệ thuật trung tâm là Hồ Tây, được miêu tả qua các hình ảnh quen thuộc như sóng, chuông chùa, cành liễu, hồ thu, lối cũ. Đây không chỉ là một địa danh mà còn là không gian của ký ức, nơi lưu giữ những kỷ niệm tình yêu \'thuở xưa\'. Hồ Tây trong thơ mang vẻ đẹp cổ kính, lãng mạn nhưng cũng thấm đẫm nỗi buồn, sự cô đơn. \'Lối cũ\' là biểu tượng cho con đường quay về quá khứ, là nơi trú ngụ của những hoài niệm, tạo nên một không gian nội tâm sâu sắc, gắn liền với tâm hồn nhân vật trữ tình.\", \"character\": \"Tuy không có nhân vật rõ ràng, bài thơ tập trung vào nhân vật trữ tình – một cái tôi ẩn danh nhưng giàu nội tâm. Đây là một con người nhạy cảm, lãng mạn, luôn sống trong dòng hồi ức và nỗi buồn man mác về quá khứ. Nhân vật này thể hiện sự trưởng thành qua \'tóc đã hoa râm\' nhưng tâm hồn vẫn vương vấn những kỷ niệm thanh xuân, đặc biệt là tình yêu đầu. Nhân vật là hiện thân của sự hoài niệm, trân trọng những giá trị tinh thần đã qua, chấp nhận nỗi buồn như một phần của cuộc sống.\"}, \"poetryAnalysis\": {\"rhythm\": \"Nhịp điệu thơ chủ yếu là 4/3 hoặc 2/2/3 trong các câu 7 chữ, mang lại một tiết tấu chậm rãi, đều đặn, tạo cảm giác nhẹ nhàng, suy tư và hoài niệm. Sự ngắt nhịp ở giữa câu (\'Gió lay cành liễu, - ai vừa qua đây?\', \'Để rồi xa vắng, để rồi…trầm ngâm…\') tạo ra những khoảng lặng, nhấn mạnh cảm xúc bâng khuâng, day dứt của nhân vật trữ tình, góp phần tạo nên nhạc tính trầm lắng, sâu lắng cho bài thơ.\", \"rhyming\": \"Bài thơ sử dụng chủ yếu vần chân bằng và trắc kết hợp, tạo nên sự uyển chuyển và liền mạch. Cụ thể, các cặp vần như lâu – sầu (câu 1, 2), đưa – vừa (câu 3, 4 khổ 1), bay – say – trời (câu 1, 2, 3 khổ 2), râm – thầm – mưa – đưa – về (vần liên tiếp từ cuối khổ 2 sang khổ 3) cho thấy sự linh hoạt trong việc gieo vần. Nhịp thơ thường là 4/3 hoặc 2/2/3, tạo tiết tấu chậm rãi, trầm buồn, phù hợp với dòng hồi ức và chiêm nghiệm.\", \"structure\": \"Bài thơ được tổ chức thành ba khổ thơ bốn câu, mỗi khổ là một lát cắt cảm xúc và thời gian, tạo nên một mạch tự sự trữ tình rõ ràng. Khổ 1 mở ra không gian Hồ Tây với nỗi buồn hoài niệm chung. Khổ 2 hồi tưởng chi tiết về một kỷ niệm tình yêu đã qua. Khổ 3 quay về hiện tại, khẳng định sự bền bỉ của ký ức và nỗi vương vấn. Cấu trúc này giúp dẫn dắt cảm xúc người đọc một cách tự nhiên, từ khái quát đến cụ thể, rồi lại trở về hiện tại với những lắng đọng sâu sắc, tạo sự thống nhất trong cảm xúc và chủ đề.\", \"spaceTimeOrganization\": \"Không gian trữ tình là Hồ Tây – một không gian vừa cụ thể vừa mang tính biểu tượng, gắn liền với Hà Nội và những hoài niệm. Không gian này được tái hiện qua các hình ảnh: sóng, chuông chùa, cành liễu, hồ thu, lối cũ, tạo nên một khung cảnh cổ kính, lãng mạn và u hoài. Thời gian vận động từ quá khứ xa xôi (\'thuở xưa\', \'một chiều thu ấy\') đến hiện tại (\'bây giờ tóc đã hoa râm\'), thể hiện sự chảy trôi của cuộc đời nhưng cũng là sự lưu giữ bền bỉ của ký ức. Sự đan xen giữa cảnh sắc Hồ Tây và dòng chảy thời gian tạo nên một không gian – thời gian nghệ thuật mang tính chiều sâu, nơi ký ức và hiện tại giao thoa.\"}, \"characteristics\": {\"Phong cách\": \"Trữ tình, lãng mạn, hoài niệm\", \"Thể loại\": \"Thơ – Thơ 7 chữ\", \"Giọng điệu\": \"Buồn man mác, trầm lắng, hoài cổ\"}, \"generalAnalysis\": {\"thematicAnalysis\": [{\"content\": \"Bài thơ khắc họa một nỗi buồn sâu lắng, man mác của nhân vật trữ tình khi hồi tưởng về những kỷ niệm đã qua, đặc biệt là một mối tình gắn liền với không gian Hồ Tây. Nỗi buồn không bi lụy mà mang vẻ đẹp lãng mạn, thanh thoát, như một phần tất yếu của dòng chảy cuộc đời và ký ức. Sự vắng bóng của Sâm Cầm, khúc nhạc sầu của sóng, chuông chùa văng vẳng đều là những biểu tượng cho sự tiếc nuối, ngậm ngùi trước cảnh cũ người xưa.\", \"heading\": \"Nỗi Buồn Lãng Mạn Và Hoài Niệm\"}, {\"content\": \"Thời gian là một chủ đề trung tâm, được thể hiện rõ nét qua sự đối lập giữa quá khứ tươi đẹp (thuở xưa, một chiều thu ấy) và hiện tại (bây giờ tóc đã hoa râm). Dù thời gian đã khiến con người già đi, cảnh vật có phần thay đổi, nhưng những ký ức về tình yêu, về hương sen trong tóc và nụ hôn thuở nào vẫn còn vẹn nguyên, ám ảnh và dẫn lối về \'lối cũ\'. Ký ức không chỉ là hoài niệm mà còn là một phần sống động, không thể tách rời của hiện tại.\", \"heading\": \"Dấu Ấn Thời Gian Và Ký Ức\"}, {\"content\": \"Bài thơ gợi mở một câu chuyện tình yêu lãng mạn nhưng dang dở, với những khoảnh khắc ngọt ngào (hương sen trong tóc ướp say tình người, nụ hôn trao giữa mây trời) rồi lại chia xa (để rồi xa vắng, để rồi…trầm ngâm). Sự chia xa không được diễn đạt trực tiếp mà thấm đẫm trong nỗi \'trầm ngâm\', \'thương\', \'vương chút hương thầm\', cho thấy tình yêu ấy vẫn còn đó, là một vết hằn sâu trong tâm hồn nhân vật trữ tình, bất chấp dòng chảy của thời gian và số phận.\", \"heading\": \"Tình Yêu Và Sự Chia Xa\"}], \"linguisticInsights\": {\"motifs\": [\"Sóng miên man\", \"Chuông chùa\", \"Gió lay cành liễu\", \"Lá bay\", \"Hương sen\", \"Áo ướt mưa\", \"Hồ thu\", \"Lối cũ\"], \"natureAndScenery\": \"Không gian Hồ Tây hiện lên với những hình ảnh quen thuộc và giàu sức gợi: sóng, chuông chùa, gió, cành liễu, lá bay, mây trời, hồ thu, lối cũ. Các yếu tố thiên nhiên không chỉ là phông nền mà còn tham gia vào việc biểu đạt cảm xúc: sóng \'miên man khúc nhạc sầu\', gió \'lay cành liễu\' như một câu hỏi bỏ lửng, \'lá bay\' gợi sự tàn phai, \'hồ thu\' và \'lối cũ\' gợi vẻ đẹp u hoài, nơi chốn của những kỷ niệm. Tất cả tạo nên một không gian trữ tình mang vẻ đẹp cô liêu, trầm mặc, thấm đượm nỗi buồn và sự hoài niệm.\", \"temporalElements\": \"Thời gian trong bài thơ vận động theo dòng hồi ức, từ \'thuở xưa\' không xác định đến \'một chiều thu ấy\' đầy kỷ niệm và hiện tại \'bây giờ\' với \'tóc đã hoa râm\'. Sự đan xen giữa quá khứ và hiện tại tạo nên một dòng chảy cảm xúc liên tục, nơi ký ức không ngừng vọng về, chi phối tâm trạng nhân vật trữ tình. Cụm \'để rồi xa vắng, để rồi…trầm ngâm\' gợi một khoảng thời gian dài của nỗi buồn và sự chiêm nghiệm.\", \"symbolsAndMetaphors\": [\"Sâm Cầm vắng bóng: Biểu tượng cho sự mất mát, đổi thay, những điều đẹp đẽ đã qua đi, hoặc sự cô đơn, trống vắng.\", \"Khúc nhạc sầu: Ẩn dụ cho nỗi buồn nội tại, được ngoại hóa qua âm thanh của sóng.\", \"Hương sen trong tóc ướp say tình người: Biểu tượng cho ký ức tình yêu tinh khiết, say đắm, và sự lưu giữ kỷ niệm.\", \"Nụ hôn trao giữa mây trời: Biểu tượng cho khoảnh khắc thăng hoa, lãng mạn, sự hòa quyện tuyệt đối của tình yêu.\", \"Tóc đã hoa râm: Biểu tượng cho dấu ấn của thời gian, tuổi già, sự trải nghiệm và chiêm nghiệm cuộc đời.\"]}}, \"intertextuality\": {\"comparison\": {\"effects\": \"Việc so sánh giúp làm nổi bật chiều sâu và đa dạng của cảm xúc trữ tình trong văn học Việt Nam. \'Tản mạn Tây Hồ\' khẳng định giá trị của ký ức và sự hoài niệm như một cách để con người tìm thấy ý nghĩa trong cuộc sống, một vẻ đẹp trầm lắng, chấp nhận sự đổi thay. Ngược lại, \'Vội Vàng\' lại thúc giục người đọc sống trọn vẹn, khai thác tận cùng vẻ đẹp của cuộc sống trước khi nó vụt mất, thể hiện một vẻ đẹp rực rỡ, bùng cháy và thái độ chủ động trước sinh mệnh. Hai tác phẩm, tuy khác biệt về thái độ trước thời gian, nhưng đều chạm đến những khía cạnh sâu sắc của tâm hồn con người và tình yêu cuộc sống.\", \"differences\": \"Trong khi \'Tản mạn Tây Hồ\' mang giọng điệu trầm buồn, hoài niệm, tập trung vào sự tiếc nuối những gì đã mất và sự kiên trì của ký ức, với thời gian quá khứ ám ảnh hiện tại. \'Vội Vàng\' của Xuân Diệu lại thể hiện một cảm xúc gấp gáp, khao khát sống mãnh liệt, sợ hãi trước sự trôi chảy của thời gian và sự mất mát của tuổi trẻ, muốn níu giữ từng khoảnh khắc hiện tại. Thời gian trong \'Vội Vàng\' là thời gian hiện tại đầy rẫy sự vô thường. \'Tản mạn Tây Hồ\' chấp nhận nỗi buồn để sống cùng ký ức, trong khi \'Vội Vàng\' lại muốn chống lại thời gian để tận hưởng hết mình, đẩy lùi sự già nua và cái chết.\", \"similarities\": \"Cả hai bài thơ đều thể hiện một cảm thức sâu sắc về thời gian và sự trôi chảy của nó, đồng thời gắn kết chặt chẽ cảm xúc con người với thiên nhiên. Nỗi buồn, sự tiếc nuối trước sự vô thường là điểm chung trong tâm trạng chủ đạo của hai tác phẩm, mặc dù cách biểu hiện và thái độ có sự khác biệt rõ rệt.\"}, \"comparativeText\": {\"title\": \"Vội Vàng\", \"author\": \"Xuân Diệu\"}, \"initialAnalysis\": \"Tản mạn Tây Hồ là bài thơ đậm chất trữ tình, khai thác chủ đề tình yêu, ký ức và sự hoài niệm trước những đổi thay của thời gian và cảnh vật. Cảm xúc chủ đạo là nỗi buồn man mác, tiếc nuối một mối tình đã qua, được thể hiện qua các hình ảnh thiên nhiên và không gian quen thuộc của Hồ Tây. Phong cách thơ giản dị nhưng sâu lắng, giàu tính biểu cảm, thấm đẫm chất tự sự trữ tình.\"}, \"concludingThoughts\": {\"lesson\": \"Cuộc đời là một dòng chảy không ngừng, nơi thời gian sẽ làm phai nhạt nhiều thứ nhưng không thể xóa nhòa những ký ức sâu sắc về tình yêu và tuổi trẻ. Nỗi hoài niệm không chỉ là sự tiếc nuối mà còn là cách con người trân trọng quá khứ, tìm thấy sự an ủi và ý nghĩa trong những gì đã qua, để rồi từ đó thấu hiểu hơn về bản thân và hành trình sống của mình. Ký ức, dù buồn, vẫn là một kho báu tinh thần quý giá.\", \"message\": \"Bài thơ truyền tải thông điệp về giá trị vĩnh cửu của ký ức và tình yêu trong dòng chảy của thời gian. Nỗi buồn hoài niệm không phải là sự yếu đuối mà là một trạng thái cảm xúc nhân văn, giúp con người kết nối với quá khứ, sống chậm lại để chiêm nghiệm những giá trị tinh thần. Dù cảnh vật đổi thay, con người già đi, nhưng những cảm xúc chân thành, những kỷ niệm đẹp đẽ vẫn là điểm tựa tinh thần, là con đường để tâm hồn tìm về chốn bình yên, dù chỉ trong hồi ức.\"}}', '2025-10-26 13:45:53', '2025-10-26 13:45:53'),
(12, 388, NULL, 4, 5, NULL, '2025-10-27 04:42:31', '2025-10-27 04:42:31');

-- --------------------------------------------------------

--
-- Table structure for table `result_evaluate`
--

CREATE TABLE `result_evaluate` (
  `id_result_eval` int NOT NULL,
  `id_evaluate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `score`
--

CREATE TABLE `score` (
  `score_id` int NOT NULL,
  `id_evaluate` int NOT NULL,
  `grade` decimal(4,2) NOT NULL,
  `id_assignment` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `score_detail`
--

CREATE TABLE `score_detail` (
  `id_score_detail` int NOT NULL,
  `score_id` int NOT NULL,
  `tc_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id_setting` int NOT NULL,
  `task_id` int NOT NULL,
  `number` int DEFAULT NULL,
  `evaluate_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `open_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting_detail`
--

CREATE TABLE `setting_detail` (
  `id_setting_detail` int NOT NULL,
  `id_setting` int NOT NULL,
  `id_obj_review` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE `task` (
  `task_id` int NOT NULL,
  `phase_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` text,
  `file_content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `content_creator` int DEFAULT NULL,
  `review_task` int DEFAULT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`task_id`, `phase_id`, `title`, `description`, `file_content`, `content_creator`, `review_task`, `added_by`, `created_at`, `updated_at`) VALUES
(3, 1, 'Phân tích bài thơ Sắc thu', 'Em hãy phân tích bài thơ Sắc thu', 'tasks/task_68fc833d1f7b5.txt', 2, NULL, 2, '2025-10-25 14:51:03', '2025-10-25 14:58:53'),
(5, 1, 'Phân tích bài thơ Tản mạn Tây Hồ', 'Em hãy sử dụng các công cụ để phân tích bài thơ', 'tasks/task_68fc826a4e99a.txt', 2, NULL, 2, '2025-10-25 14:55:22', '2025-10-25 14:55:22'),
(6, 2, 'Chữa bài Sắc thu', '', NULL, NULL, 3, 2, '2025-10-26 15:57:29', '2025-10-26 16:02:01'),
(7, 2, 'Chữa bài Tản mạn Tây Hồ', '', NULL, NULL, 5, 2, '2025-10-27 04:38:31', '2025-10-27 04:38:31');

-- --------------------------------------------------------

--
-- Table structure for table `tieu_chi`
--

CREATE TABLE `tieu_chi` (
  `tc_id` int NOT NULL,
  `name_tc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nhom_tc_id` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `min_score` int DEFAULT NULL,
  `max_score` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic`
--

CREATE TABLE `topic` (
  `topic_id` int NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `class_id` int NOT NULL,
  `added_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `topic`
--

INSERT INTO `topic` (`topic_id`, `topic_name`, `class_id`, `added_by`, `created_at`, `updated_at`) VALUES
(8, 'Tuần 1: Thơ lục bát', 1, 2, '2025-10-25 10:34:01', '2025-10-25 10:34:01');

-- --------------------------------------------------------

--
-- Table structure for table `total_score`
--

CREATE TABLE `total_score` (
  `id_total` int NOT NULL,
  `response_id` int NOT NULL,
  `teacher_grade` int DEFAULT NULL,
  `penalty` int DEFAULT NULL,
  `final_score` int DEFAULT NULL,
  `id_evaluate` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher','admin') DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `username`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Quản trị viên', 'admin', 'admin@gmail.com', '1', 'admin', '2025-10-22 13:35:57'),
(2, 'Nguyễn Minh Thương', 'gv1', 'nguyenminhthuong@gmail.com', '1', 'teacher', '2025-10-22 13:36:41'),
(357, 'Lương Bảo An', '0133378801', 'luongbaoan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(358, 'Bùi Tâm Anh', '0131638629', 'buitamanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(359, 'Đoàn Châu Anh', '0149599874', 'doanchauanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(360, 'Hán Đỗ Kỳ Anh', '0145046988', 'handokyanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(361, 'Hoàng Anh', '0130487794', 'hoanganh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(362, 'Lê Phan Quỳnh Anh', '0132665589', 'lephanquynhanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(363, 'Ngô Mai Anh', '0131044918', 'ngomaianh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(364, 'Nguyễn Tú Anh', '0131749183', 'nguyentuanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(365, 'Trần Lê Tú Anh', '0148772396', 'tranletuanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(366, 'Lê Gia Hân', '0150352610', 'legiahan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(367, 'Phạm Thị Diệu Hoa', '0145692640', 'phamthidieuhoa@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(368, 'Lê Đức Khiêm', '0150433750', 'leduckhiem@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(369, 'Đinh Hoàng Yến Linh', '0118430801', 'dinhhoangyenlinh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(370, 'Hoàng Ngọc Linh', '0142720218', 'hoangngoclinh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(371, 'Chu Tuệ Mẫn', '0149106246', 'chutueman@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(372, 'Trần Ngọc Nguyễn Minh', '0131730433', 'tranngocnguyenminh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(373, 'Nguyễn Bảo Ngân', '0117814287', 'nguyenbaongan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(374, 'Nguyễn Thị Minh Ngọc', '0131730336', 'nguyenthiminhngoc@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(375, 'Vũ Công Nhã', '0133129428', 'vucongnha@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(376, 'Nguyễn Hồng Nhung', '0142668466', 'nguyenhongnhung@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(377, 'Tạ Duy Quân', '0150349882', 'taduyquan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(378, 'Đỗ Quốc Thành', '0140783057', 'doquocthanh@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(379, 'Nguyễn Minh Thư', '0118104560', 'nguyenminhthu02@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(380, 'Nguyễn Minh Thư', '0118017017', 'nguyenminhthu10@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(381, 'Nguyễn Ngọc Huyền Thư', '0118180219', 'nguyenngochuyenthu@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(382, 'Nguyễn Hoàng Khánh Thy', '0117861700', 'nguyenhoangkhanhthy@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(383, 'Dương Minh Trang', '0139294683', 'duongminhtrang@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(384, 'Đoàn Minh Trang', '0118422859', 'doanminhtrang@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(385, 'Nguyễn Phương Uyên', '0118133173', 'nguyenphuonguyen@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(386, 'Phạm Thanh Vân', '0117816346', 'phamthanhvan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(387, 'Trương Anh Tuấn', '0155092125', 'truonganhtuan@gmail.com', '1', 'student', '2025-10-25 02:50:16'),
(388, 'Học sinh 1', 'hs1', '1@gmail.com', '1', 'student', '2025-10-25 04:02:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`id_assignment`),
  ADD KEY `idx_asg_sd` (`id_setting_detail`),
  ADD KEY `idx_asg_user` (`user_id`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `uq_class_name` (`class_name`);

--
-- Indexes for table `class_member`
--
ALTER TABLE `class_member`
  ADD PRIMARY KEY (`class_id`,`user_id`),
  ADD KEY `idx_cm_user` (`user_id`),
  ADD KEY `fk_add_by` (`added_by`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_comment_content` (`content_id`),
  ADD KEY `fk_comment_user` (`user_id`),
  ADD KEY `fk_comment_task` (`task_id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `fk_rp_response` (`response_id`);

--
-- Indexes for table `deliver`
--
ALTER TABLE `deliver`
  ADD PRIMARY KEY (`id_deliver`),
  ADD KEY `idx_deliver_task` (`task_id`),
  ADD KEY `idx_deliver_type_value` (`type`,`value`);

--
-- Indexes for table `detail_evaluate`
--
ALTER TABLE `detail_evaluate`
  ADD PRIMARY KEY (`id_detail_eval`),
  ADD KEY `idx_de_tc` (`tc_id`),
  ADD KEY `idx_de_eval` (`id_evaluate`);

--
-- Indexes for table `evaluate`
--
ALTER TABLE `evaluate`
  ADD PRIMARY KEY (`id_evaluate`),
  ADD KEY `idx_eval_task` (`task_id`),
  ADD KEY `idx_eval_asg` (`id_assignment`),
  ADD KEY `idx_eval_resp` (`response_id`),
  ADD KEY `idx_eval_user` (`user_id`);

--
-- Indexes for table `evaluate_type`
--
ALTER TABLE `evaluate_type`
  ADD PRIMARY KEY (`id_eval_type`),
  ADD UNIQUE KEY `uq_eval_type_name` (`name_eval_type`);

--
-- Indexes for table `generate`
--
ALTER TABLE `generate`
  ADD PRIMARY KEY (`id_gen`);

--
-- Indexes for table `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `uq_group_class_name` (`class_id`,`group_name`),
  ADD KEY `idx_group_class` (`class_id`);

--
-- Indexes for table `group_member`
--
ALTER TABLE `group_member`
  ADD PRIMARY KEY (`group_id`,`user_id`),
  ADD KEY `fk_ui_user` (`user_id`);

--
-- Indexes for table `main_phase`
--
ALTER TABLE `main_phase`
  ADD PRIMARY KEY (`m_phase_id`);

--
-- Indexes for table `nhom_tc`
--
ALTER TABLE `nhom_tc`
  ADD PRIMARY KEY (`nhom_tc_id`);

--
-- Indexes for table `object_review`
--
ALTER TABLE `object_review`
  ADD PRIMARY KEY (`id_obj_review`),
  ADD KEY `idx_or_eval` (`id_evaluate`),
  ADD KEY `idx_or_target` (`target_type`,`target_id`);

--
-- Indexes for table `phase`
--
ALTER TABLE `phase`
  ADD PRIMARY KEY (`phase_id`),
  ADD KEY `fk_phase_mainphase` (`m_phase_id`),
  ADD KEY `fk_phase_topic` (`topic_id`);

--
-- Indexes for table `response`
--
ALTER TABLE `response`
  ADD PRIMARY KEY (`id_response`),
  ADD KEY `idx_response_user` (`user_id`),
  ADD KEY `idx_response_parent` (`parent_response`),
  ADD KEY `idx_response_task` (`task_id`),
  ADD KEY `fk_response_owner` (`owner_user_id`);

--
-- Indexes for table `result_evaluate`
--
ALTER TABLE `result_evaluate`
  ADD PRIMARY KEY (`id_result_eval`),
  ADD KEY `idx_re_eval` (`id_evaluate`);

--
-- Indexes for table `score`
--
ALTER TABLE `score`
  ADD PRIMARY KEY (`score_id`),
  ADD KEY `idx_score_eval` (`id_evaluate`),
  ADD KEY `idx_score_asg` (`id_assignment`);

--
-- Indexes for table `score_detail`
--
ALTER TABLE `score_detail`
  ADD PRIMARY KEY (`id_score_detail`),
  ADD KEY `idx_sd_score` (`score_id`),
  ADD KEY `idx_sd_tc` (`tc_id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id_setting`),
  ADD KEY `idx_setting_task` (`task_id`);

--
-- Indexes for table `setting_detail`
--
ALTER TABLE `setting_detail`
  ADD PRIMARY KEY (`id_setting_detail`),
  ADD KEY `idx_sd_setting` (`id_setting`);

--
-- Indexes for table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`task_id`);

--
-- Indexes for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  ADD PRIMARY KEY (`tc_id`),
  ADD KEY `idx_tc_nhom` (`nhom_tc_id`);

--
-- Indexes for table `topic`
--
ALTER TABLE `topic`
  ADD PRIMARY KEY (`topic_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `total_score`
--
ALTER TABLE `total_score`
  ADD PRIMARY KEY (`id_total`),
  ADD KEY `idx_ts_resp` (`response_id`),
  ADD KEY `idx_ts_eval` (`id_evaluate`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `id_assignment` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class`
--
ALTER TABLE `class`
  MODIFY `class_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `deliver`
--
ALTER TABLE `deliver`
  MODIFY `id_deliver` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `detail_evaluate`
--
ALTER TABLE `detail_evaluate`
  MODIFY `id_detail_eval` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluate`
--
ALTER TABLE `evaluate`
  MODIFY `id_evaluate` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluate_type`
--
ALTER TABLE `evaluate_type`
  MODIFY `id_eval_type` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generate`
--
ALTER TABLE `generate`
  MODIFY `id_gen` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group`
--
ALTER TABLE `group`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `main_phase`
--
ALTER TABLE `main_phase`
  MODIFY `m_phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `nhom_tc`
--
ALTER TABLE `nhom_tc`
  MODIFY `nhom_tc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `object_review`
--
ALTER TABLE `object_review`
  MODIFY `id_obj_review` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phase`
--
ALTER TABLE `phase`
  MODIFY `phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `response`
--
ALTER TABLE `response`
  MODIFY `id_response` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `result_evaluate`
--
ALTER TABLE `result_evaluate`
  MODIFY `id_result_eval` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `score`
--
ALTER TABLE `score`
  MODIFY `score_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `score_detail`
--
ALTER TABLE `score_detail`
  MODIFY `id_score_detail` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `id_setting` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting_detail`
--
ALTER TABLE `setting_detail`
  MODIFY `id_setting_detail` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task`
--
ALTER TABLE `task`
  MODIFY `task_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  MODIFY `tc_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic`
--
ALTER TABLE `topic`
  MODIFY `topic_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `total_score`
--
ALTER TABLE `total_score`
  MODIFY `id_total` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=389;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `fk_asg_sd` FOREIGN KEY (`id_setting_detail`) REFERENCES `setting_detail` (`id_setting_detail`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `class_member`
--
ALTER TABLE `class_member`
  ADD CONSTRAINT `fk_add_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_cm_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_content` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comment_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`),
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `fk_rp_response` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `deliver`
--
ALTER TABLE `deliver`
  ADD CONSTRAINT `fk_deliver_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detail_evaluate`
--
ALTER TABLE `detail_evaluate`
  ADD CONSTRAINT `fk_de_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_de_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `evaluate`
--
ALTER TABLE `evaluate`
  ADD CONSTRAINT `fk_eval_asg` FOREIGN KEY (`id_assignment`) REFERENCES `assignment` (`id_assignment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_resp` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eval_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `group`
--
ALTER TABLE `group`
  ADD CONSTRAINT `fk_group_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `group_member`
--
ALTER TABLE `group_member`
  ADD CONSTRAINT `fk_gm_group` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`),
  ADD CONSTRAINT `fk_ui_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `object_review`
--
ALTER TABLE `object_review`
  ADD CONSTRAINT `fk_or_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `phase`
--
ALTER TABLE `phase`
  ADD CONSTRAINT `fk_phase_mainphase` FOREIGN KEY (`m_phase_id`) REFERENCES `main_phase` (`m_phase_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_phase_topic` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`topic_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `response`
--
ALTER TABLE `response`
  ADD CONSTRAINT `fk_response_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_response_parent` FOREIGN KEY (`parent_response`) REFERENCES `response` (`id_response`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_response_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `result_evaluate`
--
ALTER TABLE `result_evaluate`
  ADD CONSTRAINT `fk_re_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `score`
--
ALTER TABLE `score`
  ADD CONSTRAINT `fk_score_assignment` FOREIGN KEY (`id_assignment`) REFERENCES `assignment` (`id_assignment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_score_evaluate` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `score_detail`
--
ALTER TABLE `score_detail`
  ADD CONSTRAINT `fk_sdetail_score` FOREIGN KEY (`score_id`) REFERENCES `score` (`score_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sdetail_tc` FOREIGN KEY (`tc_id`) REFERENCES `tieu_chi` (`tc_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `setting`
--
ALTER TABLE `setting`
  ADD CONSTRAINT `fk_setting_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `setting_detail`
--
ALTER TABLE `setting_detail`
  ADD CONSTRAINT `fk_sd_setting` FOREIGN KEY (`id_setting`) REFERENCES `setting` (`id_setting`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tieu_chi`
--
ALTER TABLE `tieu_chi`
  ADD CONSTRAINT `fk_tc_nhom` FOREIGN KEY (`nhom_tc_id`) REFERENCES `nhom_tc` (`nhom_tc_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `topic`
--
ALTER TABLE `topic`
  ADD CONSTRAINT `topic_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `total_score`
--
ALTER TABLE `total_score`
  ADD CONSTRAINT `fk_ts_eval` FOREIGN KEY (`id_evaluate`) REFERENCES `evaluate` (`id_evaluate`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ts_resp` FOREIGN KEY (`response_id`) REFERENCES `response` (`id_response`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
