<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Defaults rather than nullable columns: every analysis recorded before this
     * migration was run with GPT-4o-mini and zero-shot prompting, so the defaults
     * label the existing rows correctly and keep them usable as evaluation data.
     */
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->string('llm_model')->default('gpt-4o-mini')->after('status');
            $table->string('prompting_strategy')->default('zero-shot')->after('llm_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['llm_model', 'prompting_strategy']);
        });
    }
};
