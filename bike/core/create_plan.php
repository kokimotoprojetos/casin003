<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$plan = new \App\Models\Plan();
$plan->name = 'Scooter Elétrica 1000W WD-4 60V (Plano 2)';
$plan->minimum = 0;
$plan->maximum = 0;
$plan->fixed_amount = 30;
$plan->interest = 4.80;
$plan->interest_type = 0;
$plan->time = 24;
$plan->time_name = 'Daily';
$plan->status = 1;
$plan->featured = 1;
$plan->capital_back = 1;
$plan->lifetime = 0;
$plan->repeat_time = 12;
$plan->image = 'plano222';
$plan->save();

echo "Plan created with ID: " . $plan->id . "\n";
