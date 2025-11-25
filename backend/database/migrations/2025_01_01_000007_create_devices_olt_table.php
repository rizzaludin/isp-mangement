<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices_olt', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->enum('vendor', ['huawei', 'zte', 'fiberhome', 'other'])->default('huawei');
            $table->enum('mgmt_type', ['ssh', 'telnet', 'api', 'snmp'])->default('ssh');
            $table->integer('mgmt_port')->default(22);
            $table->string('mgmt_user');
            $table->text('mgmt_password'); // Encrypted
            $table->text('snmp_community')->nullable(); // Encrypted
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices_olt');
    }
};
