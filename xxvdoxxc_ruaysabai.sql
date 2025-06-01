-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 01, 2025 at 04:36 AM
-- Server version: 10.6.20-MariaDB-cll-lve-log
-- PHP Version: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xxvdoxxc_ruaysabai`
--

-- --------------------------------------------------------

--
-- Table structure for table `analytics`
--

CREATE TABLE `analytics` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_users` int(11) DEFAULT 0,
  `new_users` int(11) DEFAULT 0,
  `active_users` int(11) DEFAULT 0,
  `prompts_generated` int(11) DEFAULT 0,
  `total_pageviews` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `avg_session_duration` int(11) DEFAULT 0,
  `bounce_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `analytics`
--

INSERT INTO `analytics` (`id`, `date`, `total_users`, `new_users`, `active_users`, `prompts_generated`, `total_pageviews`, `unique_visitors`, `avg_session_duration`, `bounce_rate`, `created_at`) VALUES
(1, '2025-05-31', 1, 1, 1, 0, 0, 0, 0, 0.00, '2025-05-31 08:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-image',
  `color` varchar(20) DEFAULT '#1E90FF',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `color`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '?????/???????', '???????????? ??????? ??? Portrait', 'fas fa-user', '#1E90FF', 1, 1, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(2, '??????/?????????', '????????????????????????????', 'fas fa-box', '#4169E1', 2, 1, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(3, '????????/????????', '??????????? ???????? ??????????', 'fas fa-mountain', '#6A5ACD', 3, 1, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(4, '????/???????????', '???????????????????????????', 'fas fa-home', '#8A2BE2', 4, 1, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(5, '?????/???????????', '?????????????????????????????', 'fas fa-utensils', '#FF6B6B', 5, 1, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(6, '?????/???????', '?????????????????????', 'fas fa-palette', '#20BF6B', 6, 1, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(7, '????????', '?????? ????????????? ???????????', 'fas fa-car', '#FFA500', 7, 1, '2025-05-31 08:34:30', '2025-05-31 08:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `credit_usage`
--

CREATE TABLE `credit_usage` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('generate_prompt','download_image','api_call') NOT NULL,
  `credits_used` int(11) DEFAULT 1,
  `prompt_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examples`
--

CREATE TABLE `examples` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `prompt` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-image',
  `difficulty` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `examples`
--

INSERT INTO `examples` (`id`, `title`, `prompt`, `category_id`, `icon`, `difficulty`, `tags`, `views`, `likes`, `is_featured`, `is_active`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '?????????????????', 'beautiful young woman, elegant portrait style, in blooming flower garden, gentle smile, wearing flowing white dress, soft pastel colors, golden hour lighting, dreamy atmosphere, medium shot angle, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 1, 'fas fa-image', 'beginner', '[\"portrait\", \"woman\", \"garden\", \"flowers\"]', 0, 0, 1, 1, 1, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(2, '????????????????????', 'modern luxury living room, contemporary interior design style, spacious open-plan layout, minimalist furniture, neutral beige and white colors, natural afternoon sunlight, sophisticated atmosphere, wide angle shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 4, 'fas fa-image', 'intermediate', '[\"interior\", \"modern\", \"living room\"]', 0, 0, 1, 1, 2, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(3, '?????????????????', 'futuristic sports car, sleek automotive design style, on neon-lit city street, aerodynamic curves, metallic silver and electric blue accents, night with dramatic city lighting, high-tech atmosphere, low angle dynamic shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 7, 'fas fa-image', 'advanced', '[\"car\", \"futuristic\", \"sports\", \"neon\"]', 0, 0, 1, 1, 3, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `prompt` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-image',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `description`, `image_url`, `prompt`, `category_id`, `icon`, `tags`, `views`, `likes`, `downloads`, `is_featured`, `is_active`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'AI Robot Portrait', '???????? AI ???????????????', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=400&h=300&fit=crop&crop=center', 'futuristic AI robot, cyberpunk portrait style, glowing blue eyes, metallic chrome finish, in dark tech laboratory, advanced circuitry details, neon blue and purple lighting, mysterious atmosphere, close-up shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 6, 'fas fa-robot', '[\"AI\", \"robot\", \"cyberpunk\", \"futuristic\"]', 0, 0, 0, 1, 1, 1, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(2, 'Sunset Mountain', '??????????????????????', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=300&fit=crop&crop=center', 'majestic mountain landscape, dramatic sunset photography style, golden hour lighting, layered mountain silhouettes, vibrant orange and purple sky, misty valleys, serene peaceful atmosphere, wide panoramic shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 3, 'fas fa-mountain', '[\"mountain\", \"sunset\", \"landscape\", \"nature\"]', 0, 0, 0, 1, 1, 2, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(3, 'Luxury Sports Car', '??????????? ?????????', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=400&h=300&fit=crop&crop=center', 'luxury sports car, automotive photography style, sleek metallic paint finish, dramatic studio lighting, reflective black floor, modern showroom background, silver and chrome accents, sophisticated atmosphere, low angle shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 7, 'fas fa-car', '[\"car\", \"luxury\", \"sports\", \"studio\"]', 0, 0, 0, 1, 1, 3, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30'),
(4, 'Fashion Portrait', '??????????? ??????????????', 'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?w=400&h=300&fit=crop&crop=center', 'elegant fashion model, high fashion portrait photography style, dramatic lighting, designer clothing, confident pose, urban modern background, black and white with color accents, sophisticated glamorous atmosphere, medium shot fashion photography, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 1, 'fas fa-user-tie', '[\"fashion\", \"model\", \"portrait\", \"elegant\"]', 0, 0, 0, 1, 1, 4, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_items`
--

