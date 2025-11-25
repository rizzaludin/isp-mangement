<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radius_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('rate_limit')->nullable(); // e.g., "10M/10M"
            $table->ipAddress('framed_ip')->nullable();
            $table->integer('vlan_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
        });

        Schema::create('radius_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radius_user_id')->constrained()->onDelete('cascade');
            $table->string('nas_ip');
            $table->ipAddress('framed_ip')->nullable();
            $table->string('mac_address')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('stop_time')->nullable();
            $table->bigInteger('bytes_in')->default(0);
            $table->bigInteger('bytes_out')->default(0);
            $table->string('terminate_cause')->nullable();
            $table->timestamps();
            
            $table->index(['radius_user_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radius_sessions');
        Schema::dropIfExists('radius_users');
    }
};
