<?php

namespace Controller;

use Base\Controller;
use Model\Product;
use Model\Category;
use Model\Store;
use Exception;

class ProductDiscoveryController extends Controller
{
    private $productModel;
    private $categoryModel;
    private $storeModel;

    // constructor dengan dependency injection
    public function __construct(Product $productModel, Category $categoryModel, Store $storeModel) {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->storeModel = $storeModel;
    }

    // halaman utama - product discovery/home
    public function index() {
        try {
            // ambil semua kategori untuk filter
            $categories = $this->categoryModel->all();
            
            // render view home
            return $this->view('home/index', [
                'categories' => $categories
            ]);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk mendapatkan produk dengan filter, search, pagination
    public function getProducts() {
        try {
            // ambil filter dari query string
            $keyword = $this->input('search', '');
            $categoryId = $this->input('category_id', '');
            $minPrice = $this->input('min_price', '');
            $maxPrice = $this->input('max_price', '');
            $sortBy = $this->input('sort_by', 'created_at');
            $sortOrder = $this->input('sort_order', 'DESC');
            $page = max(1, (int)$this->input('page', 1));
            $limit = max(1, min(50, (int)$this->input('limit', 12)));
            
            // hitung offset
            $offset = ($page - 1) * $limit;
            
            // query untuk filter produk
            $conditions = ["p.stock > 0"]; // hanya tampilkan produk yang ada stoknya
            $bindings = [];
            
            // filter search keyword
            if (!empty($keyword)) {
                $conditions[] = "(p.product_name LIKE :keyword OR p.description LIKE :keyword)";
                $bindings[':keyword'] = '%' . $keyword . '%';
            }
            
            // filter kategori
            if (!empty($categoryId)) {
                $conditions[] = "EXISTS (
                    SELECT 1 FROM category_items ci 
                    WHERE ci.product_id = p.product_id 
                    AND ci.category_id = :category_id
                )";
                $bindings[':category_id'] = $categoryId;
            }
            
            // filter harga minimum
            if (!empty($minPrice) && is_numeric($minPrice)) {
                $conditions[] = "p.price >= :min_price";
                $bindings[':min_price'] = (int)$minPrice;
            }
            
            // filter harga maksimum
            if (!empty($maxPrice) && is_numeric($maxPrice)) {
                $conditions[] = "p.price <= :max_price";
                $bindings[':max_price'] = (int)$maxPrice;
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // query hitung total items
            $countSql = "SELECT COUNT(*) as total 
                         FROM products p 
                         WHERE {$whereClause}";
            
            $db = $this->productModel->getConnection();
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($bindings);
            $totalItems = (int)$countStmt->fetch(\PDO::FETCH_ASSOC)['total'];
            
            // validasi sort by
            $allowedSortBy = ['created_at', 'price', 'product_name'];
            if (!in_array($sortBy, $allowedSortBy)) {
                $sortBy = 'created_at';
            }
            
            // validasi sort order
            $sortOrder = strtoupper($sortOrder);
            if (!in_array($sortOrder, ['ASC', 'DESC'])) {
                $sortOrder = 'DESC';
            }
            
            // query ambil produk
            $sql = "SELECT 
                        p.product_id,
                        p.product_name,
                        p.description,
                        p.price,
                        p.stock,
                        p.main_image_path,
                        s.store_id,
                        s.store_name
                    FROM products p
                    JOIN stores s ON p.store_id = s.store_id
                    WHERE {$whereClause}
                    ORDER BY p.{$sortBy} {$sortOrder}
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($sql);
            
            // bind semua parameter
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            
            $stmt->execute();
            $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // hitung total pages
            $totalPages = ceil($totalItems / $limit);
            
            // response
            return $this->success('Produk Berhasil Dimuat', [
                'products' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalItems,
                    'items_per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman detail produk
    public function showProduct() {
        try {
            $productId = $this->input('id');
            
            if (!$productId) {
                return $this->error('Product ID Tidak Valid', 400);
            }
            
            // ambil data produk dengan kategorinya
            $product = $this->productModel->getProductWithCategories($productId);
            
            if (!$product) {
                return $this->error('Produk Tidak Ditemukan', 404);
            }
            
            // ambil data toko
            $store = $this->storeModel->find($product['store_id']);
            
            // render view detail produk
            return $this->view('product/detail', [
                'product' => $product,
                'store' => $store
            ]);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk search suggestions - BONUS
    public function getSearchSuggestions() {
        try {
            $keyword = $this->input('q', '');
            
            if (strlen($keyword) < 2) {
                return $this->success('Search Suggestions', ['suggestions' => []]);
            }
            
            // query untuk mendapatkan suggestions
            $sql = "SELECT DISTINCT product_name 
                    FROM products 
                    WHERE product_name LIKE :keyword 
                    AND stock > 0
                    ORDER BY product_name ASC 
                    LIMIT 10";
            
            $db = $this->productModel->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%');
            $stmt->execute();
            
            $suggestions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            return $this->success('Search Suggestions', ['suggestions' => $suggestions]);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }
}