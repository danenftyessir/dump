<?php

namespace Service;

use Model\CartItem;

class CartService
{
    protected CartItem $cartItemModel;

    // Ctor
    public function __construct(CartItem $cartItemModel) {
        $this->cartItemModel = $cartItemModel;
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

    // Mengupdate kuantitas item di keranjang
    public function updateItemQuantity(int $cartItemId, int $newQuantity) {
        if ($newQuantity <= 0) {
            return $this->cartItemModel->delete($cartItemId);
        }
        return $this->cartItemModel->update($cartItemId, ['quantity' => $newQuantity]);
    }

    // Menghapus item dari keranjang
    public function removeItem(int $cartItemId) {
        return $this->cartItemModel->delete($cartItemId);
    }

    // Mengosongkan keranjang buyer
    public function clearCart(int $buyerId) {
        return $this->cartItemModel->deleteAllByBuyerId($buyerId);
    }
}