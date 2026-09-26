<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Records what each analysis cost to produce. Without these figures the
     * computational cost of the pipeline cannot be reported, and the evaluation
     * cannot answer whether the accuracy gained justifies the expense.
     *
     * Nullable rather than defaulted: analyses recorded before this migration
     * were genuinely not measured, and a zero would misrepresent that as free.
     */
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->unsignedInteger('duration_ms')->nullable()->after('prompting_strategy');
            $table->unsignedInteger('prompt_tokens')->nullable()->after('duration_ms');
            $table->unsignedInteger('completion_tokens')->nullable()->after('prompt_tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropColumn(['duration_ms', 'prompt_tokens', 'completion_tokens']);
        });
    }
};
