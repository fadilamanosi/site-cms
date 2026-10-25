<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_cms_resources')) {
            return;
        }

        Schema::create('site_cms_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('type')->nullable();
            $table->string('size')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_cms_resources');
    }
};
