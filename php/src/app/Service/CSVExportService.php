<?php

namespace Service;

class CSVExportService
{
    /**
     * Generate CSV content from array data
     *
     * @param array $data Array of associative arrays
     * @param array|null $headers Optional custom headers (if null, uses array keys)
     * @return string CSV content
     */
    public function generateCSV(array $data, ?array $headers = null): string
    {
        if (empty($data)) {
            return '';
        }

        // Use memory stream for CSV generation
        $output = fopen('php://temp', 'r+');

        // Get headers from first row if not provided
        if ($headers === null) {
            $headers = array_keys($data[0]);
        }

        // Write headers
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        // Get CSV content
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Send CSV file as download to browser
     *
     * @param string $content CSV content
     * @param string $filename Filename for download
     */
    public function downloadCSV(string $content, string $filename): void
    {
        // Clear any output buffers to prevent headers already sent error
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (strlen($content) + 3)); // +3 for BOM
        header('Pragma: no-cache');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        // Output CSV content
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        echo $content;

        // Flush output
        flush();
        exit;
    }

    /**
     * Export products to CSV
     *
     * @param array $products Array of product data
     * @return string CSV content
     */
    public function exportProducts(array $products): string
    {
        // Define headers
        $headers = ['ID', 'Nama Produk', 'Kategori', 'Harga', 'Stok', 'Terjual', 'Status', 'Dibuat'];

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'ID' => $product['product_id'] ?? '',
                'Nama Produk' => $product['product_name'] ?? '',
                'Kategori' => $product['category_name'] ?? 'N/A',
                'Harga' => $product['price'] ?? 0,
                'Stok' => $product['stock'] ?? 0,
                'Terjual' => $product['sold'] ?? 0,
                'Status' => ($product['deleted_at'] ?? null) ? 'Dihapus' : 'Aktif',
                'Dibuat' => isset($product['created_at']) ? date('Y-m-d H:i:s', strtotime($product['created_at'])) : ''
            ];
        }

        // If no data, still create CSV with headers only
        if (empty($data)) {
            $data = [array_combine($headers, array_fill(0, count($headers), ''))];
        }

        return $this->generateCSV($data, $headers);
    }

    /**
     * Export orders to CSV
     *
     * @param array $orders Array of order data
     * @return string CSV content
     */
    public function exportOrders(array $orders): string
    {
        // Define headers
        $headers = ['Order ID', 'Tanggal', 'Pembeli', 'Email', 'Total', 'Status', 'Alamat'];

        $data = [];

        $statusLabels = [
            'waiting_approval' => 'Menunggu Konfirmasi',
            'approved' => 'Dikonfirmasi',
            'on_delivery' => 'Dalam Pengiriman',
            'received' => 'Selesai',
            'rejected' => 'Ditolak'
        ];

        foreach ($orders as $order) {
            $data[] = [
                'Order ID' => $order['order_id'] ?? '',
                'Tanggal' => isset($order['created_at']) ? date('Y-m-d H:i:s', strtotime($order['created_at'])) : '',
                'Pembeli' => $order['buyer_name'] ?? '',
                'Email' => $order['buyer_email'] ?? 'N/A',
                'Total' => $order['total_price'] ?? 0,
                'Status' => $statusLabels[$order['status'] ?? ''] ?? ($order['status'] ?? ''),
                'Alamat' => $order['shipping_address'] ?? 'N/A'
            ];
        }

        // If no data, still create CSV with headers only
        if (empty($data)) {
            $data = [array_combine($headers, array_fill(0, count($headers), ''))];
        }

        return $this->generateCSV($data, $headers);
    }

    /**
     * Export order items (detail pesanan) to CSV
     *
     * @param array $orderItems Array of order item data
     * @return string CSV content
     */
    public function exportOrderItems(array $orderItems): string
    {
        $data = [];

        foreach ($orderItems as $item) {
            $data[] = [
                'Order ID' => $item['order_id'],
                'Produk' => $item['product_name'],
                'Harga Satuan' => $item['price'],
                'Jumlah' => $item['quantity'],
                'Subtotal' => $item['price'] * $item['quantity']
            ];
        }

        return $this->generateCSV($data);
    }
}