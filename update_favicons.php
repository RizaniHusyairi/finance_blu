<?php
$files = [
    'resources/views/layouts/app.blade.php',
    'resources/views/layouts/guest.blade.php',
    'resources/views/public/contract-tte.blade.php',
    'resources/views/public/spp-tte.blade.php',
    'resources/views/public/tagihan-aktivitas.blade.php',
    'resources/views/public/tagihan-jasa-show.blade.php',
    'resources/views/public/tagihan-jasa-verify.blade.php',
];

$faviconLink = '<link rel="icon" href="{{ asset(\'logo/minilogo-sikeren.png\') }}" type="image/png">';

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Remove existing favicon links
        $content = preg_replace('/<link[^>]+rel=["\']icon["\'][^>]*>/i', '', $content);
        
        // Insert new favicon link before </head>
        $content = str_replace('</head>', "    $faviconLink\n</head>", $content);
        
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
