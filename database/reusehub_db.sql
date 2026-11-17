-- ReUseHub Database Schema & Initial Data
-- Platform: ReUseHub – Rent Instead of Buy
-- Version: 1.0.0
-- Target: Universal MySQL / MariaDB (Fully compatible with WAMP Server MySQL 5.5 - 9.1+)

CREATE DATABASE IF NOT EXISTS `reusehub_db` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `reusehub_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('renter', 'owner', 'admin') NOT NULL DEFAULT 'renter',
  `bio` TEXT NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT 'Kochi',
  `state` VARCHAR(100) NOT NULL DEFAULT 'Kerala',
  `pincode` VARCHAR(20) NOT NULL DEFAULT '682001',
  `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT 'fa-box',
  `image` VARCHAR(255) NULL DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: products
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(250) NOT NULL UNIQUE,
  `description` TEXT NOT NULL,
  `daily_price` DECIMAL(10,2) NOT NULL,
  `security_deposit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `condition_grade` ENUM('Brand New', 'Like New', 'Gently Used', 'Fair') NOT NULL DEFAULT 'Like New',
  `location` VARCHAR(150) NOT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT 'Kochi',
  `state` VARCHAR(100) NOT NULL DEFAULT 'Kerala',
  `pincode` VARCHAR(20) NOT NULL DEFAULT '682001',
  `rental_instructions` TEXT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'inactive') NOT NULL DEFAULT 'approved',
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `views_count` INT NOT NULL DEFAULT 0,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  INDEX `idx_prod_owner` (`owner_id`),
  INDEX `idx_prod_category` (`category_id`),
  INDEX `idx_prod_status` (`status`),
  INDEX `idx_prod_city` (`city`),
  INDEX `idx_prod_price` (`daily_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: product_images
-- --------------------------------------------------------
CREATE TABLE `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  INDEX `idx_img_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: bookings
-- --------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(30) NOT NULL UNIQUE,
  `product_id` INT NOT NULL,
  `renter_id` INT NOT NULL,
  `owner_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT NOT NULL,
  `daily_rate` DECIMAL(10,2) NOT NULL,
  `rental_amount` DECIMAL(10,2) NOT NULL,
  `security_deposit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `service_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `cancellation_reason` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`renter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_book_product` (`product_id`),
  INDEX `idx_book_renter` (`renter_id`),
  INDEX `idx_book_owner` (`owner_id`),
  INDEX `idx_book_dates` (`start_date`, `end_date`),
  INDEX `idx_book_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
  `payment_method` ENUM('UPI', 'Card', 'NetBanking', 'Cash on Pickup') NOT NULL DEFAULT 'UPI',
  `amount` DECIMAL(10,2) NOT NULL,
  `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('paid', 'refunded', 'pending', 'failed') NOT NULL DEFAULT 'paid',
  `payment_gateway_ref` VARCHAR(150) DEFAULT 'DEMO_GATEWAY_SUCCESS',
  `payment_date` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  INDEX `idx_pay_booking` (`booking_id`),
  INDEX `idx_pay_tx` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: reviews
-- --------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_rev_product` (`product_id`),
  INDEX `idx_rev_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: wishlist
-- --------------------------------------------------------
CREATE TABLE `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_product` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: reports
-- --------------------------------------------------------
CREATE TABLE `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `reason` VARCHAR(150) NOT NULL,
  `details` TEXT NOT NULL,
  `status` ENUM('pending', 'reviewed', 'dismissed', 'resolved') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_rep_product` (`product_id`),
  INDEX `idx_rep_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- SEED DATA
