<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('device_type', ['router', 'olt', 'onu']);
            $table->unsignedBigInteger('device_id');
            $table->string('metric_type'); // cpu, memory, traffic, optical_power
            $table->json('value'); // Flexible for different metric types
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['device_type', 'device_id', 'recorded_at']);
            $table->index(['metric_type', 'recorded_at']);
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('device_type', ['router', 'olt', 'onu'])->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title');
            $table->text('message');
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['status', 'severity', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('monitoring_logs');
    }
};
