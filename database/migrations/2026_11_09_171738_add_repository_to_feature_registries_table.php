<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Scopes a registry entry to one repository, such as laravel/framework, so
     * that registries for different codebases can coexist. Without it, a pull
     * request from one repository is scored against every other repository's
     * features, which produces false positives that reflect the registry rather
     * than the model.
     *
     * Nullable: an unscoped entry describes a feature in general and applies to
     * pull requests from any repository, which preserves the behaviour of every
     * entry created before this column existed.
     */
    public function up(): void
    {
        Schema::table('feature_registries', function (Blueprint $table) {
            $table->string('repository')->nullable()->after('user_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_registries', function (Blueprint $table) {
            $table->dropIndex(['repository']);
            $table->dropColumn('repository');
        });
    }
};
