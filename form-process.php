<?php
/**
 * form-process.php
 * Endpoint de processamento do formulário de contato — Paróquia NSR Jericó/PB
 *
 * Mitigações aplicadas:
 *  - CSRF token (sessão)
 *  - Honeypot (anti-bot)
 *  - Rate limiting por IP (sessão)
 *  - Sanitização e validação de inputs (filter_var)
 *  - Bloqueio de header injection (rejeita CR/LF e cabeçalhos suspeitos no email)
 *  - From: do domínio próprio + Reply-To: do remetente
 *  - Mensagens em PT-BR
 *  - Resposta JSON estruturada
 *  - Suporte opcional a reCAPTCHA v3 via env vars RECAPTCHA_SECRET / RECAPTCHA_MIN_SCORE
 *
 * Pendente (próximas iterações):
 *  - PHPMailer/SMTP autenticado em vez de mail() — recomendado em produção
 *  - Logging persistente (banco de dados)
 *
 * @see MELHORIAS_GERAIS.md §4 (Plano de segurança)
 */

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

/* ---------------------- CONFIGURAÇÃO ---------------------- */

// Permite sobrescrever via variáveis de ambiente (.env / Plesk env vars).
// Em produção, o ideal é definir EMAIL_TO/EMAIL_FROM no painel sem hardcoded.
define('EMAIL_TO',        getenv('EMAIL_TO')        ?: 'contato@pascomjerico.com.br');
define('EMAIL_FROM',      getenv('EMAIL_FROM')      ?: 'nao-responda@pascomjerico.com.br'); // ⚠️ deve estar no SPF/DKIM
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: 'Site Paróquia NSR Jericó/PB');
const SUBJECT         = 'Novo contato pelo site — Paróquia NSR Jericó/PB';
const MAX_BODY_LEN    = 5000;
const RATE_LIMIT_MAX  = 3;
const RATE_LIMIT_WIN  = 600;        // 10 min
const ALLOWED_ORIGINS = [
    'https://pascomjerico.com.br',
    'https://www.pascomjerico.com.br',
    'https://kelvincustodio11.github.io', // GitHub Pages (preview p/ equipe)
];

/* ---------------------- HELPERS --------------------------- */

function respond(int $status, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $status < 400, 'mensagem' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function rejectIfHeaderInjection(string $value): void
{
    if (
        preg_match('/[\r\n]/', $value)
        || stripos($value, 'bcc:') !== false
        || stripos($value, 'cc:') !== false
        || stripos($value, 'content-type:') !== false
    ) {
        respond(400, 'Conteúdo inválido detectado.');
    }
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* ---------------------- VALIDAÇÕES INICIAIS --------------- */

// Endpoint GET: devolve um CSRF token (consumido pelo contact.html estático).
// O cookie de sessão setado aqui é reutilizado no POST de envio.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'token') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode(['csrf_token' => $_SESSION['csrf_token']], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Método não permitido.');
}

// Origin / Referer (defesa adicional contra CSRF)
$origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$validOrigin = false;
foreach (ALLOWED_ORIGINS as $allowed) {
    if ($origin === $allowed || str_starts_with($referer, $allowed . '/')) {
        $validOrigin = true;
        break;
    }
}
if (!$validOrigin && !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)) {
    respond(403, 'Origem não autorizada.');
}

/* ---------------------- CSRF TOKEN ------------------------ */

$tokenEnviado = (string) ($_POST['csrf_token'] ?? '');
$tokenSessao  = (string) ($_SESSION['csrf_token'] ?? '');
if ($tokenSessao === '' || !hash_equals($tokenSessao, $tokenEnviado)) {
    respond(403, 'Sessão expirada. Recarregue a página e tente novamente.');
}

/* ---------------------- HONEYPOT -------------------------- */

if (!empty($_POST['hp_field'] ?? '')) {
    // Bot: responder OK silenciosamente
    respond(200, 'Mensagem recebida.');
}

/* ---------------------- RATE LIMITING --------------------- */

