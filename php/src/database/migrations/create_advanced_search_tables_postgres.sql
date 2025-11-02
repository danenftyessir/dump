-- Migration: Advanced Search Keyword Extraction (PostgreSQL)
-- Auto-extract keywords from product name & description

-- 1. Add search_cache column to products for performance
ALTER TABLE products
ADD COLUMN IF NOT EXISTS search_cache TEXT,
ADD COLUMN IF NOT EXISTS search_keywords TEXT,
ADD COLUMN IF NOT EXISTS brand_extracted VARCHAR(100);

-- 2. Create text search indexes for advanced search (PostgreSQL uses GIN indexes)
CREATE INDEX IF NOT EXISTS idx_product_name_search ON products USING GIN (to_tsvector('english', product_name));
CREATE INDEX IF NOT EXISTS idx_description_search ON products USING GIN (to_tsvector('english', description));
CREATE INDEX IF NOT EXISTS idx_search_cache_search ON products USING GIN (to_tsvector('english', COALESCE(search_cache, '')));

-- 3. Create ENUM type for keyword_source
DO $$ BEGIN
    CREATE TYPE keyword_source_type AS ENUM ('name', 'description', 'category', 'auto');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- 4. Create product_search_keywords table (auto-populated)
CREATE TABLE IF NOT EXISTS product_search_keywords (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    keyword_source keyword_source_type DEFAULT 'auto',
    frequency INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE (product_id, keyword)
);

-- 5. Create indexes for keyword search
CREATE INDEX IF NOT EXISTS idx_keyword ON product_search_keywords (keyword);
CREATE INDEX IF NOT EXISTS idx_product_keyword ON product_search_keywords (product_id, keyword);
CREATE INDEX IF NOT EXISTS idx_keyword_search ON product_search_keywords USING GIN (to_tsvector('english', keyword));

-- 6. Create trigger for updated_at (PostgreSQL doesn't have ON UPDATE CURRENT_TIMESTAMP)
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

DROP TRIGGER IF EXISTS update_product_search_keywords_updated_at ON product_search_keywords;
CREATE TRIGGER update_product_search_keywords_updated_at
    BEFORE UPDATE ON product_search_keywords
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- 7. Create search_log table for learning user patterns
CREATE TABLE IF NOT EXISTS search_log (
    log_id SERIAL PRIMARY KEY,
    search_query VARCHAR(255) NOT NULL,
    results_count INTEGER DEFAULT 0,
    user_id INTEGER NULL,
    clicked_product_id INTEGER NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Create indexes for search_log
CREATE INDEX IF NOT EXISTS idx_search_query ON search_log (search_query);
CREATE INDEX IF NOT EXISTS idx_created_at ON search_log (created_at);

-- Note: Keywords will be auto-extracted via PHP when product is created/updated