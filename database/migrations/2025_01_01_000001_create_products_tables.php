<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('shopify_id')->index();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->unique(['shop_id','shopify_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('shopify_id')->index();
            $table->string('sku')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('inventory_quantity')->nullable();
            $table->string('option1')->nullable();
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            $table->unique(['product_id','shopify_id']);
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('shopify_id')->index();
            $table->text('src');
            $table->integer('position')->nullable();
            $table->string('alt')->nullable();
            $table->unique(['product_id','shopify_id']);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
