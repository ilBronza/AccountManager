<?php

$failed = false;
foreach (['src', 'config', 'routes', 'database', 'tests'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/' . $directory));
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            echo implode("\n", $output), "\n";
            $failed = true;
        }
        $output = [];
    }
}
echo $failed ? "PHP syntax check failed.\n" : "PHP syntax check passed.\n";
exit($failed ? 1 : 0);
