-- Mulai Transaksi
BEGIN;

-- ====================================================================
-- 1. SEEDING USERS
-- Password (semuanya): 'Buyer123!', 'Seller123!', 'Seller234!'
-- ====================================================================
INSERT INTO "users" ("user_id", "email", "password", "role", "name", "address", "balance", "created_at") VALUES
(1, 'buyer@test.com', '$2y$10$f/9.z4z.j8Y.N.O.R.W.C.e3.w8Q.F.G.H.I.J.K.L.M.N.O.P.Q.R', 'BUYER', 'Buyer Test', 'Jl. Pembeli No. 1', 1000000, NOW()),
(2, 'seller1@test.com', '$2y$10$f/9.z4z.j8Y.N.O.R.W.C.e3.w8Q.F.G.H.I.J.K.L.M.N.O.P.Q.R', 'SELLER', 'Seller Satu', 'Jl. Penjual No. 1A', 500000, NOW()),
(3, 'seller2@test.com', '$2y$10$f/9.z4z.j8Y.N.O.R.W.C.e3.w8Q.F.G.H.I.J.K.L.M.N.O.P.Q.R', 'SELLER', 'Seller Dua', 'Jl. Penjual No. 2B', 750000, NOW());

-- Atur ulang sequence user_id agar ID berikutnya adalah 4
SELECT setval(pg_get_serial_sequence('users', 'user_id'), COALESCE(MAX(user_id) + 1, 1), false) FROM users;


-- ====================================================================
-- 2. SEEDING STORES
-- (user_id 2 = Toko 1, user_id 3 = Toko 2)
-- ====================================================================
INSERT INTO "stores" ("store_id", "user_id", "store_name", "store_description", "store_logo_path", "balance") VALUES
(1, 2, 'Toko Kerajinan Asli', 'Menjual kerajinan tangan asli dari pengrajin lokal. Kualitas terjamin.', NULL, 0),
(2, 3, 'Galeri Unik Nusantara', 'Pusat aksesoris dan dekorasi rumah unik dari seluruh nusantara.', NULL, 0);

-- Atur ulang sequence store_id
SELECT setval(pg_get_serial_sequence('stores', 'store_id'), COALESCE(MAX(store_id) + 1, 1), false) FROM stores;


-- ====================================================================
-- 3. SEEDING PRODUCTS
-- (15 produk untuk Toko 1, 15 produk untuk Toko 2)
-- ====================================================================
-- Reset product sequence to start from 1

INSERT INTO "products" ("product_name", "description", "price", "stock", "store_id", "main_image_path") VALUES
('Tas Rotan Bulat', 'Tas rotan bulat selempang, cocok untuk fashion.', 150000, 50, 1, NULL),
('Topi Pantai Jerami', 'Topi jerami lebar untuk melindungi dari matahari.', 75000, 30, 1, NULL),
('Patung Kayu Elang', 'Pahatan kayu jati asli berbentuk elang gagah.', 450000, 10, 1, NULL),
('Gantungan Kunci Batik', 'Gantungan kunci kayu dengan motif batik tulis.', 15000, 100, 1, NULL),
('Taplak Meja Tenun', 'Taplak meja motif tenun ikat Sumba.', 220000, 20, 1, NULL),
('Dompet Kulit Pria', 'Dompet kulit sapi asli, jahitan tangan.', 180000, 40, 1, NULL),
('Lampu Meja Batok Kelapa', 'Lampu hias unik dari batok kelapa ukir.', 95000, 15, 1, NULL),
('Wayang Golek Mini', 'Wayang golek mini (Arjuna) untuk dekorasi.', 55000, 0, 1, NULL), -- Stok Habis
('Set Sendok Kayu', 'Set sendok dan garpu kayu jati.', 35000, 80, 1, NULL),
('Kalung Etnik Dayak', 'Kalung manik-manik khas suku Dayak.', 120000, 25, 1, NULL),
('Keranjang Anyaman Bambu', 'Keranjang serbaguna dari anyaman bambu.', 60000, 5, 1, NULL), -- Stok Sedikit
('Batik Tulis Cirebon', 'Kain batik tulis motif Mega Mendung.', 750000, 8, 1, NULL),
('Miniatur Rumah Gadang', 'Miniatur detail rumah adat Padang.', 300000, 12, 1, NULL),
('Cangkir Keramik Set', 'Set 2 cangkir keramik handmade.', 110000, 0, 1, NULL), -- Stok Habis
('Gelang Akar Bahar', 'Gelang kesehatan pria dari akar bahar.', 85000, 30, 1, NULL),

