<?php
// Diagnóstico de deploy — apagar após uso
header('Content-Type: text/plain; charset=utf-8');

echo "=== CAMINHO DO SERVIDOR ===\n";
echo "__DIR__  : " . __DIR__ . "\n";
echo "__FILE__ : " . __FILE__ . "\n";

echo "\n=== ARQUIVOS STATE FTP ===\n";
foreach (glob(__DIR__ . '/.ftp-deploy-sync-state*.json') as $f) {
    echo basename($f) . " — " . date('Y-m-d H:i:s', filemtime($f)) . "\n";
}
if (empty(glob(__DIR__ . '/.ftp-deploy-sync-state*.json'))) {
    echo "(nenhum arquivo state encontrado)\n";
}

echo "\n=== DATAS DOS ARQUIVOS HTML (raiz) ===\n";
foreach (['index.html', 'eventos.html', 'historia.html', 'contato.html'] as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        echo "$f — " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
    } else {
        echo "$f — NÃO ENCONTRADO\n";
    }
}

echo "\n=== JS PAGE-VIEWS ===\n";
$pv = __DIR__ . '/js/page-views.js';
echo "js/page-views.js: " . (file_exists($pv) ? date('Y-m-d H:i:s', filemtime($pv)) : 'NÃO ENCONTRADO') . "\n";

echo "\n=== PASTAS NA RAIZ ===\n";
foreach (array_diff(scandir(__DIR__), ['.', '..']) as $entry) {
    if (is_dir(__DIR__ . '/' . $entry)) {
        echo "$entry/\n";
    }
}
