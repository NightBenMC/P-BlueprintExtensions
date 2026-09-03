<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('fileflow_commands')) {
            Schema::create('fileflow_commands', function (Blueprint $table) {
            $table->id();
            $table->string('server_id')->index();
            $table->bigInteger('user_id')->unsigned();
            $table->string('label');
            $table->text('command');
            $table->string('v1_name')->nullable();
            $table->string('v1_default')->nullable();
            $table->string('v2_name')->nullable();
            $table->string('v2_default')->nullable();
            $table->string('v3_name')->nullable();
            $table->string('v3_default')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('fileflow_commands');
    }
};