$_SESSION['rate'] ??= [];
$now = time();
$_SESSION['rate'] = array_filter($_SESSION['rate'], fn($t) => $t > $now - RATE_LIMIT_WIN);
if (count($_SESSION['rate']) >= RATE_LIMIT_MAX) {
    respond(429, 'Muitas tentativas. Tente novamente em alguns minutos.');
}
$_SESSION['rate'][] = $now;

/* ---------------------- (OPCIONAL) reCAPTCHA v3 ----------- */

$recaptchaSecret = getenv('RECAPTCHA_SECRET') ?: '';
if ($recaptchaSecret !== '') {
    $minScore = (float) (getenv('RECAPTCHA_MIN_SCORE') ?: '0.5');
    $token    = (string) ($_POST['recaptcha_token'] ?? '');
    if ($token === '') {
        respond(400, 'Falha na verificação anti-spam.');
    }
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret'   => $recaptchaSecret,
                'response' => $token,
                'remoteip' => clientIp(),
            ]),
            'timeout' => 5,
        ],
    ]);
    $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    $data = $resp ? json_decode($resp, true) : null;
    if (!is_array($data) || empty($data['success']) || ($data['score'] ?? 0) < $minScore) {
        respond(400, 'Falha na verificação anti-spam.');
    }
}

/* ---------------------- SANITIZAÇÃO + VALIDAÇÃO ----------- */

$fname   = trim((string) ($_POST['fname']   ?? ''));
$lname   = trim((string) ($_POST['lname']   ?? ''));
$email   = trim((string) ($_POST['email']   ?? ''));
$phone   = trim((string) ($_POST['phone']   ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

$erros = [];
if ($fname === '' || mb_strlen($fname) > 80) {
    $erros[] = 'Nome é obrigatório (até 80 caracteres).';
}
if ($lname === '' || mb_strlen($lname) > 80) {
    $erros[] = 'Sobrenome é obrigatório (até 80 caracteres).';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    $erros[] = 'E-mail inválido.';
}
if ($phone === '' || !preg_match('/^[0-9 ()+\-]{8,20}$/', $phone)) {
    $erros[] = 'Telefone inválido (use apenas números, espaços, parênteses, + e -).';
}
if ($message === '' || mb_strlen($message) > MAX_BODY_LEN) {
    $erros[] = 'Mensagem é obrigatória (até ' . MAX_BODY_LEN . ' caracteres).';
}

if ($erros) {
    respond(422, implode(' ', $erros), ['erros' => $erros]);
}

// Bloquear header injection no email/nome
rejectIfHeaderInjection($email);
rejectIfHeaderInjection($fname);
rejectIfHeaderInjection($lname);

// Sanitizar para o corpo do email (texto puro)
$cleanFname   = htmlspecialchars(strip_tags($fname),   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$cleanLname   = htmlspecialchars(strip_tags($lname),   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$cleanPhone   = htmlspecialchars(strip_tags($phone),   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$cleanMessage = htmlspecialchars(strip_tags($message), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

/* ---------------------- MONTAGEM DO EMAIL ----------------- */

$ip = clientIp();

$body  = "Novo contato recebido pelo site da Paróquia NSR Jericó/PB\r\n";
$body .= str_repeat('-', 60) . "\r\n";
$body .= "Nome:      {$cleanFname} {$cleanLname}\r\n";
$body .= "E-mail:    {$email}\r\n";
$body .= "Telefone:  {$cleanPhone}\r\n";
$body .= "IP:        {$ip}\r\n";
$body .= "Data:      " . date('d/m/Y H:i:s') . "\r\n";
$body .= str_repeat('-', 60) . "\r\n";
$body .= "Mensagem:\r\n{$cleanMessage}\r\n";

$headers  = 'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . ">\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

/* ---------------------- ENVIO ----------------------------- */

$subjectEncoded = '=?UTF-8?B?' . base64_encode(SUBJECT) . '?=';
$enviado = @mail(EMAIL_TO, $subjectEncoded, $body, $headers, '-f' . EMAIL_FROM);

if (!$enviado) {
    respond(500, 'Não foi possível enviar agora. Tente novamente em alguns instantes ou nos chame no WhatsApp.');
}

// Renovar token CSRF para evitar replay
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

respond(200, 'Mensagem enviada com sucesso! Em breve entraremos em contato.');