('Lonceng Angin Bambu', 'Lonceng angin bambu suara menenangkan.', 50000, 60, 2, NULL),
('Selendang Songket Palembang', 'Selendang mewah bahan songket.', 400000, 15, 2, NULL),
('Asbak Ukir Batu', 'Asbak rokok ukiran batu alam.', 65000, 40, 2, NULL),
('Sepatu Lukis Kanvas', 'Sepatu kanvas putih dilukis manual.', 250000, 20, 2, NULL),
('Keris Mini Hiasan', 'Keris mini (replika) untuk pajangan.', 175000, 10, 2, NULL),
('Tas Laptop Ecoprint', 'Tas laptop kain dengan motif ecoprint daun.', 320000, 18, 2, NULL),
('Noken Papua Asli', 'Tas Noken asli rajutan tangan.', 200000, 5, 2, NULL), -- Stok Sedikit
('Topeng Kayu Bali', 'Topeng kayu hiasan dinding khas Bali.', 130000, 30, 2, NULL),
('Piring Lidi Hias', 'Piring lidi untuk dekorasi dinding.', 45000, 50, 2, NULL),
('Tempat Tisu Anyaman Pandan', 'Kotak tisu bahan anyaman daun pandan.', 70000, 0, 2, NULL), -- Stok Habis
('Bros Kebaya Perak', 'Bros perak 925 untuk kebaya.', 350000, 22, 2, NULL),
('Lilin Aromaterapi Cengkeh', 'Lilin aromaterapi bahan alami cengkeh.', 80000, 40, 2, NULL),
('Sabuk Kulit Ukir', 'Ikat pinggang kulit dengan ukiran naga.', 210000, 15, 2, NULL),
('Pembatas Buku Kulit', 'Pembatas buku bahan kulit asli.', 25000, 100, 2, NULL),
('Blangkon Jogja', 'Blangkon motif batik Jogja.', 90000, 30, 2, NULL);

-- Atur ulang sequence product_id
SELECT setval(pg_get_serial_sequence('products', 'product_id'), COALESCE(MAX(product_id) + 1, 1), false) FROM products;


-- ====================================================================
-- 4. SEEDING CATEGORY_ITEMS (Pivot Table)
-- (Asumsi ID Kategori 1 s/d 20)
-- ====================================================================
INSERT INTO "category_items" ("product_id", "category_id") VALUES
(1, 6), (1, 15), (1, 3), -- Tas Rotan
(2, 2), (2, 4), (2, 10), -- Topi Pantai
(3, 8), (3, 17), -- Patung Kayu
(4, 1), (4, 3), (4, 17), -- Gantungan Kunci
(5, 8), (5, 13), -- Taplak Meja
(6, 4), (6, 6), -- Dompet Kulit
(7, 8), (7, 1), -- Lampu Batok
(8, 8), (8, 17), -- Wayang Golek
(9, 13), (9, 7), -- Sendok Kayu
(10, 3), (10, 5), (10, 10), -- Kalung Etnik
(11, 8), (11, 13), -- Keranjang Bambu
(12, 2), (12, 4), (12, 10), -- Batik Tulis
(13, 8), (13, 17), -- Miniatur
(14, 8), (14, 13), -- Cangkir Keramik
(15, 3), (15, 5), (15, 17), -- Gelang
(16, 8), (16, 17), (16, 18), -- Lonceng Angin
(17, 2), (17, 10), (17, 15), -- Selendang Songket
(18, 8), (18, 13), -- Asbak Batu
(19, 2), (19, 5), (19, 14), -- Sepatu Lukis
(20, 8), (20, 17), -- Keris Mini
(21, 2), (21, 3), (21, 6), -- Tas Laptop
(22, 3), (22, 6), (22, 15), -- Noken
(23, 8), (23, 17), -- Topeng Bali
(24, 8), (24, 13), -- Piring Lidi
(25, 8), (25, 13), -- Tempat Tisu
(26, 3), (26, 5), (26, 10), -- Bros Perak
(27, 8), (27, 13), -- Lilin
(28, 4), (28, 6), -- Sabuk Kulit
(29, 19), (29, 17), -- Pembatas Buku
(30, 4), (30, 10); -- Blangkon

