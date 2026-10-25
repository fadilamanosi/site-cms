<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_cms_content_values')) {
            return;
        }

        Schema::create('site_cms_content_values', function (Blueprint $table): void {
            $table->id();
            $table->string('page_key', 100)->index();
            $table->string('scope_key', 100)->default('')->index();
            $table->string('field_key', 150);
            $table->string('kind', 20);
            $table->json('value');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();

            $table->unique(['page_key', 'scope_key', 'field_key', 'status'], 'site_cms_content_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_cms_content_values');
    }
};
