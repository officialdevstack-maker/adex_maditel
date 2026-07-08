<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentMigrationFieldsToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Written by the parent's redirect_user directive (see
        // ParentSyncPullDirectives) — a non-null migrated_to_parent_at means
        // this customer now has a real account on the parent platform and
        // the frontend should steer them to parent_redirect_url.
        Schema::table('user', function (Blueprint $table) {
            $table->string('parent_redirect_url')->nullable();
            $table->timestamp('migrated_to_parent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(['parent_redirect_url', 'migrated_to_parent_at']);
        });
    }
}
