<?php
/**
 * testemunho-process.php
 * Endpoint de recebimento de testemunhos de fé — Paróquia NSR Jericó/PB
 *
 * Mitigações aplicadas:
 *  - Honeypot (anti-bot)
 *  - Rate limiting por IP (sessão)
 *  - Sanitização e validação de todos os inputs (filter_var)
 *  - Sem exposição de dados internos na resposta
 *  - Resposta JSON estruturada
 *  - LGPD: e-mail não é exposto publicamente
 *  - Salva testemunho via API do CMS Laravel (cms/api/testemunhos)
 *    ou envia por e-mail como fallback
 *
 * @see PADRONIZACAO_LAYOUT.md, MELHORIAS_GERAIS.md §12.7
 */

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

/* ─── Configuração ──────────────────────────────────────────────────────── */

define('TSN_EMAIL_TO',        getenv('EMAIL_TO')        ?: 'contato@pascomjerico.com.br');
define('TSN_EMAIL_FROM',      getenv('EMAIL_FROM')      ?: 'nao-responda@pascomjerico.com.br');
define('TSN_EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: 'Site Paróquia NSR Jericó/PB');
define('TSN_RATE_LIMIT_MAX',  3);
define('TSN_RATE_LIMIT_WIN',  600);    // 10 min
define('TSN_MAX_TEXTO_LEN',   2000);

const TSN_ALLOWED_ORIGINS = [
    'https://pascomjerico.com.br',
    'https://www.pascomjerico.com.br',
];

/* ─── Helpers ───────────────────────────────────────────────────────────── */

function tsnRespond(int $status, string $message, array $extra = []): never
{
    http_response_code($status);
    echo json_encode(
        array_merge(['ok' => $status < 400, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

function tsnSanitizeStr(string $value, int $max): string
{
    return mb_substr(strip_tags(trim($value)), 0, $max);
}

/* ─── Método ────────────────────────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'token') {
        $_SESSION['tsn_csrf_token'] ??= bin2hex(random_bytes(32));
        tsnRespond(200, 'Token gerado.', ['csrf_token' => $_SESSION['tsn_csrf_token']]);
    }
    tsnRespond(405, 'Método não permitido.');
}

/* ─── CORS ──────────────────────────────────────────────────────────────── */

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, TSN_ALLOWED_ORIGINS, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}

/* ─── Honeypot ──────────────────────────────────────────────────────────── */

if (!empty($_POST['website'])) {
    tsnRespond(200, 'Testemunho recebido.');   // Silencioso para o bot
}

/* ─── Rate limiting por IP ──────────────────────────────────────────────── */

$ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$key = 'tsn_rate_' . hash('sha256', $ip);

if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = ['count' => 0, 'reset_at' => time() + TSN_RATE_LIMIT_WIN];
}

if (time() > $_SESSION[$key]['reset_at']) {
    $_SESSION[$key] = ['count' => 0, 'reset_at' => time() + TSN_RATE_LIMIT_WIN];
}

if ($_SESSION[$key]['count'] >= TSN_RATE_LIMIT_MAX) {
    tsnRespond(429, 'Muitos envios em pouco tempo. Aguarde alguns minutos e tente novamente.');
}

$_SESSION[$key]['count']++;

/* ─── Validação de inputs ───────────────────────────────────────────────── */

$nome   = tsnSanitizeStr($_POST['nome']   ?? '', 100);
$cidade = tsnSanitizeStr($_POST['cidade'] ?? '', 100);
$texto  = tsnSanitizeStr($_POST['texto']  ?? '', TSN_MAX_TEXTO_LEN);
$email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
$lgpd   = !empty($_POST['consentimento_lgpd']);
$csrfToken = (string) ($_POST['csrf_token'] ?? '');

if ($csrfToken === '' || !hash_equals((string) ($_SESSION['tsn_csrf_token'] ?? ''), $csrfToken)) {
    tsnRespond(403, 'Sessão expirada. Recarregue a página e tente novamente.');
}

if (mb_strlen($nome) < 2) {
    tsnRespond(422, 'Por favor, informe seu nome completo.');
}

if (mb_strlen($cidade) < 2) {
    tsnRespond(422, 'Por favor, informe sua cidade.');
}

if (mb_strlen($texto) < 30) {
    tsnRespond(422, 'Seu testemunho é muito curto. Por favor, escreva pelo menos algumas frases.');
}

if (!$lgpd) {
    tsnRespond(422, 'Você precisa autorizar a publicação do testemunho para continuar.');
}

/* ─── Salvar no CMS via API interna (se disponível) ────────────────────── */

$cmsApiUrl = getenv('CMS_API_URL') ?: '';
$cmsApiKey = getenv('CMS_API_KEY') ?: '';

$salvouNoCms = false;

if ($cmsApiUrl !== '' && $cmsApiKey !== '') {
    $payload = json_encode([
        'nome'               => $nome,
        'cidade'             => $cidade,
        'email'              => $email,
        'texto'              => $texto,
        'consentimento_lgpd' => true,
        'status'             => 'pendente',
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($cmsApiUrl . '/api/testemunhos');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $cmsApiKey,
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $salvouNoCms = ($httpCode >= 200 && $httpCode < 300);
}

/* ─── Fallback: notificação por e-mail ──────────────────────────────────── */

if (!$salvouNoCms) {
    $assunto  = '=?UTF-8?B?' . base64_encode('Novo testemunho — Paróquia NSR Jericó/PB') . '?=';
    $emailDe  = htmlspecialchars($email ?? 'nao-informado@invalid', ENT_QUOTES, 'UTF-8');
    $corpo    = "Novo testemunho recebido pelo site.\n\n"
              . "Nome:    {$nome}\n"
              . "Cidade:  {$cidade}\n"
              . "E-mail:  " . ($email ?? '(não informado)') . "\n"
              . "LGPD:    Autorizado\n"
              . "Status:  Pendente de aprovação\n\n"
              . "--- Texto ---\n{$texto}\n";

    $headers  = "From: " . TSN_EMAIL_FROM_NAME . " <" . TSN_EMAIL_FROM . ">\r\n";
    if ($email) {
        $headers .= "Reply-To: {$email}\r\n";
    }
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    @mail(TSN_EMAIL_TO, $assunto, $corpo, $headers);
}

/* ─── Resposta de sucesso ───────────────────────────────────────────────── */

tsnRespond(200, 'Testemunho recebido com sucesso. Obrigado por compartilhar sua fé!');