CREATE TABLE `gallery_items` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` text NOT NULL,
  `prompt` text NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-image',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_items`
--

INSERT INTO `gallery_items` (`id`, `title`, `description`, `image_url`, `prompt`, `icon`, `created_at`, `updated_at`) VALUES
(4, 'AI Robot Portrait', 'หุ่นยนต์ AI แบบไซเบอร์พังค์', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=400&h=300&fit=crop&crop=center', 'futuristic AI robot, cyberpunk portrait style, glowing blue eyes, metallic chrome finish, in dark tech laboratory, advanced circuitry details, neon blue and purple lighting, mysterious atmosphere, close-up shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 'fas fa-robot', '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(5, 'Sunset Mountain', 'ภูเขาในแสงพระอาทิตย์ตก', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=300&fit=crop&crop=center', 'majestic mountain landscape, dramatic sunset photography style, golden hour lighting, layered mountain silhouettes, vibrant orange and purple sky, misty valleys, serene peaceful atmosphere, wide panoramic shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 'fas fa-mountain', '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(6, 'Luxury Sports Car', 'รถสปอร์ตหรู ในสตูดิโอ', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=400&h=300&fit=crop&crop=center', 'luxury sports car, automotive photography style, sleek metallic paint finish, dramatic studio lighting, reflective black floor, modern showroom background, silver and chrome accents, sophisticated atmosphere, low angle shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 'fas fa-car', '2025-05-31 11:23:17', '2025-05-31 11:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `action_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `action_url`, `is_read`, `expires_at`, `created_at`) VALUES
(1, NULL, '??????????????? AI Prompt Generator Pro!', '??????????????????? ?????????? Prompt ???????????????!', 'success', NULL, 0, NULL, '2025-05-31 08:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `prompt_examples`
--

CREATE TABLE `prompt_examples` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `prompt` text NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-image',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prompt_examples`
--

INSERT INTO `prompt_examples` (`id`, `title`, `prompt`, `icon`, `created_at`, `updated_at`) VALUES
(4, 'สาวสวยในสวนดอกไม้', 'beautiful young woman, elegant portrait style, in blooming flower garden, gentle smile, wearing flowing white dress, soft pastel colors, golden hour lighting, dreamy atmosphere, medium shot angle, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 'fas fa-image', '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(5, 'ห้องนั่งเล่นโมเดิร์น', 'modern luxury living room, contemporary interior design style, spacious open-plan layout, minimalist furniture, neutral beige and white colors, natural afternoon sunlight, sophisticated atmosphere, wide angle shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 'fas fa-home', '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(6, 'รถสปอร์ตแห่งอนาคต', 'futuristic sports car, sleek automotive design style, on neon-lit city street, aerodynamic curves, metallic silver and electric blue accents, night with dramatic city lighting, high-tech atmosphere, low angle dynamic shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting', 'fas fa-car', '2025-05-31 11:23:17', '2025-05-31 11:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `prompt_logs`
--

CREATE TABLE `prompt_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prompt_logs`
--

INSERT INTO `prompt_logs` (`id`, `user_id`, `created_at`) VALUES
(1, 0, '2025-06-01 00:07:22'),
(2, 0, '2025-06-01 00:07:25'),
(3, 0, '2025-06-01 00:12:11'),
(4, 0, '2025-06-01 00:12:14'),
(5, 0, '2025-06-01 00:25:38'),
(6, 0, '2025-06-01 00:25:42'),
(7, 0, '2025-06-01 00:26:05'),
(8, 0, '2025-06-01 00:36:09'),
(9, 0, '2025-06-01 00:36:13'),
(10, 0, '2025-06-01 00:37:04'),
(11, 0, '2025-06-01 00:41:41'),
(12, 0, '2025-06-01 00:41:42'),
(13, 0, '2025-06-01 00:41:42'),
(14, 0, '2025-06-01 00:41:43'),
(15, 0, '2025-06-01 00:46:50'),
(16, 0, '2025-06-01 00:46:51'),
(17, 0, '2025-06-01 00:46:51'),
(18, 0, '2025-06-01 00:46:51'),
(19, 0, '2025-06-01 00:46:52'),
(20, 0, '2025-06-01 00:47:55'),
(21, 0, '2025-06-01 00:48:04'),
(22, 0, '2025-06-01 00:48:12'),
(23, 0, '2025-06-01 00:50:23'),
(24, 0, '2025-06-01 00:50:25'),
(25, 0, '2025-06-01 00:50:25'),
(26, 0, '2025-06-01 00:50:25'),
(27, 0, '2025-06-01 00:50:25'),
(28, 0, '2025-06-01 00:50:53');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` mediumtext NOT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` mediumtext DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `created_at`, `updated_at`) VALUES
(12, 'site_title', 'PromptEase AI Prompt 11', 'text', NULL, NULL, '2025-05-31 11:23:17', '2025-06-01 03:28:00'),
(13, 'site_description', 'สร้าง Prompt สำหรับภาพคมชัด สมจริง ด้วยปัญญาประดิษฐ์ขั้นสูง', 'text', NULL, NULL, '2025-05-31 11:23:17', '2025-05-31 11:29:26'),
(14, 'online_count', '182', 'text', NULL, NULL, '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(15, 'placeholder_title', 'เริ่มสร้าง Prompt ของคุณ', 'text', NULL, NULL, '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(16, 'placeholder_description', 'กรอกข้อมูลในฟอร์มและกดปุ่ม \"สร้าง Prompt\" เพื่อเริ่มต้น', 'text', NULL, NULL, '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(17, 'gallery_title', 'แกลเลอรี่ Prompt พร้อมตัวอย่างภาพ', 'text', NULL, NULL, '2025-05-31 11:23:17', '2025-05-31 11:23:17'),
(18, 'gallery_description', 'เลือกดูตัวอย่างภาพและคัดลอก Prompt ไปใช้งานได้ทันที', 'text', NULL, NULL, '2025-05-31 11:23:17', '2025-05-31 11:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_type` enum('monthly','yearly','lifetime') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'THB',
  `credits_included` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `status` enum('active','expired','cancelled','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `user_type` enum('admin','premium','free') DEFAULT 'free',
  `credits` int(11) DEFAULT 10,
  `daily_limit` int(11) DEFAULT 10,
  `last_reset_date` date DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','suspended','deleted') DEFAULT 'active',
  `member_type` varchar(16) DEFAULT 'free',
  `expire_date` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `avatar_url`, `user_type`, `credits`, `daily_limit`, `last_reset_date`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `last_login`, `created_at`, `updated_at`, `status`, `member_type`, `expire_date`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '???????????', NULL, 'admin', 999999, 999999, NULL, 0, NULL, NULL, NULL, NULL, '2025-05-31 08:34:30', '2025-05-31 08:34:30', 'active', 'free', NULL),
(2, 'nopaudio', 'nopaudio@gmail.com', '$2y$10$pnfavG3x.ag1LG8FfAXTKuGk0GgmShA/CHFMd4PgRyADlBoMa7fLC', 'nopaudio', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/xxvdoxxc/ruaysabai.com/profile.php</b> on line <b>100</b><br />', 'free', 10, 10, NULL, 0, NULL, NULL, NULL, NULL, '2025-05-31 23:13:33', '2025-05-31 23:20:59', 'active', 'free', NULL),
(3, 'nopaudio2', 'newnop9992@gmail.com', '$2y$10$Nd85uoO2V9q6MwgLv6gnD.8USNw/Pjb0X6Kk5GVgo3nttegfoHbyi', 'nopaudio', NULL, 'free', 10, 10, NULL, 0, NULL, NULL, NULL, NULL, '2025-05-31 23:14:28', '2025-05-31 23:14:28', 'active', 'free', NULL),
(4, 'nopaudio3', 'newnop9993@gmail.com', '$2y$10$qJHmLM..vETBphV4bsu5DuweIaatiHEr2UFwkZ5g432GiMs.pvM/C', 'nopaudio3', NULL, 'free', 10, 10, NULL, 0, NULL, NULL, NULL, '2025-06-01 11:27:17', '2025-06-01 03:26:35', '2025-06-01 04:27:17', 'active', 'free', NULL),
(5, 'testfree', 'testfree@example.com', '$2y$10$Dcm2ZZFrohiMG0h6qLo5tu/AUUN3rR41Dnpz5DgXnxKfA7T0/UZfa', '????? ?????????', NULL, 'free', 10, 10, NULL, 0, NULL, NULL, NULL, '2025-06-01 11:11:48', '2025-06-01 03:55:24', '2025-06-01 04:11:48', 'active', 'free', NULL),
(6, 'testmonthly', 'testmonthly@example.com', '$2y$10$sqL4zkdkV8FNrNR7HH/FO.cc2zqTE76ss74IXif887nnjyPpueL.6', '????? ??????????????', NULL, 'premium', 60, 60, NULL, 0, NULL, NULL, NULL, NULL, '2025-06-01 03:55:24', '2025-06-01 03:55:24', 'active', 'monthly', '2025-07-01'),
(7, 'testyearly', 'testyearly@example.com', '$2y$10$G9zor1FDmgMlW4qzH6itxOtdJCaEUrZEIJesljtO0Fi/4KBLeTx/q', '????? ???????????', NULL, 'premium', 999999, 999999, NULL, 0, NULL, NULL, NULL, NULL, '2025-06-01 03:55:24', '2025-06-01 03:55:24', 'active', 'yearly', '2026-06-01'),
(8, 'nopaudio4', 'newnop9994@gmail.com', '$2y$10$HiUD2GQDf4QEmL3NfCnQ8uEazhYrLdHJnUAb84G50e78osKKDQ49O', 'nopaudio4', NULL, 'free', 10, 10, NULL, 0, NULL, NULL, NULL, '2025-06-01 11:34:10', '2025-06-01 04:33:53', '2025-06-01 04:34:10', 'active', 'free', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_prompts`
--

CREATE TABLE `user_prompts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `content_type` varchar(100) DEFAULT NULL,
  `style` varchar(100) DEFAULT NULL,
  `scene` varchar(500) DEFAULT NULL,
  `details` mediumtext DEFAULT NULL,
  `generated_prompt` mediumtext NOT NULL,
  `is_favorite` tinyint(1) DEFAULT 0,
  `is_shared` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`),
  ADD KEY `idx_analytics_date` (`date`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `credit_usage`
--
ALTER TABLE `credit_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prompt_id` (`prompt_id`),
  ADD KEY `idx_credit_usage_user` (`user_id`),
  ADD KEY `idx_credit_usage_date` (`created_at`);

--
-- Indexes for table `examples`
--
ALTER TABLE `examples`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_examples_category` (`category_id`),
  ADD KEY `idx_examples_featured` (`is_featured`),
  ADD KEY `idx_examples_active` (`is_active`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_gallery_category` (`category_id`),
  ADD KEY `idx_gallery_featured` (`is_featured`),
  ADD KEY `idx_gallery_active` (`is_active`);

--
-- Indexes for table `gallery_items`
--
ALTER TABLE `gallery_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`);

--
-- Indexes for table `prompt_examples`
--
ALTER TABLE `prompt_examples`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prompt_logs`
--
ALTER TABLE `prompt_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_settings_key` (`setting_key`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_type` (`user_type`),
  ADD KEY `idx_users_status` (`status`);

--
-- Indexes for table `user_prompts`
--
ALTER TABLE `user_prompts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_prompts_user` (`user_id`),
  ADD KEY `idx_user_prompts_date` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `credit_usage`
--
ALTER TABLE `credit_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examples`
--
ALTER TABLE `examples`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gallery_items`
--
ALTER TABLE `gallery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `prompt_examples`
--
ALTER TABLE `prompt_examples`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `prompt_logs`
--
ALTER TABLE `prompt_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_prompts`
--
ALTER TABLE `user_prompts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
