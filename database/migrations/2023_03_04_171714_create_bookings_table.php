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
        Schema::create(
            'bookings',
            function (Blueprint $table) {
                $table->id();
                $table->string('customer_id')->nullable();
                $table->string('booking_id')->nullable();
                $table->string('event_id')->nullable();
                $table->unsignedBigInteger('organizer_id')->nullable();
                $table->string('fname')->nullable();
                $table->string('lname')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('country')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('zip_code')->nullable();
                $table->string('address')->nullable();
                $table->text('variation')->nullable();
                $table->float('price', 8, 2)->nullable();
                $table->string('quantity')->nullable();
                $table->float('discount')->nullable();
                $table->float('tax', 8, 2)->default(0.00);
                $table->float('commission', 8, 2)->default(0.00);
                $table->float('early_bird_discount')->nullable();
                $table->string('currencyText')->nullable();
                $table->string('currencyTextPosition')->nullable();
                $table->string('currencySymbol')->nullable();
                $table->string('currencySymbolPosition')->nullable();
                $table->string('paymentMethod')->nullable();
                $table->string('gatewayType')->nullable();
                $table->string('paymentStatus')->nullable();
                $table->string('invoice')->nullable();
                $table->string('attachmentFile')->nullable();
                $table->string('event_date')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->double('tax_percentage', 8, 2)->default(0.00);
                $table->double('commission_percentage', 8, 2)->default(0.00);
                $table->integer('scan_status')->default(0)->comment('1=scanned, 0=not scanned yet');
                $table->string('conversation_id')->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
