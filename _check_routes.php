<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$routes = app('router')->getRoutes();
$count = 0;
foreach ($routes as $r) {
    $name = (string) $r->getName();
    if (str_starts_with($name, 'pajak-potongan.honor')) {
        echo $name . ' => ' . implode('|', $r->methods()) . ' ' . $r->uri();
        $action = $r->getAction();
        $mw = $r->gatherMiddleware();
        echo '  middleware=[' . implode(',', $mw) . ']' . PHP_EOL;
        $count++;
    }
}
echo 'Total honor routes: ' . $count . PHP_EOL;
