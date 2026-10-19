<?php
// includes/ai_advisor_modal.php
// Reusable AI Academic Success Advisor Modal & Interactive Chat for Students
$hasOpenRouterKey = defined('OPENROUTER_API_KEY') && !empty(trim(OPENROUTER_API_KEY));
$modelName = defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'Free Tier AI';
?>

<!-- AI Academic Success Advisor Modal -->
<div id="aiAdvisorModal" class="modal modal-fixed-footer" style="max-width: 820px; width: 92%; max-height: 88%; border-radius: 12px; overflow: hidden;">
    <div class="modal-content" style="padding: 0; display: flex; flex-direction: column; height: 100%;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%); color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(255,255,255,0.2); width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="material-icons" style="font-size: 28px; color: #ffd54f;">auto_awesome</i>
                </div>
                <div>
                    <h5 style="margin: 0; font-weight: 600; font-size: 1.3rem;">AI Academic Success Advisor</h5>
                    <span style="font-size: 0.85rem; color: #bbdefb;">
                        Powered by OpenRouter Free API &amp; Institutional Performance Diagnostics
                    </span>
                </div>
            </div>
            <div>
                <?php if ($hasOpenRouterKey): ?>
                    <span class="chip tiny green lighten-4 green-text text-darken-4 tooltipped" data-tooltip="Live OpenRouter AI active">
                        <i class="material-icons left tiny">cloud_done</i>Live AI
                    </span>
                <?php else: ?>
                    <span class="chip tiny amber lighten-4 amber-text text-darken-4 tooltipped" data-tooltip="Local academic heuristic engine active">
                        <i class="material-icons left tiny">offline_bolt</i>Institutional AI
                    </span>
                <?php endif; ?>
                <a href="#!" class="modal-close waves-effect waves-light btn-flat white-text" style="min-width: 36px; padding: 0 8px;">
                    <i class="material-icons">close</i>
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div style="border-bottom: 1px solid #e0e0e0; background: #fafafa; padding: 6px 20px 0 20px;">
            <ul class="tabs tabs-transparent" id="aiAdvisorTabs" style="overflow: hidden;">
                <li class="tab col s6">
                    <a class="active blue-text text-darken-3" href="#aiAnalysisTab">
                        <i class="material-icons tiny" style="vertical-align: middle; margin-right: 4px;">analytics</i>Performance Diagnosis
                    </a>
                </li>
                <li class="tab col s6">
                    <a class="blue-text text-darken-3" href="#aiChatTab">
                        <i class="material-icons tiny" style="vertical-align: middle; margin-right: 4px;">chat</i>Success Coach Chat
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab 1: Diagnostic Analysis -->
        <div id="aiAnalysisTab" style="flex: 1; overflow-y: auto; padding: 24px;">
            <div class="card-panel blue lighten-5 blue-text text-darken-4" style="border-radius: 8px; margin-top: 0; padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <strong style="font-size: 1.05rem;">One-Click Academic Performance Analysis</strong>
                        <p style="margin: 4px 0 0 0; font-size: 0.88rem; color: #1565c0;">
                            Our AI reviews all your quarterly marks (Q1 - Q4), identifies vulnerable modules below 50% or needing improvement, and generates a tailored study plan.
                        </p>
                    </div>
                    <div>
                        <button type="button" id="btnRunAiAnalysis" class="btn-large waves-effect waves-light amber darken-3 z-depth-2" style="border-radius: 8px; font-weight: 600;">
                            <i class="material-icons left">psychology</i>Analyze My Performance
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="aiAnalysisLoading" style="display: none; text-align: center; padding: 40px 0;">
                <div class="preloader-wrapper big active">
                    <div class="spinner-layer spinner-blue-only">
                        <div class="circle-clipper left"><div class="circle"></div></div>
                        <div class="gap-patch"><div class="circle"></div></div>
                        <div class="circle-clipper right"><div class="circle"></div></div>
                    </div>
                </div>
                <p class="grey-text text-darken-1" style="margin-top: 15px; font-weight: 500;">
                    Reviewing your module results &amp; generating personalized recommendations...
                </p>
            </div>

            <!-- Analysis Output Container -->
            <div id="aiAnalysisResult" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span class="chip blue lighten-4 blue-text text-darken-4 font-weight-bold" id="aiAnalysisSourceBadge">
                        Advisor Diagnosis
                    </span>
                    <button type="button" class="btn-flat waves-effect btn-small" onclick="document.getElementById('btnRunAiAnalysis').click();">
                        <i class="material-icons left tiny">refresh</i>Re-Analyze
                    </button>
                </div>
                <div id="aiAnalysisContent" class="markdown-body" style="background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; line-height: 1.6;">
                </div>
            </div>

            <!-- Initial Placeholder -->
            <div id="aiAnalysisInitial" style="text-align: center; padding: 40px 20px; color: #757575;">
                <i class="material-icons large grey-text text-lighten-1">insights</i>
                <h6>Ready to review your academic progress</h6>
                <p style="max-width: 500px; margin: 8px auto 20px auto; font-size: 0.92rem;">
                    Click the <strong>"Analyze My Performance"</strong> button above to have AI diagnose your quarterly grades and guide you on what to study next.
                </p>
            </div>
        </div>

        <!-- Tab 2: Interactive Chat -->
        <div id="aiChatTab" style="flex: 1; display: flex; flex-direction: column; overflow: hidden; padding: 15px 20px 0 20px;">
            <!-- Starter Prompt Pills -->
            <div style="padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">
                <span class="grey-text" style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Suggested Topics:</span>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 5px;">
                    <a href="#!" class="chip ai-starter-chip" data-prompt="How can I improve my marks in my lowest scoring module?">
                        📉 Improve lowest module
                    </a>
                    <a href="#!" class="chip ai-starter-chip" data-prompt="Create a 2-week daily study timetable for my enrolled modules">
                        📅 2-Week study schedule
                    </a>
                    <a href="#!" class="chip ai-starter-chip" data-prompt="What active recall study techniques work best for technical modules?">
                        🧠 Best study methods
                    </a>
                    <a href="#!" class="chip ai-starter-chip" data-prompt="How do I prepare for upcoming Q3 and Q4 assessments?">
                        🎯 Assessment prep
                    </a>
                </div>
            </div>

            <!-- Chat Messages Log -->
            <div id="aiChatMessages" style="flex: 1; overflow-y: auto; padding: 15px 5px; display: flex; flex-direction: column; gap: 12px;">
                <div class="ai-msg-bubble ai-assistant-msg">
                    <div class="ai-msg-header">
                        <i class="material-icons tiny yellow-text text-darken-3">auto_awesome</i> <strong>Academic Success Coach</strong>
                    </div>
                    <div class="ai-msg-body">
                        Hello! I have direct access to your enrolled modules and quarterly results. How can I assist your study plans today? You can ask me how to study for specific modules, request a revision timetable, or ask for explanations of tough concepts!
                    </div>
                </div>
            </div>

            <!-- Chat Input Bar -->
            <div style="padding: 10px 0; border-top: 1px solid #e0e0e0;">
                <form id="aiChatForm" style="display: flex; gap: 10px; align-items: center; margin: 0;">
                    <div style="flex: 1;">
                        <input type="text" id="aiChatInput" placeholder="Ask your academic coach a question..." autocomplete="off" style="margin: 0; padding: 0 12px; height: 42px; border: 1px solid #ccc; border-radius: 20px; box-sizing: border-box;">
                    </div>
                    <button type="submit" id="btnSendAiChat" class="btn-floating waves-effect waves-light blue darken-3" title="Send Question">
                        <i class="material-icons">send</i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-footer" style="padding: 10px 24px; background: #fafafa; border-top: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
        <span class="grey-text" style="font-size: 0.8rem;">
            Context is strictly limited to your official enrolled modules &amp; academic outcomes.
        </span>
        <a href="#!" class="modal-close waves-effect waves-grey btn-flat">Close</a>
    </div>
