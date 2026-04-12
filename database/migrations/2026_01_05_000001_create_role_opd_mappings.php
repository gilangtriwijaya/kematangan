<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('role_opd_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('rule_id')->nullable()->index();
            $table->string('role_name')->index();
            $table->unsignedBigInteger('opd_sso_id')->index();
            $table->string('apply_to')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['role_name','opd_sso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_opd_mappings');
    }
};
