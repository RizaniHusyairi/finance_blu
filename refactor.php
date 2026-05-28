<?php

$path = 'resources/views/contracts/show.blade.php';
$content = file_get_contents($path);
$start = strpos($content, '<style>');
$end = strpos($content, '</style>') + 8;
$css = substr($content, $start, $end - $start);

@mkdir('resources/views/partials', 0777, true);
file_put_contents('resources/views/partials/modern-css.blade.php', $css);

$newContent = str_replace($css, "@include('partials.modern-css')", $content);
file_put_contents($path, $newContent);

echo "Success";
