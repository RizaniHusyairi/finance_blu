<?php

namespace App\Services;

use App\Models\DokumenSpp;
use Illuminate\Support\Facades\DB;

class DocumentNumberingService
{
    /**
     * Generate next SPP number for a given year.
     * Format: SPP-BLU/APTP-{Year}/{Sequence}
     */
    public static function getNextSppSequence($year = null): int
    {
        $year = $year ?? date('Y');
        $prefix = "SPP-BLU/APTP-{$year}/";
        
        $maxSpp = DokumenSpp::where('nomor_spp', 'like', "{$prefix}%")
            ->orderBy('nomor_spp', 'desc')
            ->first();

        if ($maxSpp) {
            $parts = explode('/', $maxSpp->nomor_spp);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                return intval($lastPart) + 1;
            }
        }
        return 1;
    }

    public static function generateSppNumber($year = null, $offset = 0): string
    {
        $year = $year ?? date('Y');
        $prefix = "SPP-BLU/APTP-{$year}/";
        $nextSequence = self::getNextSppSequence($year) + $offset;

        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get derived document number from SPP number.
     * Replaces 'SPP' with the new document type (e.g., 'SPM', 'NPI', 'SP2D').
     * If $sppNumber is null, returns null.
     */
    public static function generateDerivedNumber(?string $sppNumber, string $newType): ?string
    {
        if (!$sppNumber) {
            return null;
        }

        // Only replace the first occurrence of "SPP" 
        // to avoid replacing something else by accident, though unlikely.
        return preg_replace('/^SPP/', $newType, $sppNumber);
    }
}
