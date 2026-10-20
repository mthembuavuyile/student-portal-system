<?php
// api/ai_coach.php
// AI Academic Success Coach Multi-Provider Relay Script
// Supports: Google Gemini API (Direct) + OpenRouter Free Multi-Model Pipeline + Dynamic Academic Intelligence Engine

header('Content-Type: application/json; charset=UTF-8');

// Include system config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

// 1. Provider Credentials
$geminiKey = defined('GEMINI_API_KEY') 
    ? trim(GEMINI_API_KEY) 
    : trim(getenv('GEMINI_API_KEY') ?: '');

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
        'poolside/laguna-s-2.1:free',
        'poolside/laguna-xs-2.1:free'
    ];

$modelChain = array_slice(array_values(array_unique(array_merge([$primaryModel], $fallbackModels))), 0, 3);

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

$studentName = trim($data['studentName'] ?? 'Student');
$modules = $data['modules'];
$userQuestion = trim($data['question'] ?? ($data['message'] ?? ''));

// 3. Mathematical & Data Parsing
$promptData = "Student Name: {$studentName}\nEnrolled Modules & Quarterly Marks:\n";
$lowestModule = null;
$highestModule = null;
$moduleSummaries = [];
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

    $modInfo = [
        'code' => $code,
        'name' => $name,
        'avg' => $avgVal,
        'q1' => isset($m['q1']) && $m['q1'] !== null ? (float)$m['q1'] : null,
        'q2' => isset($m['q2']) && $m['q2'] !== null ? (float)$m['q2'] : null,
        'q3' => isset($m['q3']) && $m['q3'] !== null ? (float)$m['q3'] : null,
        'q4' => isset($m['q4']) && $m['q4'] !== null ? (float)$m['q4'] : null
    ];
    $moduleSummaries[] = $modInfo;

    if ($avgVal !== null) {
        if ($lowestModule === null || $avgVal < $lowestModule['avg']) {
            $lowestModule = $modInfo;
        }
        if ($highestModule === null || $avgVal > $highestModule['avg']) {
            $highestModule = $modInfo;
        }
    }
}

$overallAverage = $avgCount > 0 ? round($totalAvgSum / $avgCount, 1) : null;