-- Password for all seed users is: Password123
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `bio`, `address`, `city`, `state`, `pincode`, `avatar`) VALUES
(1, 'ReUseHub Admin', 'admin@reusehub.com', '+91 98460 00001', '$2y$10$c0C7LYFMM.j/vhp6fftKruFskWMsQOht86D0nfiCslDkm7EpC5o2q', 'admin', 'Head Platform Administrator overseeing quality and trust at ReUseHub Kerala.', 'Panampilly Nagar, Main Avenue', 'Kochi', 'Kerala', '682036', 'assets/images/default-avatar.png'),
(2, 'Rahul Menon', 'rahul@gmail.com', '+91 98471 23456', '$2y$10$c0C7LYFMM.j/vhp6fftKruFskWMsQOht86D0nfiCslDkm7EpC5o2q', 'owner', 'Tech enthusiast, hobby photographer & outdoor explorer based in Kakkanad.', 'Flat 4B, Silver Heights, Kakkanad', 'Kochi', 'Kerala', '682030', 'assets/images/default-avatar.png'),
(3, 'Ananya Nair', 'ananya@gmail.com', '+91 97455 67890', '$2y$10$c0C7LYFMM.j/vhp6fftKruFskWMsQOht86D0nfiCslDkm7EpC5o2q', 'owner', 'Fashion stylist and creative designer. Believer in sustainable and circular fashion.', 'TC 15/220, Kowdiar', 'Trivandrum', 'Kerala', '695003', 'assets/images/default-avatar.png'),
(4, 'Favas Rahman', 'favas@gmail.com', '+91 94951 11223', '$2y$10$c0C7LYFMM.j/vhp6fftKruFskWMsQOht86D0nfiCslDkm7EpC5o2q', 'owner', 'Videographer, drone pilot, and DIY weekend hobbyist in Kozhikode.', 'Mavoor Road, Near City Mall', 'Kozhikode', 'Kerala', '673004', 'assets/images/default-avatar.png'),
(5, 'Sneha Varma', 'sneha@gmail.com', '+91 98462 99887', '$2y$10$c0C7LYFMM.j/vhp6fftKruFskWMsQOht86D0nfiCslDkm7EpC5o2q', 'renter', 'Frequent traveler, college graduate and weekend camper.', 'Round North, Thrissur', 'Thrissur', 'Kerala', '680001', 'assets/images/default-avatar.png');

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `image`, `is_featured`) VALUES
(1, 'Electronics and Gadgets', 'electronics-gadgets', 'High-end gaming consoles, laptops, tablets, VR headsets, and smart gear.', 'fa-laptop', 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?auto=format&fit=crop&w=800&q=80', 1),
(2, 'Fashion and Accessories', 'fashion-accessories', 'Designer wedding wear, premium sarees, sherwanis, watches & tuxedos.', 'fa-shirt', 'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&fit=crop&w=800&q=80', 1),
(3, 'Photography Equipment', 'photography-equipment', 'DSLR, mirrorless bodies, zoom lenses, gimbals, lighting kits, and drones.', 'fa-camera', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80', 1),
(4, 'Camping and Travel', 'camping-travel', 'All-weather tents, rucksacks, sleeping bags, portable stoves & trekking kits.', 'fa-campground', 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80', 1),
(5, 'Tools and Appliances', 'tools-appliances', 'Heavy duty power drills, pressure washers, lawnmowers & home improvement sets.', 'fa-screwdriver-wrench', 'https://images.unsplash.com/photo-1581147036324-c17ac41dfa6c?auto=format&fit=crop&w=800&q=80', 1),
(6, 'Sports and Fitness', 'sports-fitness', 'Mountain bikes, foldable treadmills, cricket kits & gym gear.', 'fa-dumbbell', 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=800&q=80', 1),
(7, 'Event and Party Equipment', 'event-party-equipment', 'Full HD projectors, PA sound systems, karaoke mics, smoke machines & party lights.', 'fa-champagne-glasses', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=800&q=80', 1),
(8, 'Books and Educational Equipment', 'books-educational', 'Lab equipment, scientific calculators, architecture tools, telescopes & rare books.', 'fa-book-open', 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=800&q=80', 1);

-- Products
INSERT INTO `products` (`id`, `owner_id`, `category_id`, `title`, `slug`, `description`, `daily_price`, `security_deposit`, `original_price`, `condition_grade`, `location`, `city`, `state`, `pincode`, `rental_instructions`, `status`, `is_available`, `views_count`, `featured`) VALUES
(1, 2, 3, 'Sony Alpha A7 III Full-Frame Mirrorless Camera + 28-70mm Lens', 'sony-alpha-a7-iii-mirrorless-camera', 'Professional 24.2MP full-frame sensor, 4K HDR movie recording, 5-axis optical image stabilization. Comes with Sony FE 28-70mm F3.5-5.6 OSS lens, 2 original batteries, high-speed 128GB SD card, and a padded carry bag. Perfect for weddings, travel vlogging, and studio shoots.', 1400.00, 5000.00, 165000.00, 'Like New', 'Kakkanad, Near Infopark', 'Kochi', 'Kerala', '682030', 'Valid Govt ID proof (Aadhaar or Passport) required at pickup. Handle with care; keep away from water/rain without waterproof housing.', 'approved', 1, 142, 1),

(2, 4, 1, 'DJI Mini 3 Pro Drone with 4K HDR Video & DJI RC Smart Controller', 'dji-mini-3-pro-drone-4k', 'Lightweight sub-249g drone featuring obstacle avoidance, 4K/60fps video, true vertical shooting for reels, and 34-minute flight time per battery. Package includes 3 Intelligent Flight Batteries, two-way charging hub, spare propellers, and DJI RC with built-in 5.5-inch FHD screen.', 1200.00, 4000.00, 78000.00, 'Like New', 'Mavoor Road', 'Kozhikode', 'Kerala', '673004', 'Requires basic drone piloting experience. Do not fly in no-fly zones (airports, military areas) or high winds.', 'approved', 1, 98, 1),

(3, 2, 4, 'Quechua MH100 4-Person Waterproof Camping Tent + 2 Sleeping Bags', 'quechua-4-person-waterproof-camping-tent', 'Spacious 4-person dome tent with patented Fresh & Black technology to stay cool in the morning sun. Fully waterproof, wind-resistant up to 50 km/h. Includes 2 thermal sleeping bags (rated 10°C) and an inflatable double ground mattress with foot pump.', 450.00, 1500.00, 12999.00, 'Like New', 'Panampilly Nagar', 'Kochi', 'Kerala', '682036', 'Please dry the tent thoroughly before repacking if exposed to rain or morning dew. Return with all pegs and poles accounted for.', 'approved', 1, 85, 1),

(4, 2, 5, 'Bosch Professional 750W Impact Drill Kit with 100 Accessories', 'bosch-professional-750w-impact-drill-kit', 'Heavy duty Bosch GSB 16 RE 750W impact drill machine. Capable of drilling through concrete, steel, and masonry. Includes 100-piece accessory kit with masonry bits, wood bits, screw bits, spirit level, hammer, and adjustable wrench.', 250.00, 1000.00, 6500.00, 'Like New', 'Edappally', 'Kochi', 'Kerala', '682024', 'Always wear protective eyewear provided. Clean dust off the drill chuck before returning.', 'approved', 1, 64, 0),

(5, 3, 2, 'Designer Velvet Sherwani with Zari Embroidery, Stole & Safa', 'designer-velvet-sherwani-zari-embroidery', 'Exquisite royal navy blue velvet sherwani designed for grooms or high-profile wedding receptions. Features intricate gold antique zari handwork. Includes matching raw silk churidar, embellished banarasi dupatta/stole, and matching safa (turban). Fits Chest size 38-42.', 1800.00, 3000.00, 28000.00, 'Brand New', 'Kowdiar', 'Trivandrum', 'Kerala', '695003', 'Dry cleaning is covered in the rental fee. Strictly do not iron directly or use perfume sprays on the embroidered velvet.', 'approved', 1, 115, 1),

(6, 4, 1, 'Sony PlayStation 5 Disc Edition + 2 DualSense Controllers & 5 Games', 'sony-playstation-5-console-2-controllers', 'Latest PS5 Disc console delivering ultra-fast SSD loading, ray tracing, and 4K gaming at 120Hz. Comes with two wireless DualSense controllers, HDMI 2.1 cable, and 5 blockbuster games: EA Sports FC 24, Spider-Man 2, God of War Ragnarok, Gran Turismo 7, and Mortal Kombat 1.', 850.00, 3500.00, 54990.00, 'Like New', 'Kowdiar', 'Trivandrum', 'Kerala', '695003', 'Keep in well-ventilated area. Do not modify system firmware or accounts. Original power cables must be returned safely.', 'approved', 1, 210, 1),

(7, 2, 6, 'Trek Marlin 6 Mountain Bike (29 Inch Wheels, Hydraulic Disc Brakes)', 'trek-marlin-6-mountain-bike', 'Premium cross-country mountain bike with lightweight Alpha Silver Aluminium frame, Shimano 1x10 drivetrain, hydraulic disc brakes, and lockable front suspension fork. Comes with certified safety helmet, combination lock, and water bottle cage.', 350.00, 1200.00, 42000.00, 'Gently Used', 'Marine Drive', 'Kochi', 'Kerala', '682011', 'Suitable for rider height 5ft 8in to 6ft 1in. Please lock bike securely whenever parked.', 'approved', 1, 73, 1),

(8, 4, 7, 'Epson Full HD 3800 Lumens Portable Projector + 100 Inch Tripod Screen', 'epson-full-hd-portable-projector-tripod-screen', 'High brightness Epson 3LCD 1080p home theater and presentation projector. Crisp colors even in daytime lighting. Includes HDMI cable, wireless streaming dongle, remote control, and a portable 100-inch rollable tripod screen.', 900.00, 2500.00, 48000.00, 'Like New', 'Swaraj Round', 'Thrissur', 'Kerala', '680001', 'Let projector fan cool down for 5 minutes after turning off before disconnecting power.', 'approved', 1, 92, 1),

(9, 4, 3, 'Canon EOS 1500D DSLR Camera with Dual Lens Kit (18-55mm + 55-250mm)', 'canon-eos-1500d-dslr-camera-dual-lens', 'Reliable 24.1MP APS-C sensor DSLR camera with built-in Wi-Fi & NFC. Includes wide zoom 18-55mm IS II lens and telephoto 55-250mm IS STM lens, 64GB memory card, battery charger, and waterproof shoulder bag. Ideal for beginners, college projects, and nature photography.', 500.00, 2000.00, 42000.00, 'Gently Used', 'Kazhakkoottam', 'Trivandrum', 'Kerala', '695582', 'Do not touch optical lens glass directly. Clean lens only using the provided microfiber cloth.', 'approved', 1, 88, 0),

(10, 3, 2, 'Authentic Kanchipuram Pure Silk Bridal Saree (Emerald Green & Gold)', 'authentic-kanchipuram-pure-silk-saree', 'Handcrafted pure mulberry silk saree with certified Silk Mark tag. Gorgeous emerald green body with rich gold zari pallu featuring traditional peacock motifs. Comes with stitched blouse (size 34-38 adjustable).', 1500.00, 3000.00, 35000.00, 'Like New', 'Ravipuram', 'Kochi', 'Kerala', '682016', 'Do not wash at home; dry-cleaning is handled by the owner after return.', 'approved', 1, 104, 1);

-- Product Images
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=1000&q=80', 1),
(2, 1, 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?auto=format&fit=crop&w=1000&q=80', 0),
(3, 1, 'https://images.unsplash.com/photo-1607462109225-6b64ae2dd3cb?auto=format&fit=crop&w=1000&q=80', 0),

(4, 2, 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?auto=format&fit=crop&w=1000&q=80', 1),
(5, 2, 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?auto=format&fit=crop&w=1000&q=80', 0),

(6, 3, 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80', 1),
(7, 3, 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=1000&q=80', 0),

(8, 4, 'https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=1000&q=80', 1),
(9, 4, 'https://images.unsplash.com/photo-1581147036324-c17ac41dfa6c?auto=format&fit=crop&w=1000&q=80', 0),

(10, 5, 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?auto=format&fit=crop&w=1000&q=80', 1),
(11, 5, 'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?auto=format&fit=crop&w=1000&q=80', 0),

(12, 6, 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=1000&q=80', 1),
(13, 6, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=1000&q=80', 0),

(14, 7, 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=1000&q=80', 1),
(15, 7, 'https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?auto=format&fit=crop&w=1000&q=80', 0),

(16, 8, 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?auto=format&fit=crop&w=1000&q=80', 1),
(17, 8, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1000&q=80', 0),

(18, 9, 'https://images.unsplash.com/photo-1512790182412-b19e6d62bc39?auto=format&fit=crop&w=1000&q=80', 1),

(19, 10, 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=1000&q=80', 1);

-- Bookings Seed
INSERT INTO `bookings` (`id`, `booking_code`, `product_id`, `renter_id`, `owner_id`, `start_date`, `end_date`, `total_days`, `daily_rate`, `rental_amount`, `security_deposit`, `service_fee`, `total_amount`, `status`, `notes`) VALUES
(1, 'RUH-2026-9001', 1, 5, 2, '2026-11-15', '2026-11-17', 2, 1400.00, 2800.00, 5000.00, 100.00, 7900.00, 'approved', 'College short film shoot in Kochi Fort.'),
(2, 'RUH-2026-9002', 3, 5, 2, '2026-11-01', '2026-11-03', 2, 450.00, 900.00, 1500.00, 50.00, 2450.00, 'completed', 'Weekend trip to Vagamon hills. Everything returned cleanly.'),
(3, 'RUH-2026-9003', 6, 2, 4, '2026-11-20', '2026-11-22', 2, 850.00, 1700.00, 3500.00, 80.00, 5280.00, 'pending', 'Family holiday weekend gaming tournament.');

-- Payments Seed
INSERT INTO `payments` (`id`, `booking_id`, `transaction_id`, `payment_method`, `amount`, `deposit_amount`, `payment_status`, `payment_gateway_ref`, `payment_date`) VALUES
(1, 1, 'TXN_RUH_88716281', 'UPI', 2900.00, 5000.00, 'paid', 'DEMO_RAZORPAY_SIM_7718', '2026-11-08 14:30:00'),
(2, 2, 'TXN_RUH_88716282', 'UPI', 950.00, 1500.00, 'paid', 'DEMO_RAZORPAY_SIM_7719', '2026-10-31 10:15:00');

-- Reviews Seed
INSERT INTO `reviews` (`id`, `booking_id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 3, 5, 5, 'The Quechua tent was in spotless condition and kept us super comfortable and dry during a misty night in Vagamon! Rahul was very helpful with instructions.', '2026-11-04 11:20:00');

-- Wishlist Seed
INSERT INTO `wishlist` (`id`, `user_id`, `product_id`) VALUES
(1, 5, 1),
(2, 5, 6),
(3, 2, 8);

-- Notifications Seed
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `is_read`) VALUES
(1, 2, 'Booking Approved', 'Your booking request #RUH-2026-9001 for Sony Alpha A7 III has been confirmed.', 'user/my-bookings.php', 0),
(2, 4, 'New Rental Request Received', 'Sneha Varma requested to rent Sony PlayStation 5 console for 2 days.', 'owner/booking-requests.php', 0);

-- Reports Seed
INSERT INTO `reports` (`id`, `product_id`, `user_id`, `reason`, `details`, `status`) VALUES
(1, 4, 5, 'Pricing Inquiry', 'Just wanted to confirm if the deposit is refunded on the same day upon return.', 'resolved');
