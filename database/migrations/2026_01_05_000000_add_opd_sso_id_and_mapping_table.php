<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sso_allowed_opds', function (Blueprint $table) {
            $table->unsignedBigInteger('opd_sso_id')->nullable()->after('opd_id')->index();
        });

        Schema::create('sso_opd_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sso_opd_id')->index();
            $table->unsignedBigInteger('local_user_id')->index();
            $table->string('opd_name')->nullable();
            $table->timestamps();
            $table->unique(['sso_opd_id','local_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sso_allowed_opds', function (Blueprint $table) {
            $table->dropColumn('opd_sso_id');
        });
        Schema::dropIfExists('sso_opd_mappings');
    }
};
