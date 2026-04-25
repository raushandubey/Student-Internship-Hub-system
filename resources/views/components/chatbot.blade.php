{{-- ShreeRam AI — Premium Spiritual + Modern Tech Fusion Chatbot --}}
{{-- Mobile-safe: z-50 (above bottom-nav z-40), bottom offset accounts for nav height --}}

<div id="shreeram-chatbot" class="fixed z-50" style="bottom: calc(72px + env(safe-area-inset-bottom, 0px)); right: 1rem;">

    {{-- Floating Toggle Button --}}
    <button
        id="chatbot-toggle-btn"
        type="button"
        class="shreeram-float-btn group relative flex items-center justify-center rounded-full transition-all duration-500 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2"
        aria-label="Open ShreeRam AI"
        aria-expanded="false"
        aria-controls="chatbot-window"
    >
        {{-- Animated ping ring --}}
        <span class="absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-30 animate-ping pointer-events-none"></span>
        <span class="absolute inline-flex h-[115%] w-[115%] rounded-full bg-gradient-to-r from-orange-500 to-amber-500 opacity-20 animate-pulse pointer-events-none"></span>

        {{-- Om icon --}}
        <span id="chatbot-icon" class="relative z-10 text-3xl om-symbol leading-none">🕉️</span>

        {{-- Tooltip (desktop only) --}}
        <span class="hidden md:block absolute bottom-full right-0 mb-3 px-3 py-1.5 bg-gradient-to-r from-orange-600 to-amber-600 text-white text-xs rounded-xl opacity-0 group-hover:opacity-100 transition-all duration-300 whitespace-nowrap pointer-events-none shadow-xl">
            <span class="font-semibold block">ShreeRam AI</span>
            <span class="opacity-90">Guiding Your Career Path</span>
        </span>
    </button>

    {{-- Chat Window --}}
    <div
        id="chatbot-window"
        role="dialog"
        aria-label="ShreeRam AI Assistant"
        aria-modal="true"
        class="shreeram-chat-window hidden flex-col overflow-hidden transition-all duration-400 rounded-3xl"
        style="
            position: fixed;
            bottom: calc(144px + env(safe-area-inset-bottom, 0px));
            right: 1rem;
            left: 1rem;
            max-width: 420px;
            width: calc(100vw - 2rem);
            height: min(500px, calc(100vh - 200px));
            z-index: 50;
        "
    >
        {{-- Animated Background --}}
        <div class="shreeram-particles pointer-events-none"></div>

        {{-- Header --}}
        <div class="shreeram-header relative z-10 px-4 py-4 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="shreeram-avatar flex-shrink-0">
                    <span class="text-xl">🕉️</span>
                </div>
                <div>
                    <h3 class="text-white font-bold text-base tracking-wide flex items-center gap-2">
                        ShreeRam AI
                        <span class="px-1.5 py-0.5 bg-black bg-opacity-20 rounded-full text-[10px] font-normal">Beta</span>
                    </h3>
                    <p class="text-orange-100 text-xs flex items-center mt-0.5">
                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5 animate-pulse shadow-sm shadow-green-400 flex-shrink-0"></span>
                        Guiding Your Career Path
                    </p>
                </div>
            </div>
            <button
                id="chatbot-close-btn"
                type="button"
                class="shreeram-close-btn flex-shrink-0 ml-2"
                aria-label="Close chat"
            >
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        {{-- Message List --}}
        <div
            id="chatbot-messages"
            role="log"
            aria-live="polite"
            aria-atomic="false"
            class="flex-1 overflow-y-auto px-4 py-3 space-y-4 shreeram-messages-container overscroll-contain"
            style="-webkit-overflow-scrolling: touch;"
        >
            {{-- Messages injected by chatbot.js --}}
        </div>

        {{-- Typing Indicator --}}
        <div id="chatbot-typing" class="hidden px-4 py-3 shreeram-typing-container flex-shrink-0">
            <div class="flex items-center space-x-2">
                <div class="shreeram-typing-avatar">
                    <span class="text-base">🕉️</span>
                </div>
                <div class="shreeram-typing-bubble">
                    <div class="flex items-center space-x-1.5">
                        <div class="w-2 h-2 rounded-full animate-bounce" style="background-color: #ff7a00; animation-delay: 0ms"></div>
                        <div class="w-2 h-2 rounded-full animate-bounce" style="background-color: #ff7a00; animation-delay: 200ms"></div>
                        <div class="w-2 h-2 rounded-full animate-bounce" style="background-color: #ff7a00; animation-delay: 400ms"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="shreeram-input-container px-3 py-3 flex-shrink-0">
            <div class="flex items-end gap-2">
                <div class="flex-1 relative min-w-0">
                    <input
                        id="chatbot-input"
                        type="text"
                        placeholder="Ask ShreeRam AI anything..."
                        maxlength="500"
                        class="shreeram-input w-full"
                        aria-label="Message input"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="sentences"
                    />
                    {{-- Character count --}}
                    <div id="chatbot-char-count" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-orange-500 bg-opacity-20 text-orange-300 border border-orange-500 border-opacity-30">
                            <span id="chatbot-char-current">0</span>/500
                        </span>
                    </div>
                </div>
                <button
                    id="chatbot-send-btn"
                    type="button"
                    class="shreeram-send-btn flex-shrink-0"
                    aria-label="Send message"
                    disabled
                >
                    <i class="fas fa-paper-plane text-base"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- On desktop (md+), reset to original corner positioning --}}
