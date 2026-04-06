<?php

use Core\Migration;

/**
 * Clears the license cache so the next request re-fetches from the API.
 * Run after updating LICENSE_API_URL in .env to point to verify.php.
 */
class ClearLicenseCache extends Migration
{
    public function up(): void
    {
        try {
            $this->db->statement("DELETE FROM license_cache WHERE cache_key = 'license_data'");
            echo "  License cache cleared.\n";
        } catch (\Throwable $e) {
            echo "  Skipped (license_cache table not found).\n";
        }
    }

    public function down(): void
    {
        // nothing to revert
    }
}
