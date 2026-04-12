<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sso_allowed_opds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('app_code', 64)->index();
            $table->unsignedBigInteger('opd_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'app_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sso_allowed_opds');
    }
};
