<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evidence_versions', function (Blueprint $table): void {
            $table->text('file_url')->nullable()->after('file_public_id');
            $table->foreignId('uploaded_by')->nullable()->after('file_size')
                ->constrained('users')->nullOnDelete();
        });

        DB::table('evidence_versions')->whereNull('uploaded_by')->update([
            'uploaded_by' => DB::raw('(select uploaded_by from evidences where evidences.id = evidence_versions.evidence_id)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence_versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn('file_url');
        });
    }
};
