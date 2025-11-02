<?php

namespace Service;

use PDO;

class AdvancedSearchService
{
    private $db;

    public function __construct(PDO $db = null)
    {
        if ($db === null) {
            // For backward compatibility, create connection if not provided
            $config = require __DIR__ . '/../../config/database.php';
            $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            $this->db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } else {
            $this->db = $db;
        }
    }

    /**
     * Extract keywords from text for indexing
     * Removes stopwords, extracts meaningful terms
     */
    public function extractKeywords(string $text): array
    {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // Remove HTML tags
        $text = strip_tags($text);

        // Remove special characters, keep only alphanumeric and spaces
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);

        // Split into words
        $words = preg_split('/\s+/', $text);

        // Indonesian & English stopwords to remove
        $stopwords = [
            'dan', 'atau', 'yang', 'untuk', 'dari', 'dengan', 'pada', 'di', 'ke', 'ini', 'itu',
            'adalah', 'akan', 'telah', 'sudah', 'bisa', 'dapat', 'jika', 'juga', 'saja', 'nya',
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with',
            'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does',
            'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can',
            'this', 'that', 'these', 'those', 'it', 'its', 'by', 'as'
        ];

        // Filter words
        $keywords = array_filter($words, function ($word) use ($stopwords) {
            return strlen($word) > 2 && !in_array($word, $stopwords);
        });

        // Remove duplicates and get frequency
        $keywordFreq = array_count_values($keywords);

        // Sort by frequency (most common first)
        arsort($keywordFreq);

