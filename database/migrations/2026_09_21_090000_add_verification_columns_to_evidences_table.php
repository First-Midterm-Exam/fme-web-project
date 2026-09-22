<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evidences', function (Blueprint $table): void {
            $table->text('verification_reason')->nullable()->after('uploaded_by');
            $table->foreignId('verified_by')->nullable()->after('verification_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidences', function (Blueprint $table): void {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verification_reason', 'verified_by', 'verified_at']);
        });
    }
};
