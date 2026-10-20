<?php
/**
 * OpenRouterService Class
 * 
 * Handles AI-powered academic performance analysis and interactive success coaching
 * for students using OpenRouter Free API (with automatic institutional fallback).
 * 
 * Features:
 * - One-click comprehensive performance diagnosis
 * - Interactive academic success coaching chat
 * - Trend analysis & SWOT academic breakdown
 * - Module-specific study technique recommendations
 * - Admin/Lecturer class performance analytics
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Directorate: Information Technology Business Solutions (ITBS)
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class OpenRouterService {
    private string $apiKey;
    private string $model;
    private string $siteUrl;
    private string $siteName;

    public function __construct(?string $apiKey = null, ?string $model = null) {
        $this->apiKey = $apiKey ?? (defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : '');
        $this->model = $model ?? (defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'minimax/minimax-m3.1-flash:free');
        $this->siteUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost';
        $this->siteName = defined('OPENROUTER_SITE_NAME') ? OPENROUTER_SITE_NAME : 'SMS University Portal';
    }

    /**
     * Checks whether an OpenRouter API key is configured.
     */
    public function hasApiKey(): bool {
        return !empty(trim($this->apiKey));
    }

    /**
     * Builds the context-limiting system prompt.
     */
    public function buildSystemPrompt(array $studentContext): string {
        $studentName = $studentContext['name'] ?? 'Student';
        $studentNumber = $studentContext['student_number'] ?? 'N/A';
        $course = $studentContext['course'] ?? 'Undergraduate Studies';
        $overallAvg = isset($studentContext['overall_average']) ? number_format($studentContext['overall_average'], 2) . '%' : 'N/A';
        $results = $studentContext['results'] ?? [];

        $modulesText = "";
        if (!empty($results)) {
            foreach ($results as $r) {
                $code = $r['module_code'] ?? 'MOD';
                $name = $r['module_name'] ?? 'Module';
                $credits = $r['credits'] ?? '15';
                $q1 = $r['quarter1'] !== null ? $r['quarter1'] . '%' : 'Pending';
                $q2 = $r['quarter2'] !== null ? $r['quarter2'] . '%' : 'Pending';
                $q3 = $r['quarter3'] !== null ? $r['quarter3'] . '%' : 'Pending';
                $q4 = $r['quarter4'] !== null ? $r['quarter4'] . '%' : 'Pending';
                $avg = $r['average'] !== null ? $r['average'] . '%' : 'Pending';
                $modulesText .= "- {$code} ({$name}, {$credits} credits): Q1={$q1}, Q2={$q2}, Q3={$q3}, Q4={$q4} | Final Average={$avg}\n";
            }
        } else {
            $modulesText = "No quarterly marks recorded yet.\n";
        }

        return <<<PROMPT
You are the SMS Academic Success AI Advisor, a dedicated academic mentor and performance coach for university students.
Your mission is to help {$studentName} (Student Number: {$studentNumber}), currently studying {$course}, understand where and how to improve in their specific curriculum modules.

CURRENT STUDENT PERFORMANCE CONTEXT:
Overall Running Average: {$overallAvg}
Enrolled Modules & Marks:
{$modulesText}

STRICT SYSTEM RULES & CONTEXT BOUNDARIES:
1. ONLY discuss topics directly relevant to {$studentName}'s academic performance, study techniques, revision strategies, syllabus comprehension, and exam preparation.
2. If asked about unrelated subjects (politics, entertainment, unrelated coding projects, casual chit-chat), politely decline and redirect the student back to their academic goals and module results.
3. Highlight specific modules with low scores (under 50% is a critical fail risk, 50-65% needs targeted revision, 75%+ is distinction).
4. Provide concrete, high-value study techniques (e.g., active recall, Pomodoro cycles, spaced repetition, past exam paper practice, code kata exercises for programming, schema design drills for databases).
5. Maintain an inspiring, constructive, empathetic, and professional tone with clear markdown formatting (bolding, bullet points, headers).
6. When analyzing trends, compare quarterly marks across Q1→Q4 to identify improvement or decline patterns.
7. Give specific, actionable next-steps — not vague advice.
8. If the student is performing well (75%+), acknowledge their success and suggest how to maintain or push for higher distinctions.

RESPONSE FORMAT:
- Use clean markdown formatting with headers (###, ####), bold (**text**), and structured bullet points.
- Maintain an official, institutional academic tone suitable for an accredited student portal.
- DO NOT use emojis (no ✨, 💡, 🌟, 📊, 🎯, etc.). All visual styling and iconography are handled natively by the portal's Material Design system.
PROMPT;
    }

    /**
     * Executes a chat completion call to OpenRouter with automatic multi-model fallback.
     */
    public function callOpenRouter(array $messages, ?string $modelOverride = null): array {
        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'error' => 'API key not configured'
            ];
        }

        $fallbackList = defined('OPENROUTER_FALLBACK_MODELS') && is_array(OPENROUTER_FALLBACK_MODELS)
            ? OPENROUTER_FALLBACK_MODELS
            : ['minimax/minimax-m3.1-flash:free', 'poolside/laguna-s-2.1:free', 'poolside/laguna-xs-2.1:free'];

        $primary = $modelOverride ?: $this->model;
        $models = array_slice(array_values(array_unique(array_merge([$primary], $fallbackList))), 0, 3);
        $url = 'https://openrouter.ai/api/v1/chat/completions';

        $headers = [
            'Authorization: Bearer ' . trim($this->apiKey),
            'Content-Type: application/json',
            'HTTP-Referer: ' . $this->siteUrl,
            'X-Title: ' . $this->siteName
        ];

        // Attempt 1: Multi-model array
        $payload = [
            'models' => $models,
            'messages' => $messages,
            'temperature' => 0.5,
            'max_tokens' => 1600
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if (!$curlError && $httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            $content = trim((string)($data['choices'][0]['message']['content'] ?? ''));
            $usedModel = $data['model'] ?? ($models[0] ?? 'OpenRouter AI');

            if (!empty($content) && stripos($content, 'User Safety:') === false && strlen($content) > 40 && !preg_match('/^(We need|I need to|Thinking Process|Drafting)/i', $content)) {
                return [
                    'success' => true,
                    'content' => $content,
                    'model' => $usedModel,
                    'source' => 'OpenRouter AI (' . $usedModel . ')'
                ];
            }
        }

        // Attempt 2: Sequential model fallback
        foreach ($models as $singleModel) {
            $singlePayload = [
                'model' => $singleModel,
                'messages' => $messages,
                'temperature' => 0.5,
                'max_tokens' => 1600
            ];

            $ch2 = curl_init($url);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($singlePayload));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

            $singleResp = curl_exec($ch2);
            $singleHttp = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            $singleErr = curl_error($ch2);
            unset($ch2);

            if (!$singleErr && $singleHttp >= 200 && $singleHttp < 300) {
                $singleData = json_decode($singleResp, true);
                $content = trim((string)($singleData['choices'][0]['message']['content'] ?? ''));
                $usedModel = $singleData['model'] ?? $singleModel;

                if (!empty($content) && stripos($content, 'User Safety:') === false && strlen($content) > 40 && !preg_match('/^(We need|I need to|Thinking Process|Drafting)/i', $content)) {
                    return [
                        'success' => true,
                        'content' => $content,
                        'model' => $usedModel,
                        'source' => 'OpenRouter AI (' . $usedModel . ')'
                    ];
                }
            }
        }

        return [
            'success' => false,
            'error' => 'All OpenRouter AI candidate endpoints failed or timed out.'
        ];
    }

    /**
     * Performs a one-click comprehensive performance state analysis.
     */
    public function analyzeStudentState(array $studentContext): array {
        $systemPrompt = $this->buildSystemPrompt($studentContext);
        $userPrompt = "Please analyze my current quarterly marks and module results. Give me a structured breakdown including:
1. Academic Standing Overview
2. Priority Modules Needing Immediate Focus (and why)
3. Specific Practical Study Interventions for my lowest scoring modules
4. A 2-Week Actionable Revision Schedule
5. Positive Encouragement on my strongest subjects.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        // Try OpenRouter first if configured
        if ($this->hasApiKey()) {
            $apiResult = $this->callOpenRouter($messages);
            if ($apiResult['success']) {
                return [
                    'success' => true,
                    'analysis' => $apiResult['content'],
                    'model' => $apiResult['model'],
                    'source' => 'OpenRouter AI (' . $apiResult['model'] . ')'
                ];
            }
        }

        // Seamless high-fidelity institutional fallback
        $localAnalysis = $this->generateInstitutionalAnalysis($studentContext);
        return [
            'success' => true,
            'analysis' => $localAnalysis,
            'model' => 'Institutional Academic Heuristic Engine',
            'source' => 'Institutional Advisor (Built-in Heuristic AI)'
        ];
    }

    /**
     * Handles ongoing conversation with the student.
     */
    public function chatWithStudent(array $studentContext, array $chatHistory, string $userMessage): array {
        $systemPrompt = $this->buildSystemPrompt($studentContext);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Append recent sanitized history (max 8 messages to stay within token limits)
        $recentHistory = array_slice($chatHistory, -8);
        foreach ($recentHistory as $msg) {
            if (isset($msg['role']) && isset($msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => (string)$msg['content']
                ];
            }
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // Try OpenRouter
        if ($this->hasApiKey()) {
            $apiResult = $this->callOpenRouter($messages);
            if ($apiResult['success']) {
                return [
                    'success' => true,
                    'reply' => $apiResult['content'],
                    'source' => 'OpenRouter AI'
                ];
            }
        }

        // Fallback chat responder
        $reply = $this->generateLocalChatReply($studentContext, $userMessage);
        return [
            'success' => true,
            'reply' => $reply,
            'source' => 'Institutional AI Coach'
        ];
    }

    /**
     * Generates quick insights for dashboard cards (lightweight).
     */
    public function getQuickInsights(array $studentContext): array {
        $results = $studentContext['results'] ?? [];
        $overallAvg = $studentContext['overall_average'] ?? null;

        if (empty($results)) {
            return [
                'success' => true,
                'insights' => [
                    'trend' => 'neutral',
                    'trend_label' => 'No Data Yet',
                    'tip' => 'Once marks are recorded, AI insights will appear here.',
                    'risk_modules' => 0,
                    'strong_modules' => 0
                ]
            ];
        }

        // Analyze trends across quarters
        $improving = 0;
        $declining = 0;
        $riskModules = 0;
        $strongModules = 0;

        foreach ($results as $r) {
            $quarters = [];
            if ($r['quarter1'] !== null) $quarters[] = (float)$r['quarter1'];
            if ($r['quarter2'] !== null) $quarters[] = (float)$r['quarter2'];
            if ($r['quarter3'] !== null) $quarters[] = (float)$r['quarter3'];
            if ($r['quarter4'] !== null) $quarters[] = (float)$r['quarter4'];

            if (count($quarters) >= 2) {
                $lastTwo = array_slice($quarters, -2);
                if ($lastTwo[1] > $lastTwo[0]) $improving++;
                elseif ($lastTwo[1] < $lastTwo[0]) $declining++;
            }

            $avg = $r['average'] !== null ? (float)$r['average'] : null;
            if ($avg !== null) {
                if ($avg < 50) $riskModules++;
                if ($avg >= 75) $strongModules++;
            }
        }

        $trend = 'neutral';
        $trendLabel = 'Stable';
        if ($improving > $declining) { $trend = 'up'; $trendLabel = 'Improving'; }
        elseif ($declining > $improving) { $trend = 'down'; $trendLabel = 'Needs Attention'; }

        // Generate contextual tip
        $tip = $this->generateContextualTip($results, $overallAvg, $trend);

        return [
            'success' => true,
            'insights' => [
                'trend' => $trend,
                'trend_label' => $trendLabel,
                'tip' => $tip,
                'risk_modules' => $riskModules,
                'strong_modules' => $strongModules,
                'improving_count' => $improving,
                'declining_count' => $declining
            ]
        ];
    }

    /**
     * Generate a smart contextual tip based on performance data.
     */
    private function generateContextualTip(array $results, ?float $overallAvg, string $trend): string {
        if ($overallAvg === null) {
            return "Your marks are still being captured. Check back soon for AI-powered insights!";
        }

        if ($overallAvg >= 75) {
            return "🌟 Outstanding! You're in distinction territory. Focus on maintaining consistency and explore advanced topics in your strongest modules.";
        }

        if ($overallAvg >= 60) {
            if ($trend === 'up') {
                return "📈 Great momentum! Your marks are trending upward. Keep applying your current study strategies and push for distinction in your top modules.";
            }
            return "✅ Solid performance. Consider the Feynman Technique for your mid-range modules — explain concepts aloud to deepen understanding.";
        }

        if ($overallAvg >= 50) {
            // Find weakest module
            $weakest = null;
            foreach ($results as $r) {
                if ($r['average'] !== null) {
                    if (!$weakest || (float)$r['average'] < (float)$weakest['average']) {
                        $weakest = $r;
                    }
                }
            }
            $modName = $weakest ? $weakest['module_code'] : 'your lowest module';
            return "⚠️ You're passing but in the borderline zone. Prioritize **{$modName}** with daily 25-min focused study sessions using active recall.";
        }

        return "🚨 Critical zone — your average is below 50%. Schedule immediate consultation with your lecturers and commit to a structured daily revision plan. The AI Coach can create one for you!";
    }

    /**
     * Generates an intelligent, rule-based algorithmic analysis when API key is offline or unavailable.
     */
    private function generateInstitutionalAnalysis(array $context): string {
        $name = $context['name'] ?? 'Student';
        $results = $context['results'] ?? [];
        $overallAvg = $context['overall_average'] ?? null;

        if (empty($results)) {
            return "### 📘 Academic Standing Overview\n\nWelcome, **{$name}**! No quarterly marks have been captured for your profile yet. Once your educators record marks for Q1-Q4, this advisor will provide comprehensive diagnostic feedback.\n\n#### 🎯 What to Expect\n- **Performance Diagnosis:** Module-by-module analysis with risk assessment\n- **Trend Analysis:** Quarter-over-quarter improvement tracking\n- **Study Interventions:** Personalized study technique recommendations\n- **Revision Timetable:** Custom weekly study schedule based on your weak areas\n\nCheck back after your first quarterly assessment!";
        }

        // Sort modules by average ascending to find weakest
        usort($results, function($a, $b) {
            $avgA = $a['average'] !== null ? (float)$a['average'] : 100;
            $avgB = $b['average'] !== null ? (float)$b['average'] : 100;
            return $avgA <=> $avgB;
        });

        $weakest = $results[0];
        $strongest = end($results);

        // Categorize modules
        $critical = []; // < 50%
        $warning = [];  // 50-64%
        $good = [];     // 65-74%
        $excellent = []; // 75%+

        foreach ($results as $r) {
            $avg = $r['average'] !== null ? (float)$r['average'] : null;
            if ($avg === null) continue;
            if ($avg < 50) $critical[] = $r;
            elseif ($avg < 65) $warning[] = $r;
            elseif ($avg < 75) $good[] = $r;
            else $excellent[] = $r;
        }

        // Trend analysis
        $trends = [];
        foreach ($results as $r) {
            $quarters = [];
            if ($r['quarter1'] !== null) $quarters[] = (float)$r['quarter1'];
            if ($r['quarter2'] !== null) $quarters[] = (float)$r['quarter2'];
            if ($r['quarter3'] !== null) $quarters[] = (float)$r['quarter3'];
            if ($r['quarter4'] !== null) $quarters[] = (float)$r['quarter4'];
            
            if (count($quarters) >= 2) {
                $first = $quarters[0];
                $last = end($quarters);
                $diff = $last - $first;
                $trends[$r['module_code']] = [
                    'direction' => $diff > 2 ? 'improving' : ($diff < -2 ? 'declining' : 'stable'),
                    'change' => round($diff, 1),
                    'quarters' => $quarters
                ];
            }
        }

        $out = "### Academic Diagnostic & Performance Report\n\n";
        $out .= "**Student:** {$name} | **Cumulative Average:** " . ($overallAvg !== null ? number_format($overallAvg, 2) . "%" : "Pending") . "\n\n";

        // 1. Performance Overview
        $out .= "#### 1. Performance Standing Overview\n";
        if ($overallAvg >= 75) {
            $out .= "**Distinction Standing:** You are performing at an institutional honors level! Your consistent quarterly output demonstrates strong conceptual mastery.\n\n";
        } elseif ($overallAvg >= 60) {
            $out .= "**Good Standing:** You have solid fundamentals and are comfortably passing. With focused revision on your lower-quartile modules, you can push into distinction territory.\n\n";
        } elseif ($overallAvg >= 50) {
            $out .= "**Moderate Standing:** You meet the pass criteria, but several modules are in the borderline zone. Proactive interventions are recommended before final assessments.\n\n";
        } else {
            $out .= "**Critical Intervention Zone:** Your overall average is currently below the passing threshold of 50%. Immediate consultation with your lecturers and a daily revision plan are required.\n\n";
        }

        // Module distribution summary
        $out .= "**Module Performance Distribution:**\n";
        if (!empty($excellent)) $out .= "- **Distinction (75%+):** " . count($excellent) . " module(s)\n";
        if (!empty($good)) $out .= "- **Good (65-74%):** " . count($good) . " module(s)\n";
        if (!empty($warning)) $out .= "- **At Risk (50-64%):** " . count($warning) . " module(s)\n";
        if (!empty($critical)) $out .= "- **Critical (<50%):** " . count($critical) . " module(s)\n";
        $out .= "\n";

        // 2. Priority Modules
        $out .= "#### 2. Priority Modules Needing Immediate Focus\n";
        
        $priorityModules = array_merge($critical, $warning);
        if (!empty($priorityModules)) {
            foreach (array_slice($priorityModules, 0, 3) as $mod) {
                $modAvg = $mod['average'] !== null ? number_format($mod['average'], 2) . '%' : 'Pending';
                $risk = (float)($mod['average'] ?? 0) < 50 ? 'CRITICAL' : 'AT RISK';
                $out .= "\n**{$mod['module_code']} ({$mod['module_name']})** — [{$risk}]\n";
                $out .= "- Current Average: **{$modAvg}**\n";
                $out .= "- Quarterly Breakdown: Q1=" . ($mod['quarter1'] ?? '-') . "%, Q2=" . ($mod['quarter2'] ?? '-') . "%, Q3=" . ($mod['quarter3'] ?? '-') . "%, Q4=" . ($mod['quarter4'] ?? '-') . "%\n";
                
                // Trend for this module
                if (isset($trends[$mod['module_code']])) {
                    $t = $trends[$mod['module_code']];
                    $out .= "- Trend: {$t['direction']} ({$t['change']}% change)\n";
                }
            }
        } else {
            $out .= "No modules are currently below 65%. Keep up the excellent work!\n";
        }
        $out .= "\n";

        // 3. Trend Analysis
        if (!empty($trends)) {
            $out .= "#### 3. Quarter-over-Quarter Trend Analysis\n";
            foreach ($trends as $code => $t) {
                $qStr = implode('% -> ', $t['quarters']) . '%';
                $out .= "- **{$code}:** {$qStr} (net change: {$t['change']}%)\n";
            }
            $out .= "\n";
        }

        // 4. Study Strategies
        $out .= "#### 4. Actionable Study Strategies\n";
        $out .= "- **Active Recall & Self-Testing:** Don't just re-read lecture notes. After studying a topic in `{$weakest['module_code']}`, close your notes and write out the core concepts or solve sample problems from memory.\n";
        $out .= "- **Spaced Repetition (The 2-3-5 Rule):** Review new material on day 2, day 3, and day 5 after the lecture to shift knowledge into long-term memory.\n";
        $out .= "- **Pomodoro Technique:** Study in 25-minute focused blocks with 5-minute breaks. After 4 blocks, take a 15-minute break.\n";
        $out .= "- **Consultation Hours:** Schedule a 15-minute consultation with your educator to review questions you missed in Q1/Q2 assessments.\n";
        $out .= "- **Study Group Formation:** Find 2-3 classmates to form a study group for your weakest module. Teaching others reinforces your own understanding.\n\n";

        // 5. 2-Week Revision Schedule
        $out .= "#### 5. 2-Week Revision Schedule\n\n";
        $out .= "**Week 1 — Foundation Building:**\n";
        $out .= "- Mon & Wed (6-8 PM): Deep focus on `{$weakest['module_code']}` — review fundamentals, solve practice problems\n";
        if (count($results) > 1) {
            $secondWeakest = $results[1] ?? $results[0];
            $out .= "- Tue & Thu (6-7:30 PM): Focus on `{$secondWeakest['module_code']}` — summarize lecture notes, create flashcards\n";
        }
        $out .= "- Fri (5-6 PM): Light review of all modules — quick self-test on key concepts\n";
        $out .= "- Sat (9 AM - 12 PM): Timed past exam questions for `{$weakest['module_code']}`\n";
        $out .= "- Sun: Rest & 30-min preview of next week's lecture outlines\n\n";

        $out .= "**Week 2 — Consolidation & Practice:**\n";
        $out .= "- Mon & Wed (6-8 PM): Advanced practice on `{$weakest['module_code']}` — tackle harder problems\n";
        $out .= "- Tue & Thu (6-7:30 PM): Review mid-range modules — focus on areas you got wrong\n";
        $out .= "- Fri (5-7 PM): Full mock test under timed conditions\n";
        $out .= "- Sat (9 AM - 12 PM): Peer study session — teach concepts to a study partner\n";
        $out .= "- Sun: Reflection & plan adjustments for the next cycle\n\n";

        // 6. Strengths
        $out .= "#### 6. Top Performing Subjects\n";
        $strongAvg = $strongest['average'] !== null ? number_format($strongest['average'], 2) . '%' : 'Pending';
        $out .= "- **{$strongest['module_code']} ({$strongest['module_name']}):** Outstanding work maintaining **{$strongAvg}**! ";
        if ((float)($strongest['average'] ?? 0) >= 75) {
            $out .= "You're at distinction level — consider mentoring peers in this subject to reinforce your mastery.\n";
        } else {
            $out .= "Continue leveraging your effective study routine from this module across your other subjects.\n";
        }

        if (!empty($excellent) && count($excellent) > 1) {
            $out .= "\nAll distinction-level modules:\n";
            foreach ($excellent as $ex) {
                $out .= "- **{$ex['module_code']}** — {$ex['average']}%\n";
            }
        }

        return $out;
    }

    /**
     * Fallback contextual chat generator with enhanced topic detection.
     */
    private function generateLocalChatReply(array $context, string $message): string {
        $lower = strtolower($message);
        $results = $context['results'] ?? [];
        $name = $context['name'] ?? 'Student';

        // Study plan / schedule requests
        if (preg_match('/(plan|schedule|time|timetable|routine|weekly)/i', $lower)) {
            return "Here is a high-impact **Weekly Study Framework** tailored to your current load, **{$name}**:\n\n"
                 . "**Weekday Schedule:**\n"
                 . "- **Monday & Wednesday (6:00 PM - 8:00 PM):** High-Priority Module Focus — review weakest topics, solve practice questions\n"
                 . "- **Tuesday & Thursday (6:00 PM - 7:30 PM):** Secondary Modules — summarize lecture notes, create flashcards\n"
                 . "- **Friday (5:00 PM - 6:00 PM):** Quick review & self-test across all modules\n\n"
                 . "**Weekend Schedule:**\n"
                 . "- **Saturday (Morning 9:00 AM - 12:00 PM):** Timed Past Exam Questions & practical laboratory exercises\n"
                 . "- **Sunday:** Rest and light 30-minute review of the upcoming week's lecture outlines\n\n"
                 . "Action Plan:\n"
                 . "- Use the Pomodoro Technique (25 min focus + 5 min break)\n"
                 . "- Track your daily study hours in a simple notebook\n"
                 . "- Reward yourself after completing each week's goals!";
        }

        // Improvement / how to pass requests
        if (preg_match('/(improve|better|pass|fail|struggling|help|boost)/i', $lower)) {
            $weakest = null;
            foreach ($results as $r) {
                if ($r['average'] !== null && (!$weakest || (float)$r['average'] < (float)$weakest['average'])) {
                    $weakest = $r;
                }
            }
            $modRef = $weakest ? "`{$weakest['module_code']}`" : "your weakest module";
            
            return "Here's a targeted improvement strategy for you, **{$name}**:\n\n"
                 . "#### 1. Diagnose Your Weak Points\n"
                 . "- Look at your Q1 & Q2 assessment papers for {$modRef}\n"
                 . "- Categorize mistakes into **careless errors** vs **conceptual misunderstandings**\n"
                 . "- Focus 80% of your effort on conceptual gaps\n\n"
                 . "#### 2. Apply High-Impact Techniques\n"
                 . "- **Feynman Technique:** Explain complex syllabus topics out loud in simple language. When you get stuck, re-consult your course text.\n"
                 . "- **Active Recall:** After reading, close the book and write down everything you remember. Check what you missed.\n"
                 . "- **Practice Under Exam Conditions:** Use a timer when answering tutorial sheets to train recall speed and reduce test anxiety.\n\n"
                 . "#### 3. Track Your Progress\n"
                 . "- Score yourself on practice questions weekly\n"
                 . "- Aim to improve by 5-10% each quarter\n"
                 . "- Celebrate small wins — every percentage point matters!";
        }

        // Exam preparation
        if (preg_match('/(exam|test|assessment|prepare|preparation|study)/i', $lower)) {
            return "### 🎯 Exam Preparation Strategy for **{$name}**\n\n"
                 . "**4 Weeks Before the Exam:**\n"
                 . "- Gather all lecture notes, tutorials, and past papers\n"
                 . "- Create a topic checklist for each module\n"
                 . "- Identify your 3 weakest topics per module\n\n"
                 . "**2 Weeks Before:**\n"
                 . "- Complete at least 2 full past papers per module under timed conditions\n"
                 . "- Review model answers and mark your own work\n"
                 . "- Attend all consultation sessions with lecturers\n\n"
                 . "**1 Week Before:**\n"
                 . "- Focus on **summary sheets** — one page of key formulas/concepts per module\n"
                 . "- Do quick 15-minute active recall sessions daily\n"
                 . "- Get 7-8 hours of sleep each night — rest is essential for memory consolidation\n\n"
                 . "**Exam Day:**\n"
                 . "- Arrive 30 minutes early\n"
                 . "- Read through the entire paper before starting\n"
                 . "- Allocate time per question based on marks\n"
                 . "- Never leave a question blank — partial marks count!";
        }

        // Motivation / stress
        if (preg_match('/(stress|anxious|overwhelm|motivat|tired|burnout|discourag)/i', $lower)) {
            return "### 💪 You've Got This, {$name}!\n\n"
                 . "It's completely normal to feel stressed during your academic journey. Here's how to manage it:\n\n"
                 . "**🧘 Mental Wellness Tips:**\n"
                 . "- **Breathe:** Try the 4-7-8 technique: inhale for 4 seconds, hold for 7, exhale for 8\n"
                 . "- **Break it down:** Don't think about \"passing everything\" — focus on one topic at a time\n"
                 . "- **Move your body:** Even a 15-minute walk between study sessions boosts focus\n"
                 . "- **Talk to someone:** Your lecturers, peers, or campus counselors are there to help\n\n"
                 . "**🎯 Remember:**\n"
                 . "- Progress isn't always linear — bad days are part of the journey\n"
                 . "- You've already made it this far in your program\n"
                 . "- Small consistent effort beats last-minute cramming every time\n\n"
                 . "Would you like me to create a more manageable study schedule that includes proper rest breaks?";
        }

        // Module-specific inquiry
        foreach ($results as $r) {
            $code = strtolower($r['module_code'] ?? '');
            $modName = strtolower($r['module_name'] ?? '');
            if (!empty($code) && (strpos($lower, $code) !== false || strpos($lower, $modName) !== false)) {
                $avg = $r['average'] !== null ? number_format($r['average'], 2) . '%' : 'Pending';
                $status = '';
                if ($r['average'] !== null) {
                    if ((float)$r['average'] >= 75) $status = '🌟 Distinction level!';
                    elseif ((float)$r['average'] >= 50) $status = '✅ Passing, with room for improvement';
                    else $status = '🚨 Below pass mark — needs urgent attention';
                }
                
                return "### 📋 Module Report: {$r['module_code']} — {$r['module_name']}\n\n"
                     . "**Current Average:** {$avg} {$status}\n\n"
                     . "**Quarterly Breakdown:**\n"
                     . "- Q1: " . ($r['quarter1'] !== null ? $r['quarter1'] . '%' : 'Pending') . "\n"
                     . "- Q2: " . ($r['quarter2'] !== null ? $r['quarter2'] . '%' : 'Pending') . "\n"
                     . "- Q3: " . ($r['quarter3'] !== null ? $r['quarter3'] . '%' : 'Pending') . "\n"
                     . "- Q4: " . ($r['quarter4'] !== null ? $r['quarter4'] . '%' : 'Pending') . "\n\n"
                     . "**Recommended Study Approach:**\n"
                     . "- Review past assessment papers for this module\n"
                     . "- Create a mind map of the key syllabus topics\n"
                     . "- Schedule a consultation with your lecturer\n"
                     . "- Practice at least 3 past exam questions per week\n\n"
                     . "Would you like a detailed weekly study plan specifically for this module?";
            }
        }

        // Default response
        return "I'm tracking your academic modules and results, **{$name}**! To get the most tailored advice, try asking me questions like:\n\n"
             . "- 📉 *'How can I improve my marks in my lowest scoring module?'*\n"
             . "- 📅 *'Create a 2-week exam revision timetable for me'*\n"
             . "- 🧠 *'What study techniques work best for technical modules?'*\n"
             . "- 🎯 *'Help me prepare for upcoming assessments'*\n"
             . "- 💪 *'I'm feeling stressed about my marks'*\n"
             . "- 📋 *'Tell me about my performance in [module code]'*\n\n"
             . "I'm here to help you succeed! 🎓";
    }

    /**
     * Admin/Lecturer: Analyze class-wide performance for a specific module or all modules.
     */
    public function analyzeClassPerformance(array $classData): array {
        $moduleName = $classData['module_name'] ?? 'All Modules';
        $moduleCode = $classData['module_code'] ?? '';
        $studentResults = $classData['students'] ?? [];
        $totalStudents = count($studentResults);

        if (empty($studentResults)) {
            return [
                'success' => true,
                'analysis' => "### Class Performance Report: {$moduleCode}\n\nNo student results available for analysis.",
                'source' => 'Institutional Analytics Engine'
            ];
        }

        // Calculate class statistics
        $averages = [];
        $passing = 0;
        $failing = 0;
        $distinction = 0;

        foreach ($studentResults as $sr) {
            $avg = $sr['average'] !== null ? (float)$sr['average'] : null;
            if ($avg !== null) {
                $averages[] = $avg;
                if ($avg >= 75) $distinction++;
                elseif ($avg >= 50) $passing++;
                else $failing++;
            }
        }

        $classAvg = !empty($averages) ? round(array_sum($averages) / count($averages), 2) : 0;
        $highest = !empty($averages) ? max($averages) : 0;
        $lowest = !empty($averages) ? min($averages) : 0;
        $passRate = $totalStudents > 0 ? round(($passing + $distinction) / $totalStudents * 100, 1) : 0;

        $out = "### Class Performance Analytics: {$moduleCode} — {$moduleName}\n\n";
        $out .= "| Metric | Value |\n";
        $out .= "|--------|-------|\n";
        $out .= "| Total Students | {$totalStudents} |\n";
        $out .= "| Class Average | {$classAvg}% |\n";
        $out .= "| Highest Mark | {$highest}% |\n";
        $out .= "| Lowest Mark | {$lowest}% |\n";
        $out .= "| Pass Rate | {$passRate}% |\n";
        $out .= "| Distinctions | {$distinction} |\n";
        $out .= "| At Risk (< 50%) | {$failing} |\n\n";

        if ($failing > 0) {
            $out .= "#### ⚠️ Students At Risk\n";
            $out .= "{$failing} student(s) are currently below the 50% pass threshold and may need additional support.\n\n";
        }

        $out .= "#### 💡 Recommendations\n";
        if ($classAvg < 50) {
            $out .= "- Consider reviewing the assessment difficulty and marking rubric\n";
            $out .= "- Schedule additional tutorial sessions for struggling students\n";
            $out .= "- Identify common areas of weakness across the class\n";
        } elseif ($classAvg < 65) {
            $out .= "- The class is passing but could benefit from more practice exercises\n";
            $out .= "- Consider peer-assisted learning programs\n";
            $out .= "- Review which topics had the lowest scores across the class\n";
        } else {
            $out .= "- Strong class performance — maintain current teaching strategies\n";
            $out .= "- Consider advanced extension tasks for high-performing students\n";
        }

        return [
            'success' => true,
            'analysis' => $out,
            'source' => 'Institutional Analytics Engine'
        ];
    }
}
