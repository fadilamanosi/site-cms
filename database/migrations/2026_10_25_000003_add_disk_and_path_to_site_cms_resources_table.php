<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_cms_resources')) {
            return;
        }

        Schema::table('site_cms_resources', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_cms_resources', 'disk')) {
                $table->string('disk', 50)->nullable()->after('url');
            }
            if (! Schema::hasColumn('site_cms_resources', 'path')) {
                $table->string('path')->nullable()->after('disk');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_cms_resources')) {
            return;
        }

        Schema::table('site_cms_resources', function (Blueprint $table): void {
            if (Schema::hasColumn('site_cms_resources', 'path')) {
                $table->dropColumn('path');
            }
            if (Schema::hasColumn('site_cms_resources', 'disk')) {
                $table->dropColumn('disk');
            }
        });
    }
};