</div>

<style>
/* AI Chat & Advisor Styling */
.ai-starter-chip {
    cursor: pointer;
    background: #e3f2fd !important;
    color: #1565c0 !important;
    font-size: 0.82rem !important;
    transition: all 0.2s ease;
}
.ai-starter-chip:hover {
    background: #bbdefb !important;
    transform: translateY(-1px);
}
.ai-msg-bubble {
    max-width: 85%;
    padding: 12px 16px;
    border-radius: 12px;
    line-height: 1.5;
    font-size: 0.92rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.ai-assistant-msg {
    align-self: flex-start;
    background: #f5f7fa;
    border: 1px solid #e4e7eb;
    border-top-left-radius: 2px;
}
.ai-user-msg {
    align-self: flex-end;
    background: #1565c0;
    color: white;
    border-top-right-radius: 2px;
}
.ai-msg-header {
    font-size: 0.78rem;
    margin-bottom: 4px;
    opacity: 0.85;
}
.markdown-body h3 { font-size: 1.25rem; font-weight: 600; color: #0d47a1; margin: 10px 0; }
.markdown-body h4 { font-size: 1.1rem; font-weight: 600; color: #1565c0; margin: 12px 0 6px 0; }
.markdown-body ul { padding-left: 20px; margin: 8px 0; }
.markdown-body li { margin-bottom: 6px; }
.markdown-body strong { color: #212121; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Materialize tabs
    var tabsEl = document.getElementById('aiAdvisorTabs');
    if (tabsEl) {
        M.Tabs.init(tabsEl);
    }

    // Markdown Parser Helper
    function renderSimpleMarkdown(text) {
        if (!text) return '';
        var escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        
        // Headers
        escaped = escaped.replace(/^### (.*$)/gim, '<h3>$1</h3>');
        escaped = escaped.replace(/^#### (.*$)/gim, '<h4>$1</h4>');
        
        // Bold
        escaped = escaped.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
        
        // Inline code
        escaped = escaped.replace(/`([^`]+)`/gim, '<code style="background:#f0f0f0; padding:2px 5px; border-radius:3px;">$1</code>');
        
        // Bullet points
        escaped = escaped.replace(/^\s*-\s+(.*$)/gim, '<li>$1</li>');
        escaped = escaped.replace(/(<li>.*<\/li>)/gms, '<ul>$1</ul>');
        // Clean multiple duplicate uls
        escaped = escaped.replace(/<\/ul>\s*<ul>/g, '');
        
        // Paragraph breaks
        escaped = escaped.replace(/\n\n/g, '<br><br>');
        
        return escaped;
    }

    var apiEndpoint = '<?php echo BASE_URL; ?>student/ai_advisor_api.php';
    var chatHistory = [];

    // Trigger Performance State Analysis
    var btnAnalyze = document.getElementById('btnRunAiAnalysis');
    if (btnAnalyze) {
        btnAnalyze.addEventListener('click', function() {
            var initialBox = document.getElementById('aiAnalysisInitial');
            var loadingBox = document.getElementById('aiAnalysisLoading');
            var resultBox  = document.getElementById('aiAnalysisResult');
            var contentBox = document.getElementById('aiAnalysisContent');
            var badgeEl    = document.getElementById('aiAnalysisSourceBadge');

            initialBox.style.display = 'none';
            resultBox.style.display  = 'none';
            loadingBox.style.display = 'block';

            fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'analyze' })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                loadingBox.style.display = 'none';
                if (data.success) {
                    contentBox.innerHTML = renderSimpleMarkdown(data.analysis);
                    badgeEl.textContent = data.source || 'Performance Advisor';
                    resultBox.style.display = 'block';
                } else {
                    initialBox.style.display = 'block';
                    M.toast({ html: 'Analysis error: ' + (data.error || 'Unable to complete analysis'), classes: 'red' });
                }
            })
            .catch(function(err) {
                loadingBox.style.display = 'none';
                initialBox.style.display = 'block';
                M.toast({ html: 'Connection error communicating with advisor.', classes: 'red' });
            });
        });
    }

    // Chat Form Submit
    var chatForm = document.getElementById('aiChatForm');
    var chatInput = document.getElementById('aiChatInput');
    var messagesContainer = document.getElementById('aiChatMessages');

    function appendChatMessage(role, text) {
        var bubble = document.createElement('div');
        bubble.className = 'ai-msg-bubble ' + (role === 'user' ? 'ai-user-msg' : 'ai-assistant-msg');

        var header = document.createElement('div');
        header.className = 'ai-msg-header';
        if (role === 'user') {
            header.innerHTML = '<i class="material-icons tiny">person</i> You';
        } else {
            header.innerHTML = '<i class="material-icons tiny yellow-text text-darken-3">auto_awesome</i> Academic Coach';
        }

        var body = document.createElement('div');
        body.className = 'ai-msg-body';
        body.innerHTML = role === 'user' ? text.replace(/</g, '&lt;').replace(/>/g, '&gt;') : renderSimpleMarkdown(text);

        bubble.appendChild(header);
        bubble.appendChild(body);
        messagesContainer.appendChild(bubble);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    if (chatForm && chatInput) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var message = chatInput.value.trim();
            if (!message) return;

            appendChatMessage('user', message);
            chatInput.value = '';

            // Add typing indicator
            var typingEl = document.createElement('div');
            typingEl.className = 'ai-msg-bubble ai-assistant-msg';
            typingEl.id = 'aiTypingIndicator';
            typingEl.innerHTML = '<em><i class="material-icons tiny">hourglass_empty</i> Coach is typing...</em>';
            messagesContainer.appendChild(typingEl);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'chat',
                    message: message,
                    history: chatHistory
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var ind = document.getElementById('aiTypingIndicator');
                if (ind) ind.remove();

                if (data.success) {
                    appendChatMessage('assistant', data.reply);
                    chatHistory.push({ role: 'user', content: message });
                    chatHistory.push({ role: 'assistant', content: data.reply });
                } else {
                    appendChatMessage('assistant', '⚠️ ' + (data.error || 'Could not process reply.'));
                }
            })
            .catch(function(err) {
                var ind = document.getElementById('aiTypingIndicator');
                if (ind) ind.remove();
                appendChatMessage('assistant', '⚠️ Connection error. Please try again.');
            });
        });

        // Starter Prompt Chips Click
        document.querySelectorAll('.ai-starter-chip').forEach(function(chip) {
            chip.addEventListener('click', function(e) {
                e.preventDefault();
                var promptText = this.getAttribute('data-prompt');
                if (promptText) {
                    chatInput.value = promptText;
                    chatForm.dispatchEvent(new Event('submit'));
                }
            });
        });
    }
});
</script>
