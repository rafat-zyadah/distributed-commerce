<?php

use Illuminate\Support\Facades\Route;

// Every module owns its routes file. Versioned under /api/v1.
Route::prefix('v1')->group(function () {
    require app_path('Modules/Users/routes.php');
    require app_path('Modules/Products/routes.php');
    require app_path('Modules/Orders/routes.php');
    require app_path('Modules/Notifications/routes.php');
});
