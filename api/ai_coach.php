<?php
// api/ai_coach.php
// AI Academic Success Coach - Live OpenRouter API Integration

header('Content-Type: application/json; charset=UTF-8');

// Include system config
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// 1. OpenRouter Credentials & Configuration
$openRouterKey = defined('OPENROUTER_API_KEY') 
    ? trim(OPENROUTER_API_KEY) 
    : trim(getenv('OPENROUTER_API_KEY') ?: '');

$primaryModel = defined('OPENROUTER_MODEL') 
    ? OPENROUTER_MODEL 
    : (defined('OPENROUTER_DEFAULT_MODEL') ? OPENROUTER_DEFAULT_MODEL : 'minimax/minimax-m3.1-flash:free');

$fallbackModels = defined('OPENROUTER_FALLBACK_MODELS') && is_array(OPENROUTER_FALLBACK_MODELS)
    ? OPENROUTER_FALLBACK_MODELS
    : [
        'minimax/minimax-m3.1-flash:free',
        'google/gemma-4-26b-a4b-it:free',
        'nvidia/nemotron-3.5-lightning:free'
    ];

$modelChain = array_values(array_unique(array_merge([$primaryModel], $fallbackModels)));

$siteUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/university_system';
$siteTitle = defined('OPENROUTER_SITE_NAME') ? OPENROUTER_SITE_NAME : 'Student Academic Advisor';

// 2. Incoming Payload
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data && !empty($_POST)) {
    $data = $_POST;
}

if (!$data || empty($data['modules'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No module data provided. Please provide enrolled modules.'
    ]);
    exit;
}

// Check API key configuration
if (empty($openRouterKey)) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'AI Academic Success Coach is currently unavailable. Please try again later.'
    ]);
    exit;
}

$studentName = trim($data['studentName'] ?? 'Student');
$modules = $data['modules'];
$userQuestion = trim($data['question'] ?? ($data['message'] ?? ''));

// 3. Format Student Transcript Context
$promptData = "Student Name: {$studentName}\nEnrolled Modules & Quarterly Marks:\n";
$totalAvgSum = 0;
$avgCount = 0;

foreach ($modules as $m) {
    $code = trim($m['code'] ?? ($m['module_code'] ?? 'MOD'));
    $name = trim($m['name'] ?? ($m['module_name'] ?? 'Module'));
    $q1 = isset($m['q1']) && $m['q1'] !== null ? $m['q1'] . '%' : 'Pending';
    $q2 = isset($m['q2']) && $m['q2'] !== null ? $m['q2'] . '%' : 'Pending';
    $q3 = isset($m['q3']) && $m['q3'] !== null ? $m['q3'] . '%' : 'Pending';
    $q4 = isset($m['q4']) && $m['q4'] !== null ? $m['q4'] . '%' : 'Pending';
    $avgVal = isset($m['average']) && $m['average'] !== null ? (float)$m['average'] : null;
    $avg = $avgVal !== null ? number_format($avgVal, 1) . '%' : 'Pending';

    $promptData .= "- {$code} ({$name}): Q1={$q1}, Q2={$q2}, Q3={$q3}, Q4={$q4} | Final Average={$avg}\n";

    if ($avgVal !== null) {
        $totalAvgSum += $avgVal;
        $avgCount++;
    }
}

$overallAverage = $avgCount > 0 ? round($totalAvgSum / $avgCount, 1) : null;
if ($overallAverage !== null) {
    $promptData .= "Cumulative Running Average: {$overallAverage}%\n";
}