<style>
@media (min-width: 768px) {
    #shreeram-chatbot {
        bottom: 1.5rem !important;
    }

    #chatbot-window {
        position: absolute !important;
        bottom: 5rem !important;
        right: 0 !important;
        left: auto !important;
        width: 420px !important;
        max-width: 420px !important;
        height: 480px !important;
    }
}
</style>

{{-- Inject User Profile Data for Personalized AI Responses --}}
@auth
@php
    $user = auth()->user();
    $profile = $user->profile;
    $skills = $profile ? (is_array($profile->skills) ? $profile->skills : []) : [];

    $fields = ['name', 'academic_background', 'skills', 'career_interests', 'resume_path', 'aadhaar_number'];
    $completed = 0;
    if ($profile) {
        foreach ($fields as $field) {
            if (!empty($profile->$field)) $completed++;
        }
    }
    $profileCompletion = $profile ? round(($completed / count($fields)) * 100) : 0;
    $appliedCount = \App\Models\Application::where('user_id', $user->id)->count();
    $careerInterests = $profile ? ($profile->career_interests ?? '') : '';
    $missingSkills = [];
    $commonTechSkills = ['python', 'javascript', 'react', 'node.js', 'sql', 'java', 'machine learning', 'data analysis', 'git', 'docker', 'aws', 'communication', 'teamwork'];
    foreach ($commonTechSkills as $techSkill) {
        $hasSkill = false;
        foreach ($skills as $s) {
            if (stripos($s, $techSkill) !== false) { $hasSkill = true; break; }
        }
        if (!$hasSkill) $missingSkills[] = $techSkill;
    }
    $missingSkills = array_slice($missingSkills, 0, 5);
    $projects = $profile ? ($profile->academic_background ?? '') : '';
@endphp
<script>
    window.chatbotUserProfile = {
        name: @json($user->name),
        skills: @json($skills),
        missingSkills: @json($missingSkills),
        profileCompletion: {{ $profileCompletion }},
        appliedJobsCount: {{ $appliedCount }},
        careerInterests: @json($careerInterests),
        academicBackground: @json($projects),
        hasResume: {{ $profile && $profile->resume_path ? 'true' : 'false' }},
    };
</script>
@endauth

{{-- Load Chatbot Assets --}}
@vite(['public/css/chatbot.css', 'public/js/chatbot.js'])
