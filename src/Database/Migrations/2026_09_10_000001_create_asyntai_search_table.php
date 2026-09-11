<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto.
 *
 * Everything the package remembers lives in this one table: the site id, the
 * preview secret, the feed token, the cached status and the owner's settings.
 * A key-value table rather than core_config, because the preview secret and
 * the feed token must never be printed into a configuration form, and the
 * status changes without anybody touching a settings screen.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asyntai_search', function (Blueprint $table) {
            $table->string('name', 64)->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asyntai_search');
    }
};
