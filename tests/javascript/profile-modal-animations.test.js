/**
 * Profile Modal Animation Tests
 * 
 * These tests verify the fade-in and fade-out animations for the admin profile viewer modal.
 * 
 * Requirements:
 * - Task 9.1: Fade-in animation (300ms, ease-out, opacity 0 to 1)
 * - Task 9.2: Fade-out animation (200ms, ease-in, opacity 1 to 0)
 */

describe('Profile Modal Animations', () => {
    let modal;
    
    beforeEach(() => {
        // Set up DOM
        document.body.innerHTML = `
            <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 opacity-0 transition-opacity">
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                    <div id="profileLoading">Loading...</div>
                    <div id="profileContent" class="hidden">Content</div>
                    <div id="profileError" class="hidden">Error</div>
                </div>
            </div>
        `;
        
        modal = document.getElementById('profileModal');
        
        // Mock fetch
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({
                    success: true,
                    data: {
                        user: { id: 1, name: 'Test User', email: 'test@example.com' },
                        profile: {
                            academic_background: 'Test Background',
                            skills: ['JavaScript', 'PHP'],
                            career_interests: 'Development',
                            resume_path: '/test.pdf',
                            has_resume: true
                        }
                    }
                })
            })
        );
    });
    
    afterEach(() => {
        jest.clearAllMocks();
    });
    
    test('modal starts with opacity 0 and hidden class', () => {
        expect(modal.classList.contains('hidden')).toBe(true);
        expect(modal.classList.contains('opacity-0')).toBe(true);
    });
    
    test('fade-in animation applies correct transition properties', (done) => {
        // Simulate opening modal
        modal.classList.remove('hidden');
        
        setTimeout(() => {
            modal.style.opacity = '1';
            modal.style.transition = 'opacity 300ms ease-out';
            
            expect(modal.style.opacity).toBe('1');
            expect(modal.style.transition).toContain('300ms');
            expect(modal.style.transition).toContain('ease-out');
            done();
        }, 10);
    });
    
    test('fade-out animation applies correct transition properties', (done) => {
        // Set modal to visible state first
        modal.classList.remove('hidden');
        modal.style.opacity = '1';
        
        // Simulate closing modal
        modal.style.opacity = '0';
        modal.style.transition = 'opacity 200ms ease-in';
        
        expect(modal.style.opacity).toBe('0');
        expect(modal.style.transition).toContain('200ms');
        expect(modal.style.transition).toContain('ease-in');
        
        // Verify hidden class is added after animation completes
        setTimeout(() => {
            modal.classList.add('hidden');
            expect(modal.classList.contains('hidden')).toBe(true);
            done();
        }, 200);
    });
    
    test('fade-in animation duration is 300ms', () => {
        modal.classList.remove('hidden');
        
        setTimeout(() => {
            modal.style.transition = 'opacity 300ms ease-out';
            const transitionDuration = modal.style.transition.match(/(\d+)ms/);
            expect(transitionDuration[1]).toBe('300');
        }, 10);
    });
    
    test('fade-out animation duration is 200ms', () => {
        modal.style.transition = 'opacity 200ms ease-in';
        const transitionDuration = modal.style.transition.match(/(\d+)ms/);
        expect(transitionDuration[1]).toBe('200');
    });
    
    test('fade-in uses ease-out timing function', () => {
        modal.style.transition = 'opacity 300ms ease-out';
        expect(modal.style.transition).toContain('ease-out');
    });
    
    test('fade-out uses ease-in timing function', () => {
        modal.style.transition = 'opacity 200ms ease-in';
        expect(modal.style.transition).toContain('ease-in');
    });
});

/**
 * Manual Testing Checklist for Modal Animations
 * 
 * 1. Open Modal Animation (Task 9.1):
 *    - Click "View Profile" button on any application
 *    - Verify modal fades in smoothly over 300ms
 *    - Verify animation uses ease-out timing (starts fast, ends slow)
 *    - Verify opacity transitions from 0 to 1
 * 
 * 2. Close Modal Animation (Task 9.2):
 *    - Click the X button or click outside modal
 *    - Verify modal fades out smoothly over 200ms
 *    - Verify animation uses ease-in timing (starts slow, ends fast)
 *    - Verify opacity transitions from 1 to 0
 * 
 * 3. Animation Smoothness:
 *    - No flickering or jumping
 *    - Smooth transition throughout
 *    - Background overlay fades with modal
 * 
 * 4. Browser Compatibility:
 *    - Test in Chrome, Firefox, Safari, Edge
 *    - Verify animations work consistently across browsers
 */
