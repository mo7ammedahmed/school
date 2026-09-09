<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pages', function (Blueprint $table): void {
            $table->string('title_ar')->nullable()->after('title');
            $table->string('template')->default('standard')->after('content');
            $table->json('sections')->nullable()->after('template');
            $table->string('status')->default('draft')->after('is_published');
            $table->string('seo_title')->nullable()->after('seo_metadata');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->string('canonical_url')->nullable()->after('seo_description');
            $table->string('robots')->default('index,follow')->after('canonical_url');
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            $table->index(['school_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('content_pages', function (Blueprint $table): void {
            $table->dropIndex(['school_id', 'status', 'published_at']);
            $table->dropColumn([
                'title_ar',
                'template',
                'sections',
                'status',
                'seo_title',
                'seo_description',
                'canonical_url',
                'robots',
                'scheduled_at',
            ]);
        });
    }
};
