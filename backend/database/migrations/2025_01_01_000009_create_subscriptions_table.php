<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('restrict');
            $table->foreignId('router_id')->nullable()->constrained('devices_routers')->onDelete('set null');
            $table->foreignId('olt_id')->nullable()->constrained('devices_olt')->onDelete('set null');
            $table->foreignId('onu_id')->nullable()->constrained('devices_onu')->onDelete('set null');
            $table->string('username_pppoe')->unique();
            $table->string('password_pppoe');
            $table->integer('vlan_id')->nullable();
            $table->ipAddress('ip_static')->nullable();
            $table->enum('ip_type', ['dhcp', 'static'])->default('dhcp');
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated'])->default('pending');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
