<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('page-versioning.tables.pages', 'pages');

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('type')->default('general')->index();
                $table->string('slug')->unique();
                $table->unsignedBigInteger('current_version_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tableName = config('page-versioning.tables.pages', 'pages');
        Schema::dropIfExists($tableName);
    }
};
