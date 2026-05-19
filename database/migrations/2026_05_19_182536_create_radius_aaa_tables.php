<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. radcheck table (authentication)
        Schema::create('radcheck', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('customer_service_subscriptions')->cascadeOnDelete();
            $table->string('username', 64)->default('')->index();
            $table->string('attribute', 64)->default('');
            $table->string('op', 2)->default('==');
            $table->string('value', 253)->default('');
            $table->timestamps();
            
            $table->index(['username', 'attribute']);
        });

        // 2. radreply table (authorization / profile assigning)
        Schema::create('radreply', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('customer_service_subscriptions')->cascadeOnDelete();
            $table->string('username', 64)->default('')->index();
            $table->string('attribute', 64)->default('');
            $table->string('op', 2)->default('=');
            $table->string('value', 253)->default('');
            $table->timestamps();
            
            $table->index(['username', 'attribute']);
        });

        // 3. radacct table (accounting / session tracking)
        Schema::create('radacct', function (Blueprint $table) {
            $table->id('radacctid'); // FreeRADIUS uses radacctid by default
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('customer_service_subscriptions')->cascadeOnDelete();
            
            $table->string('acctsessionid', 64)->default('')->index();
            $table->string('acctuniqueid', 32)->default('')->unique();
            $table->string('username', 64)->default('')->index();
            $table->string('groupname', 64)->default('');
            $table->string('realm', 64)->default('');
            $table->string('nasipaddress', 15)->default('')->index();
            $table->string('nasportid', 32)->nullable();
            $table->string('nasporttype', 32)->nullable();
            $table->dateTime('acctstarttime')->nullable()->index();
            $table->dateTime('acctupdatetime')->nullable();
            $table->dateTime('acctstoptime')->nullable()->index();
            $table->integer('acctinterval')->nullable();
            $table->integer('acctsessiontime')->nullable()->index();
            $table->string('acctauthentic', 32)->nullable();
            $table->string('connectinfo_start', 128)->nullable();
            $table->string('connectinfo_stop', 128)->nullable();
            $table->bigInteger('acctinputoctets')->nullable();
            $table->bigInteger('acctoutputoctets')->nullable();
            $table->string('calledstationid', 50)->default('');
            $table->string('callingstationid', 50)->default('')->index();
            $table->string('acctterminatecause', 32)->default('');
            $table->string('servicetype', 32)->nullable();
            $table->string('framedprotocol', 32)->nullable();
            $table->string('framedipaddress', 15)->default('')->index();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radacct');
        Schema::dropIfExists('radreply');
        Schema::dropIfExists('radcheck');
    }
};
