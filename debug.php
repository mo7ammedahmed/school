<?php

require __DIR__.'/bootstrap/app.php';

$app = Illuminate\Foundation\Application::bootstrapWith();

$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/debug-db', 'GET')
);

$response->send();

