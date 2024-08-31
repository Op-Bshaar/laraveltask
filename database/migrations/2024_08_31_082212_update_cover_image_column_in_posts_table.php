<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCoverImageColumnInPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            // Update the column to be a string with a length of 2048 characters
            $table->string('cover_image', 2048)->change();
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            // Revert the column back to its previous state
            $table->string('cover_image')->change(); // Adjust based on previous length/type
        });
    }
}
