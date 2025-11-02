<?php

namespace Service;

use Exception;

class FileService
{
    // Ctor
    public function construct() {
    }

    public function upload(array $fileData, string $subDir) {
        // tentukan direktori upload
        $uploadDir = __DIR__ . '/../../public/uploads/' . $subDir . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Buat nama file unik
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $fileName = 'file_' . uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        // Pindahin file
        if (!move_uploaded_file($fileData['tmp_name'], $targetPath)) {
            throw new Exception('Gagal menyimpan file yang diunggah.');
        }

        return $fileName;
    }

    /**
     * Handle file upload dengan validasi dan return path lengkap
     * @param array $fileData Data file dari $_FILES
     * @param string $subDir Subdirektori dalam uploads/
     * @param array $allowedMimes MIME types yang diperbolehkan
     * @param int $maxSize Ukuran maksimal file dalam bytes
     * @return string Path lengkap file (untuk disimpan di database dan diakses via web)
     * @throws Exception jika upload gagal
     */
    public function handleUpload(array $fileData, string $subDir, array $allowedMimes = [], int $maxSize = 2097152) {
        // Validasi MIME type jika diberikan
        if (!empty($allowedMimes)) {
            $fileMimeType = mime_content_type($fileData['tmp_name']);
            if (!in_array($fileMimeType, $allowedMimes)) {
                throw new Exception('Format file tidak valid. Hanya ' . implode(', ', $allowedMimes) . ' yang diperbolehkan.');
            }
        }

        // Validasi ukuran file
        if ($fileData['size'] > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            throw new Exception("Ukuran file terlalu besar. Maksimal {$maxSizeMB}MB.");
        }

        // Upload file
        $fileName = $this->upload($fileData, $subDir);

        // Return path lengkap untuk web access
        return '/uploads/' . $subDir . '/' . $fileName;
    }

    /**
     * Delete file dari path yang diberikan
     * @param string|null $filePath Path file relatif dari public (misal: /uploads/logos/file.jpg)
     * @return bool true jika berhasil atau file tidak ada, false jika gagal
     */
    public function deleteFile($filePath) {
        if (empty($filePath)) {
            return true; // Tidak ada file yang perlu dihapus
        }

        // Konversi path relatif ke absolute path
        // Path dari database biasanya: /uploads/logos/file.jpg
        // Kita perlu konversi ke: __DIR__/../../public/uploads/logos/file.jpg
        $absolutePath = __DIR__ . '/../../public' . $filePath;

        if (file_exists($absolutePath)) {
            return unlink($absolutePath);
        }

        return true; // File tidak ada, anggap sukses
    }
}