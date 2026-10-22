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
        $this->model = $model ?? (defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'cohere/north-mini-code:free');
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
     * Executes a chat completion call to OpenRouter with automatic multi-model failover rotation.
     * 
     * Incorporates Atlas-grade safeguards:
     * 1. 25s timeout for primary attempt (permits full code generation) and 15s for subsequent candidates.
     * 2. Retryable HTTP status detection (429 rate limit, 500-504 server down, 400/404 retired model).
     * 3. Response body error pattern matching ("overloaded", "rate limit", "temporarily unavailable").
     * 4. Multi-model candidate pool rotation across verified free models without content-safety models.
     * 5. Fallback extraction of reasoning tokens & cleaning of raw <think> tags.
     */
    public function callOpenRouter(array $messages, ?string $modelOverride = null): array {
        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'error' => 'AI Academic Success Coach is currently unavailable. Please try again later.'
            ];
        }

        $fallbackPool = defined('OPENROUTER_FALLBACK_MODELS') && is_array(OPENROUTER_FALLBACK_MODELS)
            ? OPENROUTER_FALLBACK_MODELS
            : [
                'cohere/north-mini-code:free',
                'minimax/minimax-m3.1-flash:free',
                'poolside/laguna-s-2.1:free',
                'nvidia/nemotron-3.5-lightning:free'
            ];

        $primary = $modelOverride ?: $this->model;
        // Build rotation list with primary first, followed by deduplicated fallback pool (max 3 candidates)
        $rotationCandidates = array_slice(array_values(array_unique(array_merge([$primary], $fallbackPool))), 0, 3);
        $url = 'https://openrouter.ai/api/v1/chat/completions';

        $headers = [
            'Authorization: Bearer ' . trim($this->apiKey),
            'Content-Type: application/json',
            'HTTP-Referer: ' . $this->siteUrl,
            'X-Title: ' . $this->siteName
        ];

        $attemptedModels = [];
        $lastError = null;

        // Multi-model failover rotation engine
        foreach ($rotationCandidates as $idx => $candidateModel) {
            $attemptedModels[] = $candidateModel;
            $attemptTimeout = ($idx === 0) ? 25 : 15; // 25s for primary attempt, 15s for fallbacks

            $payload = [
                'model' => $candidateModel,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 1500
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_TIMEOUT, $attemptTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $rawResp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            unset($ch);

            // 1. Connection / Timeout error: rotate immediately
            if ($curlErr) {
                $lastError = "cURL error on {$candidateModel}: {$curlErr}";
                continue;
            }

            // 2. Retryable HTTP Status Codes (429 rate limit, 500-504 server down, 400/404 retired model)
            if (in_array($httpCode, [429, 500, 502, 503, 504, 400, 404])) {
                $lastError = "HTTP {$httpCode} on {$candidateModel}";
                continue;
            }

            // 3. Inspect response JSON payload
            if (!empty($rawResp)) {
                $decoded = json_decode($rawResp, true);

                // Detect upstream error objects
                if (isset($decoded['error'])) {
                    $errMsg = is_string($decoded['error']) ? $decoded['error'] : ($decoded['error']['message'] ?? '');
                    $lowered = strtolower($errMsg);
                    if (strpos($lowered, 'rate limit') !== false ||
                        strpos($lowered, 'overloaded') !== false ||
                        strpos($lowered, 'temporarily unavailable') !== false ||
                        strpos($lowered, 'no available provider') !== false ||
                        strpos($lowered, 'not found') !== false) {
                        $lastError = "Provider error on {$candidateModel}: {$errMsg}";
                        continue; // Rotate to next candidate
                    }
                }

                if ($httpCode >= 200 && $httpCode < 300) {
                    $choice = $decoded['choices'][0]['message'] ?? [];
                    $content = trim((string)($choice['content'] ?? ''));

                    // Extract reasoning if content is empty (Nemotron / DeepSeek / Minimax pattern)
                    if (empty($content) && !empty($choice['reasoning'])) {
                        $content = trim((string)$choice['reasoning']);
                    }

                    // Clean raw <think>...</think> tags if reasoning model embedded them
                    if (!empty($content)) {
                        $content = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $content);
                        $content = trim($content);
                    }

                    $resolvedModel = $decoded['model'] ?? $candidateModel;

                    // Discard content safety classifier output (e.g. nemotron-3.5-content-safety)
                    if (strpos(strtolower($resolvedModel), 'content-safety') !== false ||
                        strpos(strtolower($content), 'user safety: safe') !== false) {
                        $lastError = "Filtered safety classifier response on {$candidateModel}";
                        continue;
                    }

                    if (!empty($content)) {
                        return [
                            'success' => true,
                            'content' => $content,
                            'model' => $resolvedModel,
                            'source' => 'OpenRouter AI (' . $resolvedModel . ')',
                            'attempts' => $attemptedModels
                        ];
                    }
                }
            }
        }

        // All fallback candidates exhausted
        return [
            'success' => false,
            'error' => 'AI Academic Success Coach is currently unavailable. Please try again later.',
            'debug' => $lastError,
            'attempts' => $attemptedModels
        ];
    }

    /**
     * Performs a one-click comprehensive performance state analysis.
     */
    public function analyzeStudentState(array $studentContext): array {
        $systemPrompt = $this->buildSystemPrompt($studentContext);
        $userPrompt = "Please diagnose my quarterly academic performance transcript and provide my success roadmap.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

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

        return [
            'success' => false,
            'error' => 'AI Academic Success Coach is currently unavailable. Please try again later.'
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

        $recentHistory = array_slice($chatHistory, -8);
        foreach ($recentHistory as $msg) {
            if (isset($msg['role']) && isset($msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => (string)$msg['content']
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

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

        return [
            'success' => false,
            'error' => 'AI Academic Success Coach is currently unavailable. Please try again later.'
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
