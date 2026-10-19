<?php
// api/ai_coach.php
// AI Academic Coach Backend Relay Script for OpenRouter API
// Unified, fast endpoint for both One-Click Diagnostic & Interactive Study Inquiries

header('Content-Type: application/json; charset=UTF-8');

// Include system config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// 1. OpenRouter Configuration
$apiKey = defined('OPENROUTER_API_KEY') 
    ? OPENROUTER_API_KEY 
    : (getenv('OPENROUTER_API_KEY') ?: '');

$defaultModel = defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'openrouter/free';
$model = getenv('OPENROUTER_MODEL') ?: $defaultModel;

$siteUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/university_system';
$siteTitle = defined('OPENROUTER_SITE_NAME') ? OPENROUTER_SITE_NAME : 'Student Academic Advisor';

// 2. Grab incoming student marks payload
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

$studentName = $data['studentName'] ?? 'Student';
$modules = $data['modules'];
$userQuestion = trim($data['question'] ?? ($data['message'] ?? ''));

// 3. Construct structured prompt data
$promptData = "Student Name: {$studentName}\nQuarterly Performance:\n";
$lowestModule = null;
$highestModule = null;

foreach ($modules as $m) {
    $code = $m['code'] ?? ($m['module_code'] ?? 'MOD');
    $name = $m['name'] ?? ($m['module_name'] ?? 'Module');
    $q1 = isset($m['q1']) && $m['q1'] !== null ? $m['q1'] . '%' : 'Pending';
    $q2 = isset($m['q2']) && $m['q2'] !== null ? $m['q2'] . '%' : 'Pending';
    $q3 = isset($m['q3']) && $m['q3'] !== null ? $m['q3'] . '%' : 'Pending';
    $q4 = isset($m['q4']) && $m['q4'] !== null ? $m['q4'] . '%' : 'Pending';
    $avgVal = isset($m['average']) && $m['average'] !== null ? (float)$m['average'] : null;
    $avg = $avgVal !== null ? $avgVal . '%' : 'Pending';

    $promptData .= "- {$code} ({$name}): Q1={$q1}, Q2={$q2}, Q3={$q3}, Q4={$q4}, Final Average={$avg}\n";

    if ($avgVal !== null) {
        if ($lowestModule === null || $avgVal < $lowestModule['avg']) {
            $lowestModule = ['code' => $code, 'name' => $name, 'avg' => $avgVal];
        }
        if ($highestModule === null || $avgVal > $highestModule['avg']) {
            $highestModule = ['code' => $code, 'name' => $name, 'avg' => $avgVal];
        }
    }
}

// Check whether this is a specific user question or a full diagnostic
if (!empty($userQuestion)) {
    $systemContent = "You are an official academic advisor and study coach for a university student portal.
The student has the following quarterly module performance:
{$promptData}

Answer the student's question with actionable, high-impact study advice based on their transcript.
STRICT INSTITUTIONAL DESIGN RULES:
1. Maintain a professional, encouraging, academic tone suitable for an official university transcript portal.
2. DO NOT use emojis (no 💡, 🌟, 🎯, 🚀, etc.). All iconography is handled natively by the UI.
3. Use markdown headers (###) to separate distinct sections.
4. Use bolding (**Title/Term**) for book titles, core concepts, and module codes.
5. Use clean bullet points (- ) for lists.
6. Conclude with a clear 'Action Plan:' summarizing immediate study steps.
7. SCOPE & BOUNDARY RULES:
- IDENTITY: If asked 'who are you' or 'what is this', identify yourself as the official AI Academic Success Coach for this student portal, equipped with their current course transcript.
- OFF-TOPIC QUERIES: If the student asks something outside academic advising (e.g., sports like 'i love football', casual chatter, hobbies), politely acknowledge it in 1 sentence (e.g., noting how sports/hobbies support healthy cognitive balance), and immediately pivot back to asking how you can support their modules, exam prep, or study schedule.
- Maintain an encouraging, academic focus at all times without acting as an open-ended entertainment chatbot.
Keep your response concise, structured, and easy to scan.";
    $userContent = $userQuestion;
} else {
    $systemContent = "You are an official academic advisor for a university student portal.
Analyze the student's quarterly marks.
STRICT INSTITUTIONAL DESIGN RULES:
1. Maintain a professional, encouraging, formal academic tone.
2. DO NOT use emojis (no 💡, 🌟, 📊, 🚨, etc.).
3. Provide a structured diagnostic breakdown using clean markdown:
### 1. Overall Performance Trend
State whether performance is improving, declining, or consistent across quarters.
### 2. At-Risk & Priority Modules
Identify any module with marks/average below 50% or declining quarterly trends.
### 3. Strongest Performing Modules
Acknowledge high achievements and distinction levels (75%+).
### 4. Actionable Study Recommendations
Provide 3-4 concrete academic techniques (e.g., Active Recall, Time-Blocking, Exam Paper Drills).
### 5. Action Plan
A 1-2 week actionable timetable/roadmap.
Use clean markdown bullet points without emojis.";
    $userContent = $promptData;
}

$payload = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => $systemContent],
        ['role' => 'user', 'content' => $userContent]
    ],
    'temperature' => 0.4,
    'max_tokens' => 900
];

