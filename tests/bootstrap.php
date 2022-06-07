<?php

require_once dirname(__DIR__, 1) . '/vendor/composer/autoload_real.php';

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

(new class()
{
    use CreatesApplication;
})->createApplication();

Artisan::call('config:cache');
DB::statement('CREATE DATABASE hexclan_test');
Artisan::call('migrate:fresh --seed');
Artisan::call('tenants:seed');
$tenant = Tenant::with('domains')->first();
