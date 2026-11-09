<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * SRS FR14: admins can deactivate accounts.
     *
     * A timestamp rather than a boolean, so the record shows when access was
     * withdrawn. Deactivation is reversible and deletes nothing: a deactivated
     * user's analyses and registry entries remain, because other users' reports
     * and the evaluation may depend on them.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deactivated_at');
        });
    }
};
