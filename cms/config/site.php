<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Caminho raiz do site estático
    |--------------------------------------------------------------------------
    | Caminho absoluto da pasta raiz do site estático (onde ficam data/, js/,
    | index.html etc.). Necessário quando cms/ e site/ são pastas irmãs.
    |
    | Produção (Plesk):  SITE_ROOT=/var/www/vhosts/pedisys.com.br/pascomjerico.com.br
    | Dev local:         deixar vazio → fallback para pasta-pai de cms/
    */
    'root' => env('SITE_ROOT'),
];
