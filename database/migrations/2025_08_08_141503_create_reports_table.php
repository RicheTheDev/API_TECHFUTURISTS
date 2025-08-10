<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ReportStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_url', 255);
            $table->string('file_type', 50);
            $table->text('feedback')->nullable();
            $table->dateTime('submission_deadline')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            $table->string('status')->default(ReportStatusEnum::Submitted->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
