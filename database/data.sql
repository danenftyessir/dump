-- ENUM
CREATE TYPE user_role AS ENUM ('BUYER', 'SELLER');
CREATE TYPE order_status AS ENUM ('waiting_approval', 'approved', 'rejected', 'on_delivery', 'received');

-- Trigger untuk updated_at
CREATE OR REPLACE FUNCTION update_timestamp()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = CURRENT_TIMESTAMP;
   RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- User
CREATE TABLE User (
    user_id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    balance INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TRIGGER trigger_update_timestamp_user
BEFORE UPDATE ON User
FOR EACH ROW
EXECUTE PROCEDURE update_timestamp();

-- Store
CREATE TABLE Store (
    store_id SERIAL PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    store_name VARCHAR(255) UNIQUE NOT NULL,
    store_description VARCHAR(255),
    store_logo_path VARCHAR(255),
    balance INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);
CREATE TRIGGER trigger_update_timestamp_store
BEFORE UPDATE ON Store
FOR EACH ROW
EXECUTE PROCEDURE update_timestamp();

-- Product
CREATE TABLE Product (
    product_id SERIAL PRIMARY KEY,
    store_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    stock INT NOT NULL,
    main_image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP DEFAULT NULL,
    FOREIGN KEY (store_id) REFERENCES Store(store_id) ON DELETE CASCADE
);
CREATE TRIGGER trigger_update_timestamp_product
BEFORE UPDATE ON Product
FOR EACH ROW
EXECUTE PROCEDURE update_timestamp();

-- Cart Item
CREATE TABLE Cart_Item (
    cart_item_id SERIAL PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Product(product_id) ON DELETE CASCADE
);
CREATE TRIGGER trigger_update_timestamp_cart_item
BEFORE UPDATE ON Cart_Item
FOR EACH ROW
EXECUTE PROCEDURE update_timestamp();

-- Category
CREATE TABLE Category (
    category_id SERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL
);

-- Category Item
CREATE TABLE Category_Item (
    category_id INT NOT NULL,
    product_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Product(product_id) ON DELETE CASCADE
);

-- Order
CREATE TABLE Order (
    order_id SERIAL PRIMARY KEY,
    buyer_id INT NOT NULL,
    store_id INT NOT NULL,
    total_price INT NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    status order_status DEFAULT 'waiting_approval',
    reject_reason TEXT,
    confirmed_at TIMESTAMP,
    delivery_time TIMESTAMP,
    received_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES Store(store_id) ON DELETE CASCADE
);

-- Order Item
CREATE TABLE Order_Items (
    order_item_id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_order INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Order(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Product(product_id) ON DELETE CASCADE
);