        return $keywordFreq;
    }

    /**
     * Extract potential brand names from product name
     * Heuristic: First word or capitalized words in original text
     */
    public function extractBrand(string $productName): ?string
    {
        // Get first word (often brand name)
        $words = explode(' ', trim($productName));
        $firstWord = $words[0] ?? null;

        if ($firstWord && strlen($firstWord) > 2) {
            return ucfirst(strtolower($firstWord));
        }

        return null;
    }

    /**
     * Index a product's keywords for advanced search
     * Call this when product is created or updated
     */
    public function indexProduct(int $productId, string $productName, string $description): bool
    {
        try {
            // Extract keywords from name and description
            $nameKeywords = $this->extractKeywords($productName);
            $descKeywords = $this->extractKeywords($description);

            // Combine with higher weight for name keywords
            $allKeywords = [];
            foreach ($nameKeywords as $keyword => $freq) {
                $allKeywords[$keyword] = ($allKeywords[$keyword] ?? 0) + ($freq * 3); // Name has 3x weight
            }
            foreach ($descKeywords as $keyword => $freq) {
                $allKeywords[$keyword] = ($allKeywords[$keyword] ?? 0) + $freq;
            }

            // Extract brand
            $brand = $this->extractBrand($productName);

            // Update product search_cache and brand
            $searchCache = implode(' ', array_keys($allKeywords));
            if ($brand) {
                $searchCache .= ' ' . strtolower($brand);
            }

            $stmt = $this->db->prepare("
                UPDATE products
                SET search_cache = ?,
                    brand_extracted = ?,
                    search_keywords = ?
                WHERE product_id = ?
            ");
            $keywordsJson = json_encode(array_keys($allKeywords));
            $stmt->execute([$searchCache, $brand, $keywordsJson, $productId]);

            // Clear old keywords
            $stmt = $this->db->prepare("DELETE FROM product_search_keywords WHERE product_id = ?");
            $stmt->execute([$productId]);

            // Insert new keywords (top 20 most relevant)
            $topKeywords = array_slice($allKeywords, 0, 20, true);
            foreach ($topKeywords as $keyword => $frequency) {
                $stmt = $this->db->prepare("
                    INSERT INTO product_search_keywords (product_id, keyword, keyword_source, frequency)
                    VALUES (?, ?, 'auto', ?)
                    ON CONFLICT (product_id, keyword) DO UPDATE SET frequency = EXCLUDED.frequency
                ");
                $stmt->execute([$productId, $keyword, $frequency]);
            }

            // Add brand as keyword with high frequency
            if ($brand) {
                $stmt = $this->db->prepare("
                    INSERT INTO product_search_keywords (product_id, keyword, keyword_source, frequency)
                    VALUES (?, ?, 'name', 999)
                    ON CONFLICT (product_id, keyword) DO UPDATE SET frequency = 999
                ");
                $stmt->execute([$productId, strtolower($brand)]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error indexing product {$productId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Advanced search with fuzzy matching and keyword expansion
     *
     * @param string $query Search query from user
     * @param int $limit Number of results
     * @return array Product IDs with relevance scores
     */
    public function advancedSearch(string $query, int $limit = 100): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        // Extract keywords from query
        $queryKeywords = $this->extractKeywords($query);
        $queryKeywordsList = array_keys($queryKeywords);

        if (empty($queryKeywordsList)) {
            return [];
        }

        $results = [];

        // 1. Exact match in product name (highest score)
        $results = array_merge($results, $this->searchExactMatch($query));

        // 2. Fuzzy match using LIKE (case-insensitive)
        $results = array_merge($results, $this->searchFuzzyMatch($query));

        // 3. Keyword-based search
        foreach ($queryKeywordsList as $keyword) {
            $results = array_merge($results, $this->searchByKeyword($keyword));
        }

        // 4. Brand search
        $results = array_merge($results, $this->searchByBrand($query));

        // 5. Full-text search
        if (count($queryKeywordsList) > 1) {
            $results = array_merge($results, $this->searchFullText($query));
        }

        // Combine and calculate final scores
        $finalScores = [];
        foreach ($results as $result) {
            $productId = $result['product_id'];
            $score = $result['score'];

            if (!isset($finalScores[$productId])) {
                $finalScores[$productId] = 0;
            }
            $finalScores[$productId] += $score;
        }

        // Sort by score (highest first)
        arsort($finalScores);

        // Return top results
        return array_slice(array_keys($finalScores), 0, $limit, true);
    }

    private function searchExactMatch(string $query): array
    {
        $results = [];
        try {
            $stmt = $this->db->prepare("
                SELECT product_id, 100 as score
                FROM products
                WHERE LOWER(product_name) = LOWER(?)
                AND deleted_at IS NULL
            ");
            $stmt->execute([$query]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Exact match error: " . $e->getMessage());
        }
        return $results;
    }

    private function searchFuzzyMatch(string $query): array
    {
        $results = [];
        try {
            $likeQuery = '%' . $query . '%';
            $stmt = $this->db->prepare("
                SELECT product_id, 80 as score
                FROM products
                WHERE (LOWER(product_name) LIKE LOWER(?) OR LOWER(search_cache) LIKE LOWER(?))
                AND deleted_at IS NULL
            ");
            $stmt->execute([$likeQuery, $likeQuery]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Fuzzy match error: " . $e->getMessage());
        }
        return $results;
    }

    private function searchByKeyword(string $keyword): array
    {
        $results = [];
        try {
            $stmt = $this->db->prepare("
                SELECT psk.product_id, (psk.frequency * 10) as score
                FROM product_search_keywords psk
                JOIN products p ON psk.product_id = p.product_id
                WHERE LOWER(psk.keyword) = LOWER(?)
                AND p.deleted_at IS NULL
            ");
            $stmt->execute([$keyword]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Keyword search error: " . $e->getMessage());
        }
        return $results;
    }

    private function searchByBrand(string $query): array
    {
        $results = [];
        try {
            $stmt = $this->db->prepare("
                SELECT product_id, 90 as score
                FROM products
                WHERE LOWER(brand_extracted) = LOWER(?)
                AND deleted_at IS NULL
            ");
            $stmt->execute([$query]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Brand search error: " . $e->getMessage());
        }
        return $results;
    }

    private function searchFullText(string $query): array
    {
        $results = [];
        try {
            // Use PostgreSQL full-text search
            $stmt = $this->db->prepare("
                SELECT product_id, 50 as score
                FROM products
                WHERE to_tsvector('english', product_name) @@ plainto_tsquery('english', ?)
                AND deleted_at IS NULL
            ");
            $stmt->execute([$query]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Fulltext might not be available, skip
            error_log("Fulltext search error: " . $e->getMessage());
        }
        return $results;
    }

    /**
     * Log search query for analytics
     */
    public function logSearch(string $query, int $resultsCount, ?int $userId = null): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO search_log (search_query, results_count, user_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$query, $resultsCount, $userId]);
        } catch (\Exception $e) {
            // Logging failure shouldn't break search
            error_log("Search log error: " . $e->getMessage());
        }
    }

    /**
     * Get search suggestions based on popular queries
     */
    public function getSearchSuggestions(string $partial, int $limit = 5): array
    {
        try {
            $likeQuery = $partial . '%';
            $stmt = $this->db->prepare("
                SELECT DISTINCT search_query, COUNT(*) as popularity
                FROM search_log
                WHERE search_query LIKE ?
                GROUP BY search_query
                ORDER BY popularity DESC, created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$likeQuery, $limit]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Reindex all products (run this via CLI for existing products)
     */
    public function reindexAllProducts(): int
    {
        $stmt = $this->db->query("
            SELECT product_id, product_name, description
            FROM products
            WHERE deleted_at IS NULL
        ");
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $indexed = 0;
        foreach ($products as $product) {
            if ($this->indexProduct($product['product_id'], $product['product_name'], $product['description'])) {
                $indexed++;
            }
        }

        return $indexed;
    }
}