// 4. Construct AI System & User Messages
if (!empty($userQuestion)) {
    $systemContent = "You are the official AI Academic Success Coach for a university student portal.
Your mission is to provide concise, motivating, and highly practical coaching directly to the student based on their verified academic record.

Student Transcript:
{$promptData}

INSTRUCTIONS:
1. Provide a direct, intelligent, and practical coaching answer addressing the student's specific question.
2. Directly reference their actual modules, quarterly marks, and running average.
3. Keep the response motivating, concrete, professional, and under 400 words.
4. Do not use emojis.
5. Use clean markdown formatting (### for headers, bold for emphasis, bullet points).
6. Conclude with '### Action Plan' listing immediate, practical next steps.";

    $userContent = $userQuestion;
} else {
    $systemContent = "You are the official AI Academic Success Coach for a university student portal.
Analyze the student performance transcript and provide a structured, encouraging performance diagnosis.

INSTRUCTIONS:
Structure your response using these exact markdown headers:
### Performance Overview
Summarize running average, quarter-over-quarter progress, and overall momentum.
### Priority Modules & Targets
Identify lowest-performing modules, noting specific assessment targets to secure pass or distinction.
### Recommended Study Techniques
Provide 3 high-impact learning strategies tailored to their technical coursework (e.g. active recall, spaced repetition, timed past papers).
### Action Plan
A structured 2-week roadmap with clear weekly goals.

RULES:
- Reference actual module codes, names, and percentage marks.
- Maintain an encouraging, professional, institutional tone.
- Do not use emojis.
- Keep the response direct, high-impact, and under 450 words.";

    $userContent = "Please diagnose my quarterly academic performance transcript and provide my success roadmap:\n\n" . $promptData;
}

// 5. Construct conversation message payload
$chatHistory = isset($data['history']) && is_array($data['history']) ? $data['history'] : [];
$messagesList = [
    ['role' => 'system', 'content' => $systemContent]
];

if (!empty($chatHistory)) {
    // Include last 6 turns for conversational context
    $recent = array_slice($chatHistory, -6);
    foreach ($recent as $h) {
        $r = (isset($h['role']) && $h['role'] === 'assistant') ? 'assistant' : 'user';
        $c = trim((string)($h['content'] ?? ''));
        if ($c !== '') {
            $messagesList[] = ['role' => $r, 'content' => $c];
        }
    }
}

$lastMsg = end($messagesList);
if (!empty($userContent) && (!$lastMsg || $lastMsg['content'] !== $userContent || $lastMsg['role'] !== 'user')) {
    $messagesList[] = ['role' => 'user', 'content' => $userContent];
}

// 6. Real OpenRouter API Execution
$aiContent = null;
$usedModel = null;

$openRouterHeaders = [
    'Authorization: Bearer ' . $openRouterKey,
    'Content-Type: application/json',
    'HTTP-Referer: ' . $siteUrl,
    'X-Title: ' . $siteTitle
];

// Try models in sequence
foreach ($modelChain as $candidateModel) {
    $payload = [
        'model' => $candidateModel,
        'messages' => $messagesList,
        'temperature' => 0.4,
        'max_tokens' => 2500
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $openRouterHeaders);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    unset($ch);

    if (!$curlErr && $httpCode >= 200 && $httpCode < 300 && !empty($raw)) {
        $resp = json_decode($raw, true);
        $message = $resp['choices'][0]['message'] ?? [];
        $text = trim((string)($message['content'] ?? ''));

        // If reasoning model returned text in reasoning field
        if (empty($text) && !empty($message['reasoning'])) {
            $text = trim((string)$message['reasoning']);
        }

        if (!empty($text)) {
            $aiContent = $text;
            $usedModel = $resp['model'] ?? $candidateModel;
            break;
        }
    }
}

// 6. Return Response or Single Standard Error Message
if (!empty($aiContent)) {
    echo json_encode([
        'success' => true,
        'advice' => $aiContent,
        'model' => $usedModel,
        'source' => 'OpenRouter AI'
    ]);
    exit;
}

// If real API failed or is unavailable, return single standard error message (NO hardcoding)
http_response_code(503);
echo json_encode([
    'success' => false,
    'error' => 'AI Academic Success Coach is currently unavailable. Please try again later.'
]);
exit;
