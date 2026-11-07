<?php

namespace App\Services;

use Carbon\Carbon;

class VirtualAccountService
{
    /**
     * Bank codes for VA generation
     */
    private const BANK_CODES = [
        'bca' => '014',
        'mandiri' => '008',
        'bni' => '009',
        'bri' => '002',
        'permata' => '013',
    ];

    /**
     * Generate a dummy Virtual Account number
     * Format: [BANK_CODE][COMPANY_CODE][TIMESTAMP][RANDOM]
     * Example: 014888202510061234567 (BCA)
     *
     * @param string $bank Bank name (bca, mandiri, bni, bri, permata)
     * @param string|null $companyCode Custom company code (default: 888)
     * @return string
     */
    public function generateVANumber(string $bank = 'bca', ?string $companyCode = null): string
    {
        $bankCode = self::BANK_CODES[strtolower($bank)] ?? self::BANK_CODES['bca'];
        $companyCode = $companyCode ?? '888'; // Default company code for demo
        
        // Format: YYMMDD + random 7 digits
        $dateCode = Carbon::now()->format('ymd');
        $randomCode = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        
        return $bankCode . $companyCode . $dateCode . $randomCode;
    }

    /**
     * Generate VA expiry datetime
     *
     * @param int $days Days until expiry (default: 7)
     * @return Carbon
     */
    public function generateExpiryDate(int $days = 7): Carbon
    {
        return Carbon::now()->addDays($days)->endOfDay();
    }

    /**
     * Generate VA expiry at the end of the current month (Hari-H penagihan).
     */
    public function generateMonthEndExpiry(): Carbon
    {
        return Carbon::now()->endOfMonth()->endOfDay();
    }

    /**
     * Validate VA number format
     *
     * @param string $vaNumber
     * @return bool
     */
    public function isValidVAFormat(string $vaNumber): bool
    {
        // VA should be numeric and between 16-20 digits
        return preg_match('/^\d{16,20}$/', $vaNumber);
    }

    /**
     * Check if VA is expired
     *
     * @param Carbon|string $expiryDate
     * @return bool
     */
    public function isExpired($expiryDate): bool
    {
        if (is_string($expiryDate)) {
            $expiryDate = Carbon::parse($expiryDate);
        }
        
        return Carbon::now()->isAfter($expiryDate);
    }

    /**
     * Get bank name from VA number
     *
     * @param string $vaNumber
     * @return string|null
     */
    public function getBankFromVA(string $vaNumber): ?string
    {
        $bankCode = substr($vaNumber, 0, 3);
        
        $banks = array_flip(self::BANK_CODES);
        return $banks[$bankCode] ?? null;
    }

    /**
     * Get all supported banks
     *
     * @return array
     */
    public function getSupportedBanks(): array
    {
        return array_keys(self::BANK_CODES);
    }

    /**
     * Format VA number for display (with spaces)
     * Example: 014 888 251006 1234567
     *
     * @param string $vaNumber
     * @return string
     */
    public function formatVADisplay(string $vaNumber): string
    {
        if (strlen($vaNumber) < 16) {
            return $vaNumber;
        }

        // Format: XXX XXX XXXXXX XXXXXXX
        $bankCode = substr($vaNumber, 0, 3);
        $companyCode = substr($vaNumber, 3, 3);
        $dateCode = substr($vaNumber, 6, 6);
        $randomCode = substr($vaNumber, 12);

        return implode(' ', [$bankCode, $companyCode, $dateCode, $randomCode]);
    }
}
