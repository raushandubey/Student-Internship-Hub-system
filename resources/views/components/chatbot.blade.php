{{-- ShreeRam AI - Premium Spiritual + Modern Tech Fusion Chatbot --}}
{{-- Saffron/Bhagwa Theme with Glassmorphism Design --}}

<div id="shreeram-chatbot" class="fixed bottom-6 right-6 z-50">
    {{-- Floating Button with Saffron Glow --}}
    <button 
        id="chatbot-toggle-btn"
        type="button"
        class="shreeram-float-btn group relative flex items-center justify-center rounded-full transition-all duration-500 focus:outline-none"
        aria-label="Open ShreeRam AI"
        aria-expanded="false"
    >
        {{-- Animated Glow Rings --}}
        <span class="absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-40 animate-ping"></span>
        <span class="absolute inline-flex h-[110%] w-[110%] rounded-full bg-gradient-to-r from-orange-500 to-amber-500 opacity-30 animate-pulse"></span>
        
        {{-- Om Symbol --}}
        <span id="chatbot-icon" class="relative z-10 text-4xl om-symbol">🕉️</span>
        
        {{-- Tooltip --}}
        <span class="absolute bottom-full right-0 mb-3 px-4 py-2 bg-gradient-to-r from-orange-600 to-amber-600 text-white text-sm rounded-xl opacity-0 group-hover:opacity-100 transition-all duration-300 whitespace-nowrap pointer-events-none shadow-xl backdrop-blur-sm">
            <span class="font-semibold">ShreeRam AI</span>
            <span class="block text-xs opacity-90">Guiding Your Career Path</span>
        </span>
    </button>

    {{-- Chat Window with Glassmorphism --}}
    <div 
        id="chatbot-window"
        role="dialog"
        aria-label="ShreeRam AI Assistant"
        class="shreeram-chat-window hidden absolute bottom-24 right-0 w-[420px] h-[450px] rounded-3xl flex flex-col overflow-hidden transition-all duration-500"
    >
        {{-- Animated Background Particles --}}
        <div class="shreeram-particles"></div>
        
        {{-- Header with Saffron Gradient --}}
        <div class="shreeram-header relative z-10 px-6 py-5 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                {{-- Om Avatar with Glow --}}
                <div class="shreeram-avatar">
                    <span class="text-2xl">🕉️</span>
                </div>
                <div>
                    <h3 class="text-white font-bold text-xl tracking-wide flex items-center">
                        ShreeRam AI
                        <span class="ml-2 px-2 py-0.5 bg-black bg-opacity-20 rounded-full text-xs font-normal">Beta</span>
                    </h3>
                    <p class="text-orange-100 text-sm flex items-center mt-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse shadow-lg shadow-green-400"></span>
                        Guiding Your Career Path
                    </p>
                </div>
            </div>
            <button 
                id="chatbot-close-btn"
                type="button"
                class="shreeram-close-btn"
                aria-label="Close chat"
            >
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Message List with Dark Glassmorphism --}}
        <div 
            id="chatbot-messages"
            role="log"
            aria-live="polite"
            aria-atomic="false"
            class="overflow-y-auto p-6 space-y-5 shreeram-messages-container"
        >
            {{-- Messages will be dynamically inserted here --}}
        </div>

        {{-- Typing Indicator --}}
        <div id="chatbot-typing" class="hidden px-6 py-4 shreeram-typing-container">
            <div class="flex items-center space-x-3">
                <div class="shreeram-typing-avatar">
                    <span class="text-lg">🕉️</span>
                </div>
                <div class="shreeram-typing-bubble">
                    <div class="flex items-center space-x-2">
                        <div class="w-2.5 h-2.5 rounded-full animate-bounce" style="background-color: #ff7a00; animation-delay: 0ms"></div>
                        <div class="w-2.5 h-2.5 rounded-full animate-bounce" style="background-color: #ff7a00; animation-delay: 200ms"></div>
                        <div class="w-2.5 h-2.5 rounded-full animate-bounce" style="background-color: #ff7a00; animation-delay: 400ms"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area with Dark Theme --}}
        <div class="shreeram-input-container p-5">
            <div class="flex items-end space-x-3">
                <div class="flex-1 relative">
                    <input 
                        id="chatbot-input"
                        type="text"
                        placeholder="Ask ShreeRam AI anything..."
                        maxlength="500"
                        class="shreeram-input"
                        aria-label="Message input"
                    />
                    {{-- Character Count --}}
                    <div id="chatbot-char-count" class="hidden absolute right-4 top-1/2 transform -translate-y-1/2">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-orange-500 bg-opacity-20 text-orange-300 border border-orange-500 border-opacity-30">
                            <span id="chatbot-char-current">0</span>/500
                        </span>
                    </div>
                </div>
                <button 
                    id="chatbot-send-btn"
                    type="button"
                    class="shreeram-send-btn"
                    aria-label="Send message"
                    disabled
                >
                    <i class="fas fa-paper-plane text-lg"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Inject User Profile Data for Personalized AI Responses --}}
@auth
@php
    $user = auth()->user();
    $profile = $user->profile;
    $skills = $profile ? (is_array($profile->skills) ? $profile->skills : []) : [];

    // Calculate profile completion
    $fields = ['name', 'academic_background', 'skills', 'career_interests', 'resume_path', 'aadhaar_number'];
    $completed = 0;
    if ($profile) {
        foreach ($fields as $field) {
            if (!empty($profile->$field)) $completed++;
        }
    }
    $profileCompletion = $profile ? round(($completed / count($fields)) * 100) : 0;

    // Applied jobs count
    $appliedCount = \App\Models\Application::where('user_id', $user->id)->count();

    // Missing skills (career_interests vs skills gap)
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

    // Projects from academic background
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

{{-- Load Chatbot Assets using Vite (automatically resolves correct paths from manifest) --}}
@vite(['public/css/chatbot.css', 'public/js/chatbot.js'])
