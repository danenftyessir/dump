<?php

namespace Validator;

use Model\Store;
use Exception\ValidationException;

class StoreValidator
{
    private Store $storeModel;

    // Ctor
    public function __construct(Store $storeModel) {
        $this->storeModel = $storeModel;
    }

    // Validasi data toko
    public function validateUpdate(array $data, ?array $fileData, ?int $currentStoreId = null): bool
    {
        $errors = [];

        // validasi nama toko, deskripsi, logo
        if (empty(trim($data['store_name'] ?? ''))) {
            $errors['store_name'] = 'Nama toko wajib diisi.';
        } elseif (strlen($data['store_name']) > 100) {
            $errors['store_name'] = 'Nama toko tidak boleh lebih dari 100 karakter.';
        } else {
            // validasi unik nama toko
            $existing = $this->storeModel->findByStoreName($data['store_name']);

            if ($existing && $existing['store_id'] != $currentStoreId) {
                $errors['store_name'] = 'Nama toko ini sudah digunakan.';
            }
        }

        // validasi deskripsi toko
        $cleanDescription = trim(strip_tags($data['store_description'] ?? ''));
        if (empty($cleanDescription)) {
             $errors['store_description'] = 'Deskripsi toko wajib diisi.';
        }

        // validasi file logo toko (jika ada)
        if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
            
            // Cek Tipe File (MIME type lebih aman dari ekstensi)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileMimeType = mime_content_type($fileData['tmp_name']);
            
            if (!in_array($fileMimeType, $allowedTypes)) {
                $errors['store_logo'] = 'Format file tidak valid. Hanya JPG, PNG, atau WEBP.';
            }
        } elseif ($fileData && $fileData['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['store_logo'] = 'Terjadi error saat mengupload logo. Silakan coba lagi.';
        }

        if (!empty($errors)) {
            throw new ValidationException('Validasi toko gagal', $errors);
        }
        return true;
    }
}