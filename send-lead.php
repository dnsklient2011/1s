<?php
/**
 * Отправка заявки с лендинга веб-разработки на zakaz@1c-center.net
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Honeypot — боты заполняют скрытое поле
if (!empty($_POST['company_url'])) {
  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
  exit;
}

function field(string $key): string {
  $value = isset($_POST[$key]) ? (string) $_POST[$key] : '';
  $value = trim(preg_replace('/[\r\n]+/', ' ', $value));
  return mb_substr($value, 0, 2000);
}

$name = field('name');
$phone = field('phone');
$type = field('type');
$message = trim((string) ($_POST['message'] ?? ''));
$message = mb_substr($message, 0, 4000);

if ($name === '' || $phone === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Заполните имя и телефон'], JSON_UNESCAPED_UNICODE);
  exit;
}

$to = 'zakaz@1c-center.net';
$subject = 'Заявка: веб-разработка — ' . ($type !== '' ? $type : 'без типа');
$subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$body = "Новая заявка с лендинга веб-разработки\n"
  . "=====================================\n\n"
  . "Имя: {$name}\n"
  . "Телефон: {$phone}\n"
  . "Тип проекта: {$type}\n\n"
  . "Задача:\n"
  . ($message !== '' ? $message : '—') . "\n\n"
  . "=====================================\n"
  . 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '—') . "\n"
  . 'Страница: ' . ($_SERVER['HTTP_REFERER'] ?? '—') . "\n"
  . 'Время: ' . date('d.m.Y H:i:s') . "\n";

$headers = [
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'Content-Transfer-Encoding: 8bit',
  'From: 1C-Center Web <noreply@1c-center.net>',
  'Reply-To: zakaz@1c-center.net',
  'X-Mailer: 1c-center-web-lead',
];

$sent = @mail($to, $subjectEncoded, $body, implode("\r\n", $headers));

if ($sent) {
  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
  exit;
}

http_response_code(500);
echo json_encode([
  'ok' => false,
  'error' => 'Не удалось отправить письмо. Напишите на zakaz@1c-center.net или позвоните 8 800 555-62-77',
], JSON_UNESCAPED_UNICODE);
