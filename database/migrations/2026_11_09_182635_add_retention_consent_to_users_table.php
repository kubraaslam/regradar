<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * SRS NFR8: analyses are not kept beyond the retention period without
     * explicit consent.
     *
     * A timestamp rather than a boolean, so the record shows when consent was
     * given. Null for every existing account: consent is never assumed, and has
     * to be given by the user from their profile.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('retention_consent_at')->nullable()->after('deactivated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('retention_consent_at');
        });
    }
};