// Helper for local algorithmic fallback if OpenRouter is slow or unavailable
function generateFastLocalFallback($name, $modules, $lowest, $highest, $question) {
    if (!empty($question)) {
        $lowCode = $lowest ? $lowest['code'] : 'your priority modules';
        $qLower = strtolower($question);

        // 1. Identity & Role Inquiries
        if (preg_match('/(who are you|who r u|what are you|what is this|your name|introduce yourself)/i', $qLower)) {
            return "### Official Academic Success Coach\n\n"
                 . "I am your official **AI Academic Success Coach** integrated into the student portal.\n\n"
                 . "- **My Scope:** I assist **{$name}** by analyzing your registered module marks, diagnosing quarterly trends, and providing study techniques.\n"
                 . "- **Current Transcript Context:** I have direct access to your enrolled module performance, including **{$lowCode}**.\n"
                 . "- **Capabilities:** Ask me for a customized study schedule, exam preparation steps, or active recall methods.\n\n"
                 . "Action Plan: Inquire about revision strategies for your modules or click any quick coaching topic above.";
        }

        // 2. Off-Topic / Casual / Sports Inquiries
        if (preg_match('/(football|soccer|rugby|sport|game|movie|music|party|club|play)/i', $qLower)) {
            return "### Academic Coach Guidance: **{$name}**\n\n"
                 . "Engaging in sports and personal hobbies like football is beneficial for mental wellness, physical endurance, and stress management during the academic semester.\n\n"
                 . "- **Healthy Work-Life Balance:** Use sports and recreation as structured rewards after completing your daily study goals.\n"
                 . "- **Focus Preservation:** Keep your study sessions (e.g. for **{$lowCode}**) isolated from distractions so you get the full benefit of both your studies and your downtime.\n\n"
                 . "Action Plan: Finish your designated revision block for {$lowCode} before recreational activities to maintain peak focus.";
        }

        return "### Academic Coaching Guidance: **{$name}**\n\n"
             . "Regarding your inquiry: *\"" . htmlspecialchars($question) . "\"*\n\n"
             . "- **Active Retrieval & Problem Solving:** Allocate 45 minutes daily to solving practice tutorial questions for **{$lowCode}** from memory before reviewing notes.\n"
             . "- **Pomodoro Focused Revision:** Study in 25-minute concentrated cycles followed by 5-minute cognitive breaks.\n"
             . "- **Lecturer Consultation:** Compile 2-3 specific conceptual questions you struggled with in Q1/Q2 assessments to review during lecturer consultation hours.\n\n"
             . "Action Plan: Implement a 45-minute daily focus block for {$lowCode} and complete weekly timed self-assessments.";
    }

    $out = "### Academic Performance Diagnostic Report: **{$name}**\n\n";
    $out .= "#### 1. Overall Performance Trend\n";
    $out .= "- Cumulative standing demonstrates consistent academic engagement across quarterly assessment cycles.\n\n";
    
    $out .= "#### 2. At-Risk & Priority Modules\n";
    if ($lowest && $lowest['avg'] < 50) {
        $out .= "- **{$lowest['code']} ({$lowest['name']}):** Critical attention required (Current Average: {$lowest['avg']}%). Immediate daily revision and educator consultation recommended.\n\n";
    } elseif ($lowest && $lowest['avg'] < 65) {
        $out .= "- **{$lowest['code']} ({$lowest['name']}):** Moderate risk (Current Average: {$lowest['avg']}%). Additional tutorial and coursework reinforcement recommended.\n\n";
    } else {
        $out .= "- All registered modules currently meet or exceed the 65% benchmark.\n\n";
    }

    $out .= "#### 3. Strongest Performing Modules\n";
    if ($highest) {
        $out .= "- **{$highest['code']} ({$highest['name']}):** Commendable achievement with a current average of **{$highest['avg']}%**.\n\n";
    }

    $out .= "#### 4. Actionable Study Recommendations\n";
    $targetMod = $lowest ? $lowest['code'] : 'priority modules';
    $out .= "- **Active Recall:** For `{$targetMod}`, summarize each syllabus topic from memory on paper after lecture sessions.\n";
    $out .= "- **Time Blocking:** Schedule two 90-minute revision blocks per week dedicated strictly to problem sets.\n";
    $out .= "- **Timed Assessments:** Practice past examination papers under timed conditions to refine exam pace and question familiarity.\n\n";

    $out .= "Action Plan: Commit to structured weekly study blocks and schedule a consultation with your educator before the upcoming quarterly assessments.";
    
    return $out;
}

// 4. Send request to OpenRouter via cURL with a 15-second timeout
$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Fast timeout to prevent long hangs
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . trim($apiKey),
    'Content-Type: application/json',
    'HTTP-Referer: ' . $siteUrl,
    'X-Title: ' . $siteTitle
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch); // Automatic cleanup (curl_close is deprecated in PHP 8.5+)

// If OpenRouter succeeded
if (!$curlError && $httpCode === 200) {
    $result = json_decode($response, true);
    $aiReply = $result['choices'][0]['message']['content'] ?? null;
    if (!empty($aiReply)) {
        echo json_encode([
            'success' => true,
            'advice' => $aiReply,
            'model' => $result['model'] ?? $model,
            'source' => 'OpenRouter AI'
        ]);
        exit;
    }
}

// If OpenRouter failed or timed out, provide instant, high-quality institutional heuristic answer
$fallback = generateFastLocalFallback($studentName, $modules, $lowestModule, $highestModule, $userQuestion);
echo json_encode([
    'success' => true,
    'advice' => $fallback,
    'model' => 'Institutional Advisor Heuristic',
    'source' => 'Institutional Advisor (Instant Mode)'
]);
exit;
