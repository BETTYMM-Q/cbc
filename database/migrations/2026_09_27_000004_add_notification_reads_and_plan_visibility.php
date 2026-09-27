<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('notification_id')->constrained('school_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);
        });

        Schema::table('packages', function (Blueprint $table): void {
            $table->boolean('is_visible_to_schools')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::table('packages', function (Blueprint $table): void { $table->dropColumn('is_visible_to_schools'); });
    }
};
