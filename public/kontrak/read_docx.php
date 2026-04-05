<?php
$zip = new ZipArchive;
$file = __DIR__ . '/Ringkasan Kontrak Pemeliharaan Fasilitas Sisi Udara (Runway)  1 (satu) Paket.docx';
if ($zip->open($file) === TRUE) {
    $content = $zip->getFromName('word/document.xml');
    $zip->close();
    
    // Add newlines after paragraphs
    $content = str_replace(['</w:p>', '<w:br/>'], "\n", $content);
    $content = str_replace('</w:tr>', "\n", $content);
    $text = strip_tags($content);
    
    file_put_contents(__DIR__ . '/spk_text.txt', $text);
    echo "Extracted.\n";
} else {
    echo "Failed to open ZIP.\n";
}
