<?php
/**
 * OpenRouterService Class
 * 
 * Handles AI-powered academic performance analysis and interactive success coaching
 * for students using OpenRouter Free API (with automatic institutional fallback).
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
        $this->model = $model ?? (defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'google/gemini-2.0-flash-exp:free');
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
PROMPT;
    }

    /**
     * Executes a chat completion call to OpenRouter.
     */
    public function callOpenRouter(array $messages, ?string $modelOverride = null): array {
        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'error' => 'API key not configured'
            ];
        }

        $model = $modelOverride ?: $this->model;
        $url = 'https://openrouter.ai/api/v1/chat/completions';

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.5,
            'max_tokens' => 1200
        ];

        $headers = [
            'Authorization: Bearer ' . trim($this->apiKey),
            'Content-Type: application/json',
            'HTTP-Referer: ' . $this->siteUrl,
            'X-Title: ' . $this->siteName
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error' => 'Network error connecting to OpenRouter: ' . $curlError
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($data['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'],
                'model' => $data['model'] ?? $model,
                'source' => 'openrouter'
            ];
        }

        $errorMsg = $data['error']['message'] ?? "OpenRouter returned HTTP {$httpCode}";
        return [
            'success' => false,
            'error' => $errorMsg,
            'http_code' => $httpCode
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
     * Generates an intelligent, rule-based algorithmic analysis when API key is offline or unavailable.
     */
    private function generateInstitutionalAnalysis(array $context): string {
        $name = $context['name'] ?? 'Student';
        $results = $context['results'] ?? [];
        $overallAvg = $context['overall_average'] ?? null;

        if (empty($results)) {
            return "### 📘 Academic Standing Overview\n\nWelcome, **{$name}**! No quarterly marks have been captured for your profile yet. Once your educators record marks for Q1-Q4, this advisor will provide comprehensive diagnostic feedback.";
        }

        // Sort modules by average ascending to find weakest
        usort($results, function($a, $b) {
            $avgA = $a['average'] !== null ? (float)$a['average'] : 100;
            $avgB = $b['average'] !== null ? (float)$b['average'] : 100;
            return $avgA <=> $avgB;
        });

        $weakest = $results[0];
        $strongest = end($results);

        $out = "### 📊 Academic Diagnostic & Performance Report\n\n";
        $out .= "**Student:** {$name} | **Cumulative Average:** " . ($overallAvg !== null ? number_format($overallAvg, 2) . "%" : "Pending") . "\n\n";

        $out .= "#### 1. Performance Overview\n";
        if ($overallAvg >= 75) {
            $out .= "🌟 **Distinction Standing:** You are performing at an institutional honors level! Your consistent quarterly output demonstrates strong conceptual mastery.\n\n";
        } elseif ($overallAvg >= 60) {
            $out .= "✅ **Good Standing:** You have solid fundamentals and are comfortably passing. With focused revision on your lower-quartile modules, you can push into distinction territory.\n\n";
        } elseif ($overallAvg >= 50) {
            $out .= "⚠️ **Moderate Standing:** You meet the pass criteria, but several modules are in the borderline zone. Proactive interventions are recommended before final assessments.\n\n";
        } else {
            $out .= "🚨 **Critical Intervention Zone:** Your overall average is currently below the passing threshold of 50%. Immediate consultation with your lecturers and a daily revision plan are required.\n\n";
        }

        $out .= "#### 2. Priority Modules Needing Immediate Focus\n";
        $weakAvg = $weakest['average'] !== null ? number_format($weakest['average'], 2) . '%' : 'Pending';
        $out .= "- **{$weakest['module_code']} ({$weakest['module_name']}):** Current average is **{$weakAvg}**.\n";
        $out .= "  - *Quarterly Breakdown:* Q1=" . ($weakest['quarter1'] ?? '-') . "%, Q2=" . ($weakest['quarter2'] ?? '-') . "%, Q3=" . ($weakest['quarter3'] ?? '-') . "%, Q4=" . ($weakest['quarter4'] ?? '-') . "%\n";
        $out .= "  - *Diagnosis:* This module has the greatest impact on your GPA. Focus on addressing conceptual gaps in this syllabus before the next evaluation.\n\n";

        $out .= "#### 3. Actionable Study Strategies\n";
        $out .= "- **Active Recall & Self-Testing:** Don't just re-read lecture notes. After studying a topic in `{$weakest['module_code']}`, close your notes and write out the core concepts or solve sample problems from memory.\n";
        $out .= "- **Spaced Repetition (The 2-3-5 Rule):** Review new material on day 2, day 3, and day 5 after the lecture to shift knowledge into long-term memory.\n";
        $out .= "- **Consultation Hours:** Schedule a 15-minute consultation with your educator to review questions you missed in Q1/Q2 assessments.\n\n";

        $out .= "#### 4. Top Performing Subject\n";
        $strongAvg = $strongest['average'] !== null ? number_format($strongest['average'], 2) . '%' : 'Pending';
        $out .= "- **{$strongest['module_code']} ({$strongest['module_name']}):** Outstanding work maintaining **{$strongAvg}**! Continue leveraging your effective study routine from this module across your other subjects.\n";

        return $out;
    }

    /**
     * Fallback contextual chat generator.
     */
    private function generateLocalChatReply(array $context, string $message): string {
        $lower = strtolower($message);
        $results = $context['results'] ?? [];

        if (strpos($lower, 'plan') !== false || strpos($lower, 'schedule') !== false || strpos($lower, 'time') !== false) {
            return "Here is a high-impact **Weekly Study Framework** tailored to your current load:\n\n"
                 . "- **Monday & Wednesday (6:00 PM - 8:00 PM):** High-Priority Module Focus (review weakest topics, solve practice questions).\n"
                 . "- **Tuesday & Thursday (6:00 PM - 7:30 PM):** Secondary Modules (summarize lecture notes, flashcards).\n"
                 . "- **Saturday (Morning 9:00 AM - 12:00 PM):** Timed Past Exam Questions & practical laboratory exercises.\n"
                 . "- **Sunday:** Rest and light 30-minute review of the upcoming week's lecture outlines.";
        }

        if (strpos($lower, 'improve') !== false || strpos($lower, 'better') !== false || strpos($lower, 'pass') !== false) {
            return "To rapidly improve your marks in your modules:\n\n"
                 . "1. **Analyze Past Mistakes:** Look at your Q1 & Q2 assessment papers. Categorize mistakes into *careless errors* vs *conceptual misunderstandings*.\n"
                 . "2. **Feynman Technique:** Explain complex syllabus topics out loud in simple language. When you get stuck, re-consult your course text.\n"
                 . "3. **Practice Under Exam Conditions:** Use a timer when answering tutorial sheets to train your recall speed and reduce test anxiety.";
        }

        return "I am tracking your academic modules and results! To get the most tailored advice, ask me questions like:\n\n"
             . "- *'How can I improve my marks in my lowest scoring module?'*\n"
             . "- *'Create a 2-week exam revision timetable for me'*\n"
             . "- *'What study techniques work best for programming or database modules?'*";
    }
}