-- Selesai Transaksi
-- ====================================================================
-- 5. AUTO-ADD: Tambah produk sampai total 10.000 (jika diperlukan)
-- Script ini menghitung berapa produk yang kurang dan men-generate baris
-- Product akan didistribusikan ke semua store yang ada secara round-robin
-- ====================================================================
DO $$
BEGIN
	-- hanya jalankan jika masih kurang dari 10000
	IF (SELECT COUNT(*) FROM products) < 10000 THEN
		WITH params AS (
			SELECT array_agg(store_id ORDER BY store_id) AS store_ids
			FROM stores
		), counts AS (
			SELECT store_ids, cardinality(store_ids) AS store_count FROM params
		), needed AS (
			SELECT (10000 - (SELECT COUNT(*) FROM products))::int AS to_add
		)
		INSERT INTO products (product_name, description, price, stock, store_id, main_image_path, created_at, updated_at)
		SELECT
			-- build realistic product names by combining adjective, material and type
			(counts.adjectives[((gs-1) % array_length(counts.adjectives,1)) + 1] || ' ' ||
			 counts.materials[((gs-1) % array_length(counts.materials,1)) + 1] || ' ' ||
			 counts.types[((gs-1) % array_length(counts.types,1)) + 1]) AS product_name,
			-- short descriptive sentence
			('Original ' || counts.materials[((gs-1) % array_length(counts.materials,1)) + 1] || ' ' || counts.types[((gs-1) % array_length(counts.types,1)) + 1] || ' crafted by Indonesian artisans.') AS description,
			-- price and stock variation
			(50000 + ((gs * 37) % 450000))::integer AS price,
			(1 + ((gs * 13) % 200))::integer AS stock,
			counts.store_ids[((gs-1) % counts.store_count) + 1]::int AS store_id,
			NULL AS main_image_path,
			NOW() AS created_at,
			NOW() AS updated_at
		FROM (
			SELECT store_ids, store_count,
				   ARRAY['Vintage','Handmade','Classic','Modern','Artisanal','Premium','Eco','Traditional','Elegant','Minimalist']::text[] AS adjectives,
				   ARRAY['Rotan','Batik','Kayu','Kulit','Kain','Keramik','Kanvas','Bambu','Perak','Anyaman']::text[] AS materials,
				   ARRAY['Bag','Hat','Statue','Keychain','Table Cloth','Wallet','Lamp','Wayang','Cutlery Set','Necklace','Basket','Batik Cloth','Miniature','Mug Set','Bracelet','Wind Chime','Shawl','Ashtray','Shoes','Keris','Laptop Bag','Noken','Mask','Plate','Tissue Box','Bros','Candle','Belt','Bookmark','Blangkon']::text[] AS types
			FROM counts
		) AS counts, needed, generate_series(1, (SELECT to_add FROM needed)) AS gs;

		-- advance sequence
		PERFORM setval(pg_get_serial_sequence('products','product_id'), (SELECT COALESCE(MAX(product_id),1) FROM products));
	END IF;
END
$$;

COMMIT;