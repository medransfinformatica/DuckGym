<?php
declare(strict_types=1);

/**
 * DuckGym - almacén compartido de clases.
 *
 * Las clases se guardan en api/data/classes.json para que un estudiante
 * pueda vincularse con el código desde cualquier dispositivo que acceda
 * a este mismo servidor (XAMPP/Apache).
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const MAX_CLASSES = 2000;
const MAX_EXERCISES = 50;

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$dataFile = $dataDir . DIRECTORY_SEPARATOR . 'classes.json';

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(int $status, string $error, string $message = ''): void
{
    respond($status, [
        'ok' => false,
        'error' => $error,
        'message' => $message !== '' ? $message : $error,
    ]);
}

function clean_text($value, int $maxLength): string
{
    $text = trim((string) $value);
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLength);
    }
    return substr($text, 0, $maxLength);
}

function normalize_code(string $value): string
{
    $code = strtoupper((string) preg_replace('/\s+/', '', trim($value)));
    if ($code === '') {
        return '';
    }
    if (preg_match('/^[A-Z0-9]{4}$/', $code)) {
        $code = 'DUCK-' . $code;
    } elseif (str_starts_with($code, 'DUCK') && !str_starts_with($code, 'DUCK-')) {
        $code = 'DUCK-' . substr($code, 4);
    }
    return $code;
}

function valid_code(string $code): bool
{
    return (bool) preg_match('/^DUCK-[A-Z0-9]{4}$/', $code);
}

function valid_teacher(string $teacher): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_.@-]{1,64}$/', $teacher);
}

function load_store(string $dataFile): array
{
    if (!is_file($dataFile)) {
        return [];
    }
    $handle = @fopen($dataFile, 'rb');
    if (!$handle) {
        return [];
    }
    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        return [];
    }
    $raw = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    $store = json_decode($raw !== false && $raw !== '' ? $raw : '{}', true);
    return is_array($store) ? $store : [];
}

function save_store(string $dataDir, string $dataFile, array $store): bool
{
    if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
        return false;
    }
    $handle = @fopen($dataFile, 'c+b');
    if (!$handle) {
        return false;
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }
    ftruncate($handle, 0);
    rewind($handle);
    $json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = $json !== false && fwrite($handle, $json) !== false;
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return $ok;
}

function generate_code(array $store): string
{
    for ($i = 0; $i < 500; $i++) {
        $code = 'DUCK-' . (string) random_int(1000, 9999);
        if (!isset($store[$code])) {
            return $code;
        }
    }

    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < 500; $i++) {
        $suffix = '';
        for ($j = 0; $j < 4; $j++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $code = 'DUCK-' . $suffix;
        if (!isset($store[$code])) {
            return $code;
        }
    }

    fail(500, 'code_generation_failed', 'No se pudo generar un código único. Inténtalo de nuevo.');
    return '';
}

function normalize_exercise($raw): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $name = clean_text($raw['name'] ?? '', 160);
    if ($name === '') {
        return null;
    }

    $exercise = [
        'name' => $name,
        'detail' => clean_text($raw['detail'] ?? 'A tu ritmo', 500),
        'gif' => clean_text($raw['gif'] ?? '', 1200),
        'desc' => clean_text($raw['desc'] ?? '', 1500),
    ];

    $duration = (int) ($raw['durationSec'] ?? 0);
    if ($duration > 0) {
        $exercise['durationSec'] = min($duration, 86400);
    }

    $distance = (float) ($raw['distanceKm'] ?? 0);
    if ($distance > 0) {
        $exercise['distanceKm'] = min($distance, 1000);
    }

    foreach (['targetPace' => 160, 'phase' => 80, 'sourceCategory' => 40, 'sourceName' => 160] as $field => $maxLength) {
        if (!empty($raw[$field])) {
            $exercise[$field] = clean_text($raw[$field], $maxLength);
        }
    }

    return $exercise;
}

function public_class(array $class): array
{
    $enrollments = isset($class['enrollments']) && is_array($class['enrollments'])
        ? $class['enrollments']
        : [];
    unset($class['enrollments']);
    $class['enrollmentCount'] = count($enrollments);
    return $class;
}

function find_class(array $store, string $code): ?array
{
    return isset($store[$code]) && is_array($store[$code]) ? $store[$code] : null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $store = load_store($dataFile);

    if (isset($_GET['health'])) {
        respond(200, [
            'ok' => true,
            'service' => 'duckgym-classes',
            'classes' => count($store),
            'writable' => is_writable($dataDir),
        ]);
    }

    if (isset($_GET['code'])) {
        $code = normalize_code((string) $_GET['code']);
        if (!valid_code($code)) {
            fail(400, 'invalid_code', 'El código debe tener el formato DUCK-XXXX.');
        }
        $class = find_class($store, $code);
        if (!$class) {
            fail(404, 'class_not_found', 'No encontramos ninguna clase con ese código.');
        }
        respond(200, ['ok' => true, 'class' => public_class($class)]);
    }

    if (isset($_GET['teacher'])) {
        $teacher = clean_text($_GET['teacher'], 64);
        if (!valid_teacher($teacher)) {
            fail(400, 'invalid_teacher', 'Profesor no válido.');
        }
        $classes = [];
        foreach ($store as $class) {
            if (is_array($class) && ($class['teacher'] ?? '') === $teacher) {
                $classes[] = public_class($class);
            }
        }
        usort($classes, static function (array $a, array $b): int {
            return strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? ''));
        });
        respond(200, ['ok' => true, 'classes' => $classes, 'count' => count($classes)]);
    }

    respond(200, [
        'ok' => true,
        'service' => 'duckgym-classes',
        'message' => 'API de clases de DuckGym',
    ]);
}

if ($method !== 'POST') {
    fail(405, 'method_not_allowed', 'Método no permitido.');
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody !== false && $rawBody !== '' ? $rawBody : '{}', true);
if (!is_array($input)) {
    fail(400, 'invalid_json', 'El cuerpo de la petición no es válido.');
}

$action = (string) ($_POST['action'] ?? $input['action'] ?? '');

if ($action === 'create') {
    $classInput = isset($input['class']) && is_array($input['class']) ? $input['class'] : $input;
    $teacher = clean_text($classInput['teacher'] ?? '', 64);
    if (!valid_teacher($teacher)) {
        fail(422, 'invalid_teacher', 'El profesor no es válido.');
    }

    $name = clean_text($classInput['name'] ?? '', 120);
    if ($name === '') {
        fail(422, 'invalid_name', 'La clase necesita un nombre.');
    }

    $rawExercises = $classInput['exercises'] ?? [];
    if (!is_array($rawExercises) || count($rawExercises) === 0) {
        fail(422, 'invalid_exercises', 'La clase necesita al menos un bloque.');
    }

    $exercises = [];
    foreach (array_slice($rawExercises, 0, MAX_EXERCISES) as $rawExercise) {
        $exercise = normalize_exercise($rawExercise);
        if ($exercise !== null) {
            $exercises[] = $exercise;
        }
    }
    if (count($exercises) === 0) {
        fail(422, 'invalid_exercises', 'Los bloques de la clase no son válidos.');
    }

    $store = load_store($dataFile);
    if (count($store) >= MAX_CLASSES) {
        fail(507, 'storage_full', 'No se pueden guardar más clases en este servidor.');
    }

    $requestedCode = normalize_code((string) ($classInput['code'] ?? ''));
    if ($requestedCode !== '' && !valid_code($requestedCode)) {
        $requestedCode = '';
    }

    $recoded = false;
    $code = $requestedCode !== '' ? $requestedCode : generate_code($store);
    $existing = find_class($store, $code);
    if ($existing !== null) {
        if (($existing['teacher'] ?? '') === $teacher) {
            respond(200, ['ok' => true, 'class' => public_class($existing), 'existing' => true]);
        }
        $code = generate_code($store);
        $recoded = true;
    }

    $class = [
        'code' => $code,
        'id' => 'class-' . strtolower($code),
        'teacher' => $teacher,
        'teacherName' => clean_text($classInput['teacherName'] ?? $teacher, 80),
        'name' => $name,
        'description' => clean_text($classInput['description'] ?? '', 1000),
        'level' => clean_text($classInput['level'] ?? 'Todos los niveles', 40),
        'emoji' => clean_text($classInput['emoji'] ?? '🎓', 8),
        'minutes' => max(1, min(600, (int) ($classInput['minutes'] ?? 1))),
        'createdAt' => clean_text($classInput['createdAt'] ?? gmdate('c'), 40),
        'exercises' => $exercises,
        'enrollments' => [],
    ];

    $store[$code] = $class;
    if (!save_store($dataDir, $dataFile, $store)) {
        fail(500, 'storage_error', 'No se pudo guardar la clase en el servidor.');
    }

    respond(201, [
        'ok' => true,
        'class' => public_class($class),
        'recoded' => $recoded,
        'requestedCode' => $requestedCode,
    ]);
}

if ($action === 'join') {
    $code = normalize_code((string) ($input['code'] ?? ''));
    if (!valid_code($code)) {
        fail(400, 'invalid_code', 'El código debe tener el formato DUCK-XXXX.');
    }

    $student = clean_text($input['student'] ?? '', 64);
    if ($student === '') {
        fail(422, 'invalid_student', 'Falta el identificador del estudiante.');
    }

    $store = load_store($dataFile);
    $class = find_class($store, $code);
    if ($class === null) {
        fail(404, 'class_not_found', 'No encontramos ninguna clase con ese código.');
    }

    if (!isset($class['enrollments']) || !is_array($class['enrollments'])) {
        $class['enrollments'] = [];
    }
    $class['enrollments'][$student] = [
        'name' => clean_text($input['studentName'] ?? $student, 80),
        'joinedAt' => gmdate('c'),
    ];
    $store[$code] = $class;

    if (!save_store($dataDir, $dataFile, $store)) {
        fail(500, 'storage_error', 'No se pudo guardar la vinculación en el servidor.');
    }

    respond(200, ['ok' => true, 'class' => public_class($class), 'joined' => true]);
}

if ($action === 'leave') {
    $code = normalize_code((string) ($input['code'] ?? ''));
    $student = clean_text($input['student'] ?? '', 64);
    if (!valid_code($code) || $student === '') {
        fail(422, 'invalid_request', 'Faltan datos para desvincularse.');
    }

    $store = load_store($dataFile);
    $class = find_class($store, $code);
    if ($class === null) {
        fail(404, 'class_not_found', 'No encontramos ninguna clase con ese código.');
    }

    if (isset($class['enrollments'][$student])) {
        unset($class['enrollments'][$student]);
        $store[$code] = $class;
        if (!save_store($dataDir, $dataFile, $store)) {
            fail(500, 'storage_error', 'No se pudo actualizar la vinculación.');
        }
    }

    respond(200, ['ok' => true, 'class' => public_class($class)]);
}

if ($action === 'delete') {
    $code = normalize_code((string) ($input['code'] ?? ''));
    $teacher = clean_text($input['teacher'] ?? '', 64);
    if (!valid_code($code) || !valid_teacher($teacher)) {
        fail(422, 'invalid_request', 'Faltan datos para eliminar la clase.');
    }

    $store = load_store($dataFile);
    $class = find_class($store, $code);
    if ($class === null) {
        fail(404, 'class_not_found', 'La clase ya no existe en el servidor.');
    }
    if (($class['teacher'] ?? '') !== $teacher) {
        fail(403, 'not_owner', 'Solo el profesor que creó la clase puede eliminarla.');
    }

    unset($store[$code]);
    if (!save_store($dataDir, $dataFile, $store)) {
        fail(500, 'storage_error', 'No se pudo eliminar la clase del servidor.');
    }

    respond(200, ['ok' => true, 'deleted' => $code]);
}

fail(400, 'unknown_action', 'Acción no reconocida.');
