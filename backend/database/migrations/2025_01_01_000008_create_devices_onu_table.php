<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices_onu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('devices_olt')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number')->unique();
            $table->string('pon_port'); // e.g., 0/1/1
            $table->integer('onu_index');
            $table->decimal('signal_rx', 5, 2)->nullable(); // dBm
            $table->decimal('signal_tx', 5, 2)->nullable(); // dBm
            $table->enum('status', ['online', 'offline', 'disabled', 'unregistered'])->default('unregistered');
            $table->timestamp('last_online')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['olt_id', 'pon_port', 'onu_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices_onu');
    }
};
