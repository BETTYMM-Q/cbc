<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { if (Schema::hasTable('platform_broadcasts') && ! Schema::hasColumn('platform_broadcasts','expires_at')) Schema::table('platform_broadcasts', fn (Blueprint $table) => $table->timestamp('expires_at')->nullable()->after('sent_at')); } public function down(): void { if (Schema::hasTable('platform_broadcasts') && Schema::hasColumn('platform_broadcasts','expires_at')) Schema::table('platform_broadcasts', fn (Blueprint $table) => $table->dropColumn('expires_at')); } };
