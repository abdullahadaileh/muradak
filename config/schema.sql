-- ============================================================
--  Muradak Market — MySQL Database Schema
--  Run this file once to set up all tables
--  Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS muradak_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE muradak_db;

-- ─── Categories ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_ar     VARCHAR(120) NOT NULL,
  name_en     VARCHAR(120) NOT NULL,
  slug        VARCHAR(140) NOT NULL UNIQUE,
  icon        VARCHAR(10)  NOT NULL DEFAULT '🛒',
  sort_order  INT          NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Products ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id  INT UNSIGNED NOT NULL,
  name_ar      VARCHAR(200) NOT NULL,
  name_en      VARCHAR(200) NOT NULL,
  description_ar TEXT,
  description_en TEXT,
  price        DECIMAL(10,3) NOT NULL,
  old_price    DECIMAL(10,3) DEFAULT NULL,
  image        VARCHAR(300)  DEFAULT 'placeholder.png',
  stock        INT           NOT NULL DEFAULT 100,
  is_featured  TINYINT(1)    NOT NULL DEFAULT 0,
  is_active    TINYINT(1)    NOT NULL DEFAULT 1,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── Orders ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number    VARCHAR(20)  NOT NULL UNIQUE,
  customer_name   VARCHAR(150) NOT NULL,
  customer_phone  VARCHAR(30)  NOT NULL,
  customer_email  VARCHAR(200) DEFAULT NULL,
  delivery_address TEXT        NOT NULL,
  notes           TEXT,
  subtotal        DECIMAL(10,3) NOT NULL,
  delivery_fee    DECIMAL(10,3) NOT NULL DEFAULT 0.000,
  total           DECIMAL(10,3) NOT NULL,
  payment_method  ENUM('cod') NOT NULL DEFAULT 'cod',
  status          ENUM('pending','confirmed','processing','out_for_delivery','delivered','cancelled')
                  NOT NULL DEFAULT 'pending',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Order Items ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NOT NULL,
  name_ar     VARCHAR(200) NOT NULL,
  name_en     VARCHAR(200) NOT NULL,
  price       DECIMAL(10,3) NOT NULL,
  qty         INT           NOT NULL,
  subtotal    DECIMAL(10,3) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ─── Admin Users ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(80)  NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  email        VARCHAR(200) NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── Settings ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(80)  NOT NULL PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB;

-- ============================================================
--  SEED DATA
-- ============================================================

-- Default admin: username=admin  password=Muradak@2024
INSERT IGNORE INTO admin_users (username, password, email) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'abdullahadaileh957@gmail.com');

-- Categories
INSERT IGNORE INTO categories (name_ar, name_en, slug, icon, sort_order) VALUES
('رقائق ومقرمشات',  'Chips & Snacks',     'chips-snacks',      '🍟', 1),
('شوكولاتة وحلويات','Chocolate & Sweets',  'chocolate-sweets',  '🍫', 2),
('أرز وحبوب',       'Rice & Grains',       'rice-grains',       '🌾', 3),
('مواد تنظيف',      'Cleaning Supplies',   'cleaning-supplies', '🧹', 4),
('مشروبات',         'Beverages',           'beverages',         '🥤', 5),
('منتجات الألبان',  'Dairy Products',      'dairy',             '🥛', 6),
('خضروات وفواكه',   'Fruits & Vegetables', 'fruits-vegetables', '🥦', 7),
('معلبات وجاهز',    'Canned & Ready',      'canned-ready',      '🥫', 8);

-- Sample products
INSERT IGNORE INTO products (category_id, name_ar, name_en, price, old_price, is_featured) VALUES
(1, 'ليز كلاسيك 150 جم',       'Lays Classic 150g',          0.750, 1.000, 1),
(1, 'دوريتوس نكهة الجبن',       'Doritos Cheese',             0.500, NULL,  0),
(1, 'برينجلز أصلي',             'Pringles Original',          1.200, 1.500, 1),
(2, 'كيت كات 4 أصابع',          'KitKat 4 Fingers',           0.350, NULL,  0),
(2, 'سنيكرز 50 جم',             'Snickers 50g',               0.400, NULL,  1),
(2, 'كادبري ميلك 90 جم',        'Cadbury Milk 90g',           0.600, 0.800, 0),
(3, 'أرز بسمتي 5 كيلو',         'Basmati Rice 5kg',           3.500, 4.000, 1),
(3, 'أرز مصري 2 كيلو',          'Egyptian Rice 2kg',          1.200, NULL,  0),
(4, 'صابون جلي للصحون 750مل',   'Fairy Dish Soap 750ml',      1.000, 1.200, 0),
(4, 'مسحوق غسيل أريال 3 كيلو',  'Ariel Washing Powder 3kg',  4.500, 5.000, 1),
(5, 'ماء معدني 1.5 لتر',        'Mineral Water 1.5L',         0.150, NULL,  0),
(5, 'عصير برتقال طازج 1 لتر',   'Fresh Orange Juice 1L',      0.900, 1.100, 1),
(6, 'حليب كامل الدسم 1 لتر',    'Full Fat Milk 1L',           0.600, NULL,  0),
(6, 'لبن زبادي طبيعي',          'Natural Yogurt',             0.400, NULL,  0),
(7, 'طماطم طازجة 1 كيلو',       'Fresh Tomatoes 1kg',         0.300, NULL,  0),
(7, 'موز أصفر ناضج 1 كيلو',     'Ripe Bananas 1kg',           0.450, NULL,  1),
(8, 'تونة في الزيت 170جم',      'Tuna in Oil 170g',           0.800, NULL,  0),
(8, 'فاصوليا بيضاء معلبة',      'Canned White Beans',         0.500, 0.600, 0);

-- Default settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('delivery_fee',   '0.500'),
('min_order',      '2.000'),
('store_open',     '1'),
('whatsapp',       ''),
('store_address',  'سوق التاسعة جمعيات');
