<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pagesTable = config('page-versioning.tables.pages', 'pages');
        $versionsTable = config('page-versioning.tables.page_versions', 'page_versions');

        if (!Schema::hasTable($versionsTable)) {
            Schema::create($versionsTable, function (Blueprint $table) use ($pagesTable) {
                $table->id();
                $table->foreignId('page_id')->constrained($pagesTable)->onDelete('cascade');
                $table->string('version_name');
                $table->string('version_code');
                $table->string('title');
                $table->longText('content');
                $table->text('change_summary')->nullable();
                $table->string('status')->default('draft')->index();
                $table->timestamp('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['page_id', 'version_code']);
            });

            // Add foreign key constraint to current_version_id on pages table safely
            Schema::table($pagesTable, function (Blueprint $table) use ($versionsTable) {
                $table->foreign('current_version_id')
                    ->references('id')
                    ->on($versionsTable)
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        $pagesTable = config('page-versioning.tables.pages', 'pages');
        $versionsTable = config('page-versioning.tables.page_versions', 'page_versions');

        if (Schema::hasTable($pagesTable)) {
            Schema::table($pagesTable, function (Blueprint $table) {
                $table->dropForeign(['current_version_id']);
            });
        }

        Schema::dropIfExists($versionsTable);
    }
};