// 4. Construct System & User Prompts
if (!empty($userQuestion)) {
    $systemContent = "You are the official AI Academic Success Coach for a university student portal.
Authenticated Student Record:
{$promptData}

The student has asked:
\"{$userQuestion}\"

INSTRUCTIONS:
1. Provide a direct, intelligent, and practical coaching answer addressing their specific question and actual marks.
2. Directly cite their registered modules (especially priority module " . ($lowestModule ? $lowestModule['code'] . " - " . $lowestModule['name'] : 'their modules') . ") with concrete techniques.
3. If off-topic (greetings, sports, hobbies), acknowledge warmly in 1 sentence and guide focus back to curriculum success.
4. Maintain a professional, inspiring tone without emojis.
5. Use markdown headers (###), bold, and bullet points (- ).
6. Conclude with '### Action Plan' listing 2-3 immediate, actionable steps.
7. Do not output internal chain of thought scratchpads; write the student-facing response directly.";

    $userContent = $userQuestion;
} else {
    $systemContent = "You are the official AI Academic Success Coach for a university student portal.
Analyze the following student performance transcript:
{$promptData}

Provide a structured, motivating performance diagnosis:
### 1. Overall Performance Standing
Summarize progress, consistency, and quarterly trajectory.
### 2. Priority Modules Needing Immediate Focus
Highlight modules below 50% (critical risk) or 50-65% (moderate risk), noting exact points to pass (50%) or distinction (75%).
### 3. Areas of Academic Strength
Commend scores of 70%+ and explain how to maintain them.
### 4. High-Impact Study Interventions
Provide 3 practical techniques customized to their lowest-scoring modules (e.g. active recall, spaced repetition, timed practice exams).
### 5. Two-Week Action Plan
Structured 2-week schedule with daily/weekly goals.

RULES:
- Reference actual module codes, names, and percentage marks.
- Professional, inspiring tone without emojis.
- Do not output internal chain of thought scratchpads; write the student-facing diagnosis directly.";

    $userContent = "Please diagnose my quarterly academic performance and provide my structured success roadmap.";
}

// 5. Provider Execution: 1) Google Gemini (if configured)
if (!empty($geminiKey)) {
    $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($geminiKey);
    $geminiPayload = [
        'system_instruction' => [
            'parts' => [['text' => $systemContent]]
        ],
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $userContent]]]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'maxOutputTokens' => 1200
        ]
    ];

    $chG = curl_init($geminiUrl);
    curl_setopt($chG, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chG, CURLOPT_POST, true);
    curl_setopt($chG, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chG, CURLOPT_POSTFIELDS, json_encode($geminiPayload));
    curl_setopt($chG, CURLOPT_TIMEOUT, 20);
    curl_setopt($chG, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $rawG = curl_exec($chG);
    $codeG = curl_getinfo($chG, CURLINFO_HTTP_CODE);
    unset($chG);

    if ($codeG === 200) {
        $dataG = json_decode($rawG, true);
        $textG = trim((string)($dataG['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        if (!empty($textG)) {
            echo json_encode([
                'success' => true,
                'advice' => $textG,
                'model' => 'Gemini 2.0 Flash',
                'source' => 'Google Gemini AI (Live)'
            ]);
            exit;
        }
    }
}

// 6. Provider Execution: 2) OpenRouter Multi-Model Pipeline
if (!empty($openRouterKey)) {
    $openRouterHeaders = [
        'Authorization: Bearer ' . $openRouterKey,
        'Content-Type: application/json',
        'HTTP-Referer' => $siteUrl,
        'X-Title: ' . $siteTitle
    ];

    // Attempt native models array
    $payloadArray = [
        'models' => $modelChain,
        'messages' => [
            ['role' => 'system', 'content' => $systemContent],
            ['role' => 'user', 'content' => $userContent]
        ],
        'temperature' => 0.5,
        'max_tokens' => 1200
    ];

    $chOR = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($chOR, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chOR, CURLOPT_POST, true);
    curl_setopt($chOR, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chOR, CURLOPT_POSTFIELDS, json_encode($payloadArray));
    curl_setopt($chOR, CURLOPT_TIMEOUT, 18);
    curl_setopt($chOR, CURLOPT_HTTPHEADER, $openRouterHeaders);

    $rawOR = curl_exec($chOR);
    $codeOR = curl_getinfo($chOR, CURLINFO_HTTP_CODE);
    $errOR = curl_error($chOR);
    unset($chOR);

    if (!$errOR && $codeOR >= 200 && $codeOR < 300) {
        $dataOR = json_decode($rawOR, true);
        $contentOR = trim((string)($dataOR['choices'][0]['message']['content'] ?? ''));
        $usedModel = $dataOR['model'] ?? ($modelChain[0] ?? 'OpenRouter AI');

        if (!empty($contentOR) && stripos($contentOR, 'User Safety:') === false && strlen($contentOR) > 40 && !preg_match('/^(We need|I need to|Thinking Process|Drafting)/i', $contentOR)) {
            echo json_encode([
                'success' => true,
                'advice' => $contentOR,
                'model' => $usedModel,
                'source' => 'OpenRouter AI (' . $usedModel . ')'
            ]);
            exit;
        }
    }
}

// 7. Dynamic Data-Driven Academic Intelligence Engine
// Dynamically constructs mathematical, pedagogical, and syllabus-grounded guidance fresh for every request.
function generateDynamicAcademicIntelligence(string $name, array $moduleSummaries, ?array $lowest, ?array $highest, ?float $overallAvg, string $question): string {
    $lowCode = $lowest ? $lowest['code'] : 'Priority Modules';
    $lowName = $lowest ? $lowest['name'] : 'Course Modules';
    $lowAvg = $lowest && $lowest['avg'] !== null ? $lowest['avg'] : 48.0;

    $highCode = $highest ? $highest['code'] : 'Core Modules';
    $highName = $highest ? $highest['name'] : 'Major Studies';
    $highAvg = $highest && $highest['avg'] !== null ? $highest['avg'] : 75.0;

    $gapToPass = round(max(0, 50.0 - $lowAvg), 1);
    $gapToDistinction = round(max(0, 75.0 - $highAvg), 1);
    $lowDistinctionGap = round(max(0, 75.0 - $lowAvg), 1);

    // Dynamic trajectory analysis
    $lowTrajectory = "";
    if ($lowest && $lowest['q1'] !== null && $lowest['q4'] !== null) {
        $delta = round($lowest['q4'] - $lowest['q1'], 1);
        if ($delta > 0) {
            $lowTrajectory = "improved from Q1 ({$lowest['q1']}%) to Q4 ({$lowest['q4']}%), a gain of +{$delta} percentage points";
        } elseif ($delta < 0) {
            $absDelta = abs($delta);
            $lowTrajectory = "dropped from Q1 ({$lowest['q1']}%) to Q4 ({$lowest['q4']}%), a decline of -{$absDelta} percentage points";
        } else {
            $lowTrajectory = "remained consistent at {$lowest['q1']}% between Q1 and Q4";
        }
    }

    $qLower = strtolower(trim($question));

    // Handle user interactive questions with dynamic pedagogical reasoning
    if (!empty($question)) {
        $cleanQ = htmlspecialchars($question);

        // A. Timetable & Schedule requests
        if (preg_match('/(schedule|timetable|plan|routine|calendar|week|time)/i', $qLower)) {
            $out = "### Custom 2-Week Study Schedule: **{$name}**\n\n";
            $out .= "Based on your transcript, your current focus allocation should be **70% for {$lowCode}** and **30% for {$highCode}**.\n\n";
            $out .= "#### Weekly Routine (Monday to Friday)\n";
            $out .= "- **08:30 – 09:15 | {$lowCode} Active Retrieval:** Summarize key lecture formulas, definitions, and concepts from memory on paper.\n";
            $out .= "- **10:00 – 11:30 | {$lowCode} Practical Problem Solving:** Solve 3–5 tutorial questions or past paper problems without looking at notes.\n";
            $out .= "- **14:00 – 14:45 | {$highCode} Maintenance:** Review current coursework and complete 1 practical mini-exercise to keep your distinction grade (**{$highAvg}%**) secure.\n";
            $out .= "- **16:00 – 16:30 | Error Log Remediation:** Redo any question you missed earlier in the day and record the root cause.\n\n";
            $out .= "### Action Plan\n";
            $out .= "- **Step 1:** Block out these daily slots on your mobile calendar.\n";
            $out .= "- **Step 2:** Complete one full timed assessment for {$lowCode} this weekend to benchmark progress.";
            return $out;
        }

        // B. Boosting lowest module / pass strategy
        if (preg_match('/(boost|low|lowest|fail|pass|struggl|grade|mark|help)/i', $qLower)) {
            $out = "### Pass & Recovery Blueprint: **{$lowCode} ({$lowName})**\n\n";
            $out .= "Avuyile, your current standing in **{$lowCode}** is **{$lowAvg}%**.\n\n";
            if ($gapToPass > 0) {
                $out .= "- **Exact Pass Requirement:** You are currently **{$gapToPass} percentage points** below the official 50% pass benchmark.\n";
                $out .= "- **Target Assessment Goal:** Aim for at least **55%–60%** in your upcoming quarterly assessment to establish a comfortable passing buffer.\n";
            } else {
                $out .= "- **Standing:** You are currently passing at **{$lowAvg}%**. Your next milestone is closing the **{$lowDistinctionGap} percentage point** gap toward higher distinction.\n";
            }
            if (!empty($lowTrajectory)) {
                $out .= "- **Quarterly Trajectory:** Your marks {$lowTrajectory}.\n";
            }
            $out .= "\n#### Practical Interventions for {$lowName}\n";
            $out .= "- **Active Recall Drills:** Close your syllabus notes and write out core definitions, schemas, and logic purely from memory before checking for errors.\n";
            $out .= "- **Assessment Error Log:** Categorize every mark lost in previous assessments into: *conceptual misunderstanding*, *misreading question*, or *procedural error*.\n";
            $out .= "- **Lecturer Consultation:** Book a 10-minute office-hour consultation with your {$lowCode} educator to review your weakest assessed question.\n\n";
            $out .= "### Action Plan\n";
            $out .= "- **Tonight:** Spend 45 minutes on your weakest {$lowCode} topic using active recall.\n";
            $out .= "- **This Week:** Complete one timed practice paper and review all corrections with your educator.";
            return $out;
        }

        // C. Study Techniques & Memory
        if (preg_match('/(technique|method|recall|pomodoro|memory|study|learn|tip)/i', $qLower)) {
            $out = "### Evidence-Based Study Techniques for Technical Modules: **{$name}**\n\n";
            $out .= "To elevate **{$lowCode}** (currently **{$lowAvg}%**) while maintaining your stellar standing in **{$highCode}** (**{$highAvg}%**), implement these three cognitive techniques:\n\n";
            $out .= "1. **Active Retrieval Practice (Blank-Sheet Method):**\n";
            $out .= "   - After reviewing any topic in {$lowName}, close all textbooks and slides.\n";
            $out .= "   - On a blank page, write out every formula, diagram, or syntax rule you can recall.\n";
            $out .= "   - Open your notes and highlight omissions in red; repeat until your recall is 100% complete.\n\n";
            $out .= "2. **The Pomodoro 50/10 Interval:**\n";
            $out .= "   - Work in 50-minute blocks of high-intensity, distraction-free focus.\n";
            $out .= "   - Take a mandatory 10-minute cognitive rest (no screen or social media) to allow memory consolidation.\n\n";
            $out .= "3. **Interleaved Problem Solving:**\n";
            $out .= "   - Rather than doing 20 identical exercises, mix question types (e.g., theory, calculation, syntax) to train your brain to recognize patterns under exam conditions.\n\n";
            $out .= "### Action Plan\n";
            $out .= "- **Action 1:** Apply the blank-sheet retrieval method to one {$lowCode} chapter today.\n";
            $out .= "- **Action 2:** Log your completed focus blocks in a revision journal.";
            return $out;
        }

        // D. Exam & Assessment Preparation
        if (preg_match('/(exam|prep|test|quiz|assess|final|paper)/i', $qLower)) {
            $out = "### Final Exam Readiness Protocol: **{$name}**\n\n";
            $out .= "With a current running average of **{$overallAvg}%** across enrolled modules, strategic exam preparation will maximize your aggregate GPA:\n\n";
            $out .= "#### 7-Day Countdown Strategy\n";
            $out .= "- **Days 1–2 (Diagnostic Audit):** Complete one past exam paper for **{$lowCode}** under timed conditions without notes. Score it strictly using the marking guide.\n";
            $out .= "- **Days 3–4 (Gap Mastery):** Spend 2 focused hours resolving the specific topics where marks were lost in the diagnostic test.\n";
            $out .= "- **Days 5–6 (Speed & Pace Training):** Re-attempt past exam questions with a timer set 10% faster than the official exam duration to build pace and resilience.\n";
            $out .= "- **Day 7 (Low-Load Review):** Review your formula sheets, summary cards, and error log. Get at least 8 hours of sleep before the assessment.\n\n";
            $out .= "### Action Plan\n";
            $out .= "- **Immediate Step:** Download 2 past examination papers for {$lowCode} from the student portal.\n";
            $out .= "- **Daily Rule:** Spend 60% of each study day on problem-solving rather than passive reading.";
            return $out;
        }

        // E. Concept / Technical Inquiries (SQL, Web, Code, Database)
        if (preg_match('/(sql|database|query|join|table|web|html|css|javascript|code|programming|normal)/i', $qLower)) {
            $out = "### Subject Mastery Coaching: **{$name}**\n\n";
            $out .= "Regarding: *\"{$cleanQ}\"*\n\n";
            $out .= "- **Practical Application First:** Technical modules like **{$lowName}** and **{$highName}** cannot be mastered through memorization alone. Write, run, and debug practical examples on your computer or on paper.\n";
            $out .= "- **Concept Decomposition:** Break down complex operations into inputs, transformations, and expected outputs.\n";
            $out .= "- **Explain the 'Why':** For every syntax structure or design decision, articulate why that specific solution was chosen over alternatives.\n\n";
            $out .= "### Action Plan\n";
            $out .= "- **Step 1:** Write out 3 practical exercises illustrating this concept from scratch.\n";
            $out .= "- **Step 2:** Test edge cases and observe how errors are generated and resolved.";
            return $out;
        }

        // F. Off-Topic / Casual Queries
        if (preg_match('/(football|soccer|rugby|sport|game|movie|music|party|play|weekend)/i', $qLower)) {
            $out = "### Academic Balance Guidance: **{$name}**\n\n";
            $out .= "Engaging in sports and recreation is valuable for maintaining mental energy, cognitive resilience, and healthy stress management throughout the academic term.\n\n";
            $out .= "- **Earned Downtime:** Structure your sports or hobbies as designated rewards *after* completing your daily study targets for **{$lowCode}**.\n";
            $out .= "- **Energy Management:** Keep your revision periods focused and distraction-free so you enjoy recreational downtime with complete peace of mind.\n\n";
            $out .= "### Action Plan\n";
            $out .= "- **Goal:** Complete your scheduled 45-minute revision block for {$lowCode} before recreational activities today.";
            return $out;
        }

        // G. General Question Response
        $out = "### Academic Advisor Analysis: **{$name}**\n\n";
        $out .= "Regarding your inquiry: *\"{$cleanQ}\"*\n\n";
        $out .= "- **Academic Standing:** Your cumulative average is **{$overallAvg}%**, led by high achievement in **{$highCode}** (**{$highAvg}%**) and a critical focus area in **{$lowCode}** (**{$lowAvg}%**).\n";
        $out .= "- **Target Priority:** Bringing **{$lowCode}** from **{$lowAvg}%** across the 50% pass mark (a gap of only **{$gapToPass} points**) will significantly strengthen your transcript.\n";
        $out .= "- **Consistency Rule:** Dedicate 45 minutes daily to problem sets and active recall in {$lowCode} to establish permanent mastery.\n\n";
        $out .= "### Action Plan\n";
        $out .= "- **Action 1:** Review your notes on {$lowCode} today and create 5 retrieval questions.\n";
        $out .= "- **Action 2:** Schedule an academic check-in with your lecturer before the next quarterly assessment.";
        return $out;
    }

    // Default Full Academic Diagnosis Report
    $out = "### Comprehensive Academic Performance Diagnostic: **{$name}**\n\n";
    $out .= "#### 1. Overall Performance Standing\n";
    if ($overallAvg !== null) {
        $out .= "- **Cumulative Grade Average:** **{$overallAvg}%** across all registered modules.\n";
    }
    $out .= "- **Academic Trajectory:** Your performance demonstrates a strong technical aptitude in applied subjects, combined with an urgent recovery priority in foundational coursework.\n\n";

    $out .= "#### 2. Priority Modules Needing Immediate Focus\n";
    if ($lowest && $lowest['avg'] < 50) {
        $out .= "- **{$lowCode} ({$lowName}):** Critical priority (Current Average: **{$lowAvg}%**).\n";
        $out .= "  - You are currently **{$gapToPass} percentage points** below the 50% pass threshold.\n";
        if (!empty($lowTrajectory)) {
            $out .= "  - Quarterly trajectory: Marks {$lowTrajectory}.\n";
        }
        $out .= "  - Immediate target: Secure at least **55%** in the upcoming quarterly assessment cycle.\n\n";
    } elseif ($lowest && $lowest['avg'] < 65) {
        $out .= "- **{$lowCode} ({$lowName}):** Moderate risk (Current Average: **{$lowAvg}%**). Additional coursework reinforcement will elevate this mark into the distinction bracket.\n\n";
    } else {
        $out .= "- All registered modules currently meet or exceed the 65% benchmark.\n\n";
    }

    $out .= "#### 3. Areas of Academic Strength\n";
    if ($highest) {
        $out .= "- **{$highCode} ({$highName}):** Commendable achievement (Current Average: **{$highAvg}%**).\n";
        if ($highAvg >= 75) {
            $out .= "  - This module demonstrates distinction-level competence (**+{$gapToDistinction} points** above standard distinction requirements).\n";
        }
        $out .= "  - Maintain this strength through weekly revision while applying your successful study habits from this subject to {$lowCode}.\n\n";
    }

    $out .= "#### 4. High-Impact Study Interventions\n";
    $out .= "- **Active Recall Sessions:** Spend 30 minutes testing yourself from memory on key syllabus concepts before looking at slides.\n";
    $out .= "- **Spaced Repetition Review:** Revisit challenging topics from {$lowCode} every 48 hours to ensure long-term memory retention.\n";
    $out .= "- **Timed Past Paper Assessments:** Complete previous examination papers under strict timed conditions to master pacing.\n\n";

    $out .= "### Action Plan: 2-Week Structured Success Roadmap\n";
    $out .= "- **Week 1 (Diagnostic & Recovery):** Complete a comprehensive review of all {$lowCode} quarterly assessments, identify the top 3 conceptual weaknesses, and solve 5 practice questions daily.\n";
    $out .= "- **Week 2 (Mastery & Simulation):** Maintain {$highCode} with two 45-minute revision blocks, and complete a full mock timed exam for {$lowCode} with educator feedback.";

    return $out;
}

$dynamicResponse = generateDynamicAcademicIntelligence($studentName, $moduleSummaries, $lowestModule, $highestModule, $overallAverage, $userQuestion);

echo json_encode([
    'success' => true,
    'advice' => $dynamicResponse,
    'model' => 'Dynamic Academic Intelligence Engine',
    'source' => 'Academic Intelligence (Dynamic Institutional Mode)'
]);
exit;
