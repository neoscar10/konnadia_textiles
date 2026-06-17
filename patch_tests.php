<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('tests'));
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $c = file_get_contents($file->getPathname());
        $n = str_replace("'is_active' => true,", "'is_active' => true,\n            'gst_percentage' => 12.0,\n            'hsn_code' => '6205',", $c);
        if ($c !== $n) {
            file_put_contents($file->getPathname(), $n);
            echo "Updated " . $file->getPathname() . "\n";
        }
    }
}
