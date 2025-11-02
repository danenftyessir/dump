<?php

namespace Model;

class OrderStatus
{
    const WAITING_APPROVAL = 'waiting_approval';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const ON_DELIVERY = 'on_delivery';
    const RECEIVED = 'received';

    // Validasi status
    public static function isValid($status)
    {
        return in_array($status, self::getAll());
    }

    // Get semua status
    public static function getAll()
    {
        return [
            self::WAITING_APPROVAL,
            self::APPROVED,
            self::REJECTED,
            self::ON_DELIVERY,
            self::RECEIVED
        ];
    }

    //get Deskripsi status
    public static function getDescription($status)
    {
        $descriptions = [
            self::WAITING_APPROVAL => 'Menunggu konfirmasi',
            self::APPROVED => 'Dikonfirmasi, dikemas',
            self::REJECTED => 'Ditolak oleh seller',
            self::ON_DELIVERY => 'Sedang dikirim',
            self::RECEIVED => 'Sudah sampai'
        ];

        return $descriptions[$status] ?? 'Status Tidak Dikenal';
    }

    // Status transitions yang valid
    public static function canTransition($currentStatus, $newStatus)
    {
        $validTransitions = [
            self::WAITING_APPROVAL => [self::APPROVED, self::REJECTED],
            self::APPROVED => [self::ON_DELIVERY],
            self::REJECTED => [], // Final state
            self::ON_DELIVERY => [self::RECEIVED],
            self::RECEIVED => [] // Final state
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }
}