<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('sso_user_id')->nullable()->index()->after('id');
            $table->string('sso_app_role_slug', 191)->nullable()->after('role');
            $table->timestamp('sso_last_synced_at')->nullable()->after('sso_app_role_slug');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sso_user_id', 'sso_app_role_slug', 'sso_last_synced_at']);
        });
    }
};
