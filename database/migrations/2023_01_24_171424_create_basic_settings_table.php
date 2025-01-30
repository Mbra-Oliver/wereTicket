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
            'basic_settings',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('uniqid')->default(12345);
                $table->string('favicon')->nullable();
                $table->string('logo')->nullable();
                $table->string('website_title')->nullable();
                $table->string('email_address')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('address')->nullable();
                $table->decimal('latitude', 8, 5)->nullable();
                $table->decimal('longitude', 8, 5)->nullable();
                $table->unsignedSmallInteger('theme_version');
                $table->string('base_currency_symbol')->nullable();
                $table->string('base_currency_symbol_position', 20)->nullable();
                $table->string('base_currency_text', 20)->nullable();
                $table->string('base_currency_text_position', 20)->nullable();
                $table->decimal('base_currency_rate', 8, 2)->nullable();
                $table->string('primary_color', 30)->nullable();
                $table->string('breadcrumb_overlay_color', 30)->nullable();
                $table->decimal('breadcrumb_overlay_opacity', 4, 2)->nullable();
                $table->tinyInteger('smtp_status')->nullable();
                $table->string('smtp_host')->nullable();
                $table->unsignedInteger('smtp_port')->nullable();
                $table->string('encryption', 50)->nullable();
                $table->string('smtp_username')->nullable();
                $table->string('smtp_password')->nullable();
                $table->string('from_mail')->nullable();
                $table->string('from_name')->nullable();
                $table->string('to_mail')->nullable();
                $table->string('breadcrumb')->nullable();
                $table->unsignedTinyInteger('disqus_status')->nullable();
                $table->string('disqus_short_name')->nullable();
                $table->tinyInteger('google_recaptcha_status')->nullable();
                $table->string('google_recaptcha_site_key')->nullable();
                $table->string('google_recaptcha_secret_key')->nullable();
                $table->integer('facebook_login_status')->default(0);
                $table->string('facebook_app_id')->nullable();
                $table->string('facebook_app_secret')->nullable();
                $table->integer('google_login_status')->default(0);
                $table->string('google_client_id')->nullable();
                $table->string('google_client_secret')->nullable();
                $table->unsignedTinyInteger('whatsapp_status')->nullable();
                $table->string('whatsapp_number', 20)->nullable();
                $table->string('whatsapp_header_title')->nullable();
                $table->unsignedTinyInteger('whatsapp_popup_status')->nullable();
                $table->text('whatsapp_popup_message')->nullable();
                $table->string('maintenance_img')->nullable();
                $table->tinyInteger('maintenance_status')->nullable();
                $table->text('maintenance_msg')->nullable();
                $table->string('bypass_token')->nullable();
                $table->string('footer_logo')->nullable();
                $table->string('preloader')->nullable();
                $table->string('admin_theme_version', 10)->default('light');
                $table->string('features_section_image')->nullable();
                $table->string('testimonials_section_image')->nullable();
                $table->string('course_categories_section_image')->nullable();
                $table->string('notification_image')->nullable();
                $table->string('google_adsense_publisher_id')->nullable();
                $table->tinyInteger('shop_status')->default(1)->comment('1 - active, 0 - deactive');
                $table->tinyInteger('catalog_mode')->default(1)->comment('1 - active, 0 - deactive');
                $table->tinyInteger('is_shop_rating')->default(1)->comment('1 - active, 0 - deactive');
                $table->tinyInteger('shop_guest_checkout')->default(1)->comment('1 - active, 0 - deactive');
                $table->float('shop_tax')->nullable();
                $table->double('tax', 8, 2)->default(0.00);
                $table->double('commission', 8, 2)->default(0.00);
                $table->integer('organizer_email_verification')->default(0);
                $table->integer('organizer_admin_approval')->default(0);
                $table->longText('admin_approval_notice')->nullable();
                $table->string('timezone')->nullable();
                $table->timestamp('updated_at')->useCurrent();
                $table->integer('event_guest_checkout_status')->default(0)->comment('0=deactive, 1=active');
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
        Schema::dropIfExists('basic_settings');
    }
};
