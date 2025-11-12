<?php

namespace Service;

use Model\CartItem;
use Model\Product;
use Exception;

class CartService
{
    protected CartItem $cartItemModel;
    protected Product $productModel;

    // Ctor
    public function __construct(CartItem $cartItemModel, Product $productModel) {
        $this->cartItemModel = $cartItemModel;
        $this->productModel = $productModel;
    }

    // Mengelompokkan item keranjang berdasarkan seller/store
    public function groupItemsBySeller(int $buyerId) {
        // get cart items dari model
        $itemsRaw = $this->cartItemModel->getGroupedItemsRaw($buyerId);
        $grouped = [];

        // Proses pengelompokan
        foreach ($itemsRaw as $item) {
            $storeId = $item['store_id'];
            if (!isset($grouped[$storeId])) {
                $grouped[$storeId] = [
                    'store_id' => $item['store_id'],
                    'store_name' => $item['store_name'],
                    'store_logo_path' => $item['store_logo_path'] ?? null,
                    'subtotal' => 0, // Subtotal per toko
                    'items' => []
                ];
            }
            
            // Add item ke dalam grup toko
            $grouped[$storeId]['items'][] = $item;
            $grouped[$storeId]['subtotal'] += $item['item_subtotal'];
        }

        return $grouped;
    }

    // Mendapatkan ringkasan keranjang
    public function getCartSummary(int $buyerId) {
        $groupedCart = $this->groupItemsBySeller($buyerId);
        
        $grandTotal = 0;
        $totalItemsCount = 0;

        foreach ($groupedCart as $store) {
            $grandTotal += $store['subtotal'];
            
            // Hitung total kuantitas produk dari semua toko
            foreach ($store['items'] as $item) {
                $totalItemsCount += $item['quantity'];
            }
        }

        return [
            'stores' => array_values($groupedCart),
            'store_count' => count($groupedCart),
            'total_items_quantity' => $totalItemsCount,
            'grand_total' => $grandTotal,
            'is_empty' => $totalItemsCount === 0
        ];
    }

    // Menambahkan item ke keranjang
    public function addToCart(int $buyerId, int $productId, int $quantity) {
        // Cek apakah item sudah ada di keranjang
        $existingItem = $this->cartItemModel->first(['buyer_id' => $buyerId, 'product_id' => $productId]);
        
        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            return $this->cartItemModel->update($existingItem['cart_item_id'], ['quantity' => $newQuantity]);
        } else {
            // Insert baru
            return $this->cartItemModel->create([
                'buyer_id' => $buyerId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }
    }

    // Menambahkan item ke keranjang dengan validasi stok dan return info lengkap
    public function addToCartWithValidation(int $buyerId, int $productId, int $quantity) {
        // Validasi produk dan stok        
        $product = $this->productModel->find($productId);
        if (!$product) {
            throw new Exception('Produk tidak ditemukan.');
        }
        
        // Cek apakah item sudah ada di keranjang
        $existingItem = $this->cartItemModel->first(['buyer_id' => $buyerId, 'product_id' => $productId]);
        $totalQuantityNeeded = $quantity;
        
        if ($existingItem) {
            $totalQuantityNeeded = $existingItem['quantity'] + $quantity;
        }
        
        // Validasi stok total yang dibutuhkan
        if ($product['stock'] < $totalQuantityNeeded) {
            throw new \Exception("Stok tidak mencukupi. Tersedia: {$product['stock']}, dibutuhkan: {$totalQuantityNeeded}");
        }
        
        // Tambahkan/update item
        if ($existingItem) {
            $this->cartItemModel->update($existingItem['cart_item_id'], ['quantity' => $totalQuantityNeeded]);
        } else {
            $this->cartItemModel->create([
                'buyer_id' => $buyerId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }
        
        // Return total items count
        $newTotalItems = $this->getCartItemCount($buyerId);
        
        return [
            'success' => true,
            'total_items' => $newTotalItems,
            'added_quantity' => $quantity,
            'product_name' => $product['product_name']
        ];
    }

    // Mengupdate kuantitas item di keranjang (UNSAFE - tidak ada pengecekan kepemilikan)
    public function updateItemQuantity(int $cartItemId, int $newQuantity) {
        if ($newQuantity <= 0) {
            return $this->cartItemModel->delete($cartItemId);
        }
        return $this->cartItemModel->update($cartItemId, ['quantity' => $newQuantity]);
    }

    // Menghapus item dari keranjang (UNSAFE - tidak ada pengecekan kepemilikan)
    public function removeItem(int $cartItemId) {
        return $this->cartItemModel->delete($cartItemId);
    }

    // Mengupdate kuantitas item dengan pengecekan kepemilikan dan return subtotal
    public function updateItemQuantityForBuyer(int $buyerId, int $cartItemId, int $newQuantity) {
        // Cek apakah cart item ini milik buyer
        $cartItem = $this->cartItemModel->find($cartItemId);
        if (!$cartItem) {
            throw new \Exception('Cart item tidak ditemukan.');
        }
        
        if ($cartItem['buyer_id'] != $buyerId) {
            throw new \Exception('Anda tidak memiliki akses untuk mengubah item ini.');
        }

        // Ambil informasi produk untuk hitung subtotal
        if ($this->productModel) {
            $product = $this->productModel->find($cartItem['product_id']);
            $itemSubtotal = $product ? ($product['price'] * $newQuantity) : 0;
        } else {
            $itemSubtotal = 0;
        }

        // Update quantity
        if ($newQuantity <= 0) {
            $this->cartItemModel->delete($cartItemId);
            return [
                'success' => true,
                'deleted' => true,
                'cart_item_id' => $cartItemId,
                'item_subtotal' => 0
            ];
        }
        
        $updateResult = $this->cartItemModel->update($cartItemId, ['quantity' => $newQuantity]);
        
        return [
            'success' => true,
            'deleted' => false,
            'cart_item_id' => $cartItemId,
            'item_subtotal' => $itemSubtotal,
            'new_quantity' => $newQuantity
        ];
    }

    // Menghapus item dengan pengecekan kepemilikan  
    public function removeItemForBuyer(int $buyerId, int $cartItemId) {
        // Cek apakah cart item ini milik buyer
        $cartItem = $this->cartItemModel->find($cartItemId);
        if (!$cartItem) {
            throw new \Exception('Cart item tidak ditemukan.');
        }
        
        if ($cartItem['buyer_id'] != $buyerId) {
            throw new \Exception('Anda tidak memiliki akses untuk menghapus item ini.');
        }

        // Hapus item
        return $this->cartItemModel->delete($cartItemId);
    }

    // Mengosongkan keranjang buyer
    public function clearCart(int $buyerId) {
        return $this->cartItemModel->deleteAllByBuyerId($buyerId);
    }

    // Mendapatkan total jumlah item di keranjang
    public function getCartItemCount(int $buyerId): int {
        try {
            return $this->cartItemModel->getTotalQuantityByBuyer($buyerId);
        } catch (\Exception $e) {
            return 0;
        }
    }
}