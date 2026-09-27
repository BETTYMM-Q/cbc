<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feature_requests', function (Blueprint $table): void {
            $table->id(); $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('title'); $table->text('description'); $table->string('status', 30)->default('open');
            $table->text('admin_response')->nullable(); $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('platform_advertisements', function (Blueprint $table): void {
            $table->id(); $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title'); $table->text('message')->nullable(); $table->string('media_type', 10)->default('image');
            $table->string('media_path'); $table->json('audiences'); $table->unsignedInteger('skip_after_seconds')->default(5);
            $table->timestamp('starts_at')->nullable(); $table->timestamp('ends_at')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('platform_advertisements'); Schema::dropIfExists('feature_requests'); }
};
