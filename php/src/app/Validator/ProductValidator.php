<?php
namespace Validator;

use Exception\ValidationException;

class ProductValidator
{
    // Ctor
    public function __construct() {
    }

    // Validasi data produk
    public function validate(array $data, ?array $fileData, bool $isUpdate = false) {
        $errors = [];

        if (empty(trim($data['product_name'] ?? ''))) {
            $errors['product_name'] = 'Nama produk wajib diisi.';
        } elseif (strlen($data['product_name']) > 200) {
            $errors['product_name'] = 'Nama produk tidak boleh lebih dari 200 karakter.';
        }

        $cleanDescription = trim(strip_tags($data['description'] ?? ''));
        if (empty($cleanDescription)) {
            $errors['description'] = 'Deskripsi wajib diisi.';
        } elseif (strlen($cleanDescription) > 1000) { // Cek panjang teks bersih
            $errors['description'] = 'Deskripsi tidak boleh lebih dari 1000 karakter.';
        }

        $price = (int)($data['price'] ?? 0);
        if ($price < 1000) {
            $errors['price'] = 'Harga minimal adalah Rp 1.000.';
        }

        if (!isset($data['stock']) || $data['stock'] === '' || (int)$data['stock'] < 0) {
            $errors['stock'] = 'Stok tidak boleh kurang dari 0.';
        }
        
        if (empty($data['category_ids']) || !is_array($data['category_ids'])) {
             $errors['category_ids'] = 'Pilih minimal satu kategori.';
        }

        if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
            // Tipe File
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileMimeType = mime_content_type($fileData['tmp_name']);
            if (!in_array($fileMimeType, $allowedTypes)) {
                $errors['main_image'] = 'Format file tidak valid (JPG, PNG, WEBP).';
            }
        } elseif (!$isUpdate && (empty($fileData) || $fileData['error'] !== UPLOAD_ERR_OK)) {
            $errors['main_image'] = 'Foto produk wajib diupload.';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Validasi produk gagal', $errors);
        }
        return true;
    }
}