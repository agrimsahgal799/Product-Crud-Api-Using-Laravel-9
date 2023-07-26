<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_product', function (Blueprint $table) {
            $table->increments('id');
            $table->string("name", 255);
            $table->string("slug", 255)->unique();
            $table->longText("description")->nullable();
            $table->integer("inventory");
            $table->enum("inventory_status",['no','yes'])->default('no');
            $table->integer("option_set")->nullable();
            $table->enum("hide_from_shop",['no','yes'])->default('no');
            $table->enum("status",['enable','disable'])->default('enable');
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_product');
    }
};
