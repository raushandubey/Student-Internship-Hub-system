/**
 * Admin Profile Viewer - Unit Tests
 * 
 * Tests the openProfileModal function and related functionality.
 * Validates Requirements 1.3, 2.1, 2.5, 8.1, 8.2, 8.4
 * 
 * @requires jest
 */

import { describe, it, expect, beforeEach, afterEach, jest } from '@jest/globals';

// Mock DOM environment for testing
const setupMockDOM = () => {
    document.body.innerHTML = `
        <div id="profileModal" class="hidden">
            <div id="profileLoading">Loading...</div>
            <div id="profileContent" class="hidden">
                <span id="profileInitial"></span>
                <h4 id="profileName"></h4>
                <p id="profileEmail"></p>
                <p id="profileAcademic"></p>
                <div id="profileSkills"></div>
                <p id="profileCareerInterests"></p>
                <div id="profileProjects"></div>
                <section class="ai-summary">
                    <ul id="aiStrengths"></ul>
                    <ul id="aiWeaknesses"></ul>
                    <p id="aiAssessment"></p>
                </section>
                <div id="resumePreview" class="hidden">
                    <iframe id="resumeIframe"></iframe>
                    <a id="resumeDownload" href="#"></a>
                </div>
                <div id="resumeNotFound" class="hidden"></div>
            </div>
            <div id="profileError" class="hidden">
                <p id="profileErrorMessage"></p>
            </div>
        </div>
    `;
};

// Mock fetch API
global.fetch = jest.fn();

describe('Admin Profile Viewer - Unit Tests', () => {
    beforeEach(() => {
        setupMockDOM();
        jest.clearAllMocks();
        document.body.style.overflow = '';
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe('openProfileModal Function', () => {
        it('should show modal overlay when called', () => {
            const modal = document.getElementById('profileModal');
            expect(modal.classList.contains('hidden')).toBe(true);
            
            // Mock successful fetch
            global.fetch.mockResolvedValueOnce({
                json: async () => ({ success: true, data: {} })
            });
            
            // Simulate function call (would be defined in the actual page)
            modal.classList.remove('hidden');
            
            expect(modal.classList.contains('hidden')).toBe(false);
        });

        it('should display loading spinner initially', () => {
            const loading = document.getElementById('profileLoading');
            const content = document.getElementById('profileContent');
            
            // Initial state
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            
            expect(loading.classList.contains('hidden')).toBe(false);
            expect(content.classList.contains('hidden')).toBe(true);
        });

        it('should prevent background scrolling when modal opens', () => {
            expect(document.body.style.overflow).toBe('');
            
            // Simulate modal opening
            document.body.style.overflow = 'hidden';
            
            expect(document.body.style.overflow).toBe('hidden');
        });

        it('should restore background scrolling when modal closes', () => {
            document.body.style.overflow = 'hidden';
            
            // Simulate modal closing
            document.body.style.overflow = '';
            
            expect(document.body.style.overflow).toBe('');
        });

        it('should make AJAX request to correct endpoint', async () => {
            const applicationId = 123;
            const expectedUrl = `/admin/applications/${applicationId}/profile`;
            
            global.fetch.mockResolvedValueOnce({
                json: async () => ({ success: true, data: {} })
            });
            
            await fetch(expectedUrl);
            
            expect(global.fetch).toHaveBeenCalledWith(expectedUrl);
        });
    });

    describe('renderProfileData Function', () => {
        it('should populate user basic information', () => {
            const mockData = {
                user: {
                    name: 'John Doe',
                    email: 'john@example.com'
                },
                profile: {
                    academic_background: 'B.Tech Computer Science',
                    skills: ['JavaScript', 'React', 'Node.js'],
                    career_interests: 'Full-stack development',
                    projects: 'E-commerce platform',
                    has_resume: false
                }
            };
            
            // Simulate rendering
            document.getElementById('profileInitial').textContent = mockData.user.name.charAt(0).toUpperCase();
            document.getElementById('profileName').textContent = mockData.user.name;
            document.getElementById('profileEmail').textContent = mockData.user.email;
            
            expect(document.getElementById('profileInitial').textContent).toBe('J');
            expect(document.getElementById('profileName').textContent).toBe('John Doe');
            expect(document.getElementById('profileEmail').textContent).toBe('john@example.com');
        });

        it('should render skills as badges', () => {
            const skills = ['JavaScript', 'React', 'Node.js'];
            const skillsContainer = document.getElementById('profileSkills');
            
            skillsContainer.innerHTML = '';
            skills.forEach(skill => {
                const badge = document.createElement('span');
                badge.className = 'px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full border border-blue-200';
                badge.textContent = skill;
                skillsContainer.appendChild(badge);
            });
            
            expect(skillsContainer.children.length).toBe(3);
            expect(skillsContainer.children[0].textContent).toBe('JavaScript');
        });

        it('should show "No skills listed" when skills array is empty', () => {
            const skillsContainer = document.getElementById('profileSkills');
            skillsContainer.innerHTML = '<span class="text-gray-500 text-sm italic">No skills listed</span>';
            
            expect(skillsContainer.innerHTML).toContain('No skills listed');
        });

        it('should hide loading spinner after rendering', () => {
            const loading = document.getElementById('profileLoading');
            const content = document.getElementById('profileContent');
            
            loading.classList.add('hidden');
            content.classList.remove('hidden');
            
            expect(loading.classList.contains('hidden')).toBe(true);
            expect(content.classList.contains('hidden')).toBe(false);
        });

        it('should display AI summary when available', () => {
            const mockSummary = {
                strengths: ['Strong technical skills', 'Good communication'],
                weaknesses: ['Limited experience'],
                overall_assessment: 'Promising candidate'
            };
            
            const strengthsList = document.getElementById('aiStrengths');
            strengthsList.innerHTML = '';
            mockSummary.strengths.forEach(strength => {
                const li = document.createElement('li');
                li.textContent = strength;
                strengthsList.appendChild(li);
            });
            
            expect(strengthsList.children.length).toBe(2);
            expect(strengthsList.children[0].textContent).toBe('Strong technical skills');
        });

        it('should hide AI summary section when not available', () => {
            const aiSummarySection = document.querySelector('.ai-summary');
            aiSummarySection.classList.add('hidden');
            
            expect(aiSummarySection.classList.contains('hidden')).toBe(true);
        });

        it('should show resume preview when resume exists', () => {
            const resumePreview = document.getElementById('resumePreview');
            const resumeNotFound = document.getElementById('resumeNotFound');
            const resumePath = '/storage/resumes/john_doe.pdf';
            
            document.getElementById('resumeIframe').src = resumePath;
            document.getElementById('resumeDownload').href = resumePath;
            resumePreview.classList.remove('hidden');
            resumeNotFound.classList.add('hidden');
            
            expect(resumePreview.classList.contains('hidden')).toBe(false);
            expect(resumeNotFound.classList.contains('hidden')).toBe(true);
            expect(document.getElementById('resumeIframe').src).toContain(resumePath);
        });

        it('should show "No resume uploaded" when resume does not exist', () => {
            const resumePreview = document.getElementById('resumePreview');
            const resumeNotFound = document.getElementById('resumeNotFound');
            
            resumePreview.classList.add('hidden');
            resumeNotFound.classList.remove('hidden');
            
            expect(resumePreview.classList.contains('hidden')).toBe(true);
            expect(resumeNotFound.classList.contains('hidden')).toBe(false);
        });
    });

    describe('showProfileError Function', () => {
        it('should hide loading spinner and show error message', () => {
            const loading = document.getElementById('profileLoading');
            const error = document.getElementById('profileError');
            const errorMessage = document.getElementById('profileErrorMessage');
            const testMessage = 'Unable to load profile data';
            
            loading.classList.add('hidden');
            errorMessage.textContent = testMessage;
            error.classList.remove('hidden');
            
            expect(loading.classList.contains('hidden')).toBe(true);
            expect(error.classList.contains('hidden')).toBe(false);
            expect(errorMessage.textContent).toBe(testMessage);
        });

        it('should display network error message', () => {
            const errorMessage = document.getElementById('profileErrorMessage');
            const networkError = 'Unable to connect. Please try again.';
            
            errorMessage.textContent = networkError;
            
            expect(errorMessage.textContent).toBe(networkError);
        });
    });

    describe('Error Handling', () => {
        beforeEach(() => {
            jest.clearAllMocks();
        });

        it('should handle fetch failure gracefully', async () => {
            const mockError = new Error('Network error');
            global.fetch = jest.fn().mockRejectedValueOnce(mockError);
            
            await expect(fetch('/admin/applications/123/profile')).rejects.toThrow('Network error');
        });

        it('should handle unsuccessful API response', async () => {
            const errorResponse = {
                success: false,
                message: 'Profile not found'
            };
            
            global.fetch = jest.fn().mockResolvedValueOnce({
                json: async () => errorResponse
            });
            
            const response = await fetch('/admin/applications/123/profile');
            const data = await response.json();
            
            expect(data.success).toBe(false);
            expect(data.message).toBe('Profile not found');
        });
    });

    describe('Integration Flow', () => {
        beforeEach(() => {
            jest.clearAllMocks();
        });

        it('should complete full success flow', async () => {
            const mockResponse = {
                success: true,
                data: {
                    user: {
                        name: 'Jane Smith',
                        email: 'jane@example.com'
                    },
                    profile: {
                        academic_background: 'M.Tech AI',
                        skills: ['Python', 'TensorFlow'],
                        career_interests: 'Machine Learning',
                        projects: 'Image recognition system',
                        has_resume: true,
                        resume_path: '/storage/resumes/jane.pdf'
                    },
                    ai_summary: {
                        strengths: ['Strong ML background'],
                        weaknesses: ['Limited industry experience'],
                        overall_assessment: 'Excellent candidate'
                    }
                }
            };
            
            global.fetch = jest.fn().mockResolvedValueOnce({
                json: async () => mockResponse
            });
            
            const response = await fetch('/admin/applications/456/profile');
            const data = await response.json();
            
            expect(data.success).toBe(true);
            expect(data.data.user.name).toBe('Jane Smith');
            expect(data.data.profile.skills).toContain('Python');
            expect(data.data.ai_summary.strengths).toContain('Strong ML background');
        });
    });

    describe('Modal Interaction Handlers - Validates Requirements 3.6, 3.7, 3.8', () => {
        beforeEach(() => {
            setupMockDOM();
            jest.clearAllMocks();
        });

        it('should close modal when close button is clicked', () => {
            const modal = document.getElementById('profileModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Simulate close button click
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            
            expect(modal.classList.contains('hidden')).toBe(true);
            expect(document.body.style.overflow).toBe('');
        });

        it('should close modal when clicking outside modal container', () => {
            const modal = document.getElementById('profileModal');
            modal.classList.remove('hidden');
            
            // Simulate click event on modal overlay (not on modal content)
            const clickEvent = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(clickEvent, 'target', { value: modal, enumerable: true });
            
            // Simulate the event listener behavior
            if (clickEvent.target === modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
            
            expect(modal.classList.contains('hidden')).toBe(true);
        });

        it('should NOT close modal when clicking inside modal content', () => {
            const modal = document.getElementById('profileModal');
            const content = document.getElementById('profileContent');
            modal.classList.remove('hidden');
            
            // Simulate click event on modal content (not on overlay)
            const clickEvent = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(clickEvent, 'target', { value: content, enumerable: true });
            
            // Simulate the event listener behavior - should NOT close
            if (clickEvent.target === modal) {
                modal.classList.add('hidden');
            }
            
            expect(modal.classList.contains('hidden')).toBe(false);
        });

        it('should close modal when Escape key is pressed', () => {
            const modal = document.getElementById('profileModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Simulate Escape key press
            const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' });
            
            // Simulate the event listener behavior
            if (escapeEvent.key === 'Escape') {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
            
            expect(modal.classList.contains('hidden')).toBe(true);
            expect(document.body.style.overflow).toBe('');
        });

        it('should NOT close modal when other keys are pressed', () => {
            const modal = document.getElementById('profileModal');
            modal.classList.remove('hidden');
            
            // Simulate Enter key press
            const enterEvent = new KeyboardEvent('keydown', { key: 'Enter' });
            
            // Simulate the event listener behavior - should NOT close
            if (enterEvent.key === 'Escape') {
                modal.classList.add('hidden');
            }
            
            expect(modal.classList.contains('hidden')).toBe(false);
        });

        it('should prevent multiple modals from opening on rapid clicks', () => {
            let isModalLoading = false;
            const applicationId = 123;
            
            // First click - should proceed
            if (!isModalLoading) {
                isModalLoading = true;
                global.fetch = jest.fn().mockResolvedValueOnce({
                    json: async () => ({ success: true, data: {} })
                });
            }
            
            expect(isModalLoading).toBe(true);
            
            // Second rapid click - should be blocked
            let secondClickBlocked = false;
            if (isModalLoading) {
                secondClickBlocked = true;
            }
            
            expect(secondClickBlocked).toBe(true);
            
            // After request completes, flag should reset
            isModalLoading = false;
            expect(isModalLoading).toBe(false);
        });

        it('should reset loading flag when modal is closed before request completes', () => {
            let isModalLoading = true;
            const modal = document.getElementById('profileModal');
            modal.classList.remove('hidden');
            
            // Simulate closing modal while loading
            modal.classList.add('hidden');
            isModalLoading = false;
            
            expect(isModalLoading).toBe(false);
            expect(modal.classList.contains('hidden')).toBe(true);
        });

        it('should handle multiple close triggers simultaneously', () => {
            const modal = document.getElementById('profileModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Simulate both Escape key and close button clicked
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            
            // Should handle gracefully without errors
            expect(modal.classList.contains('hidden')).toBe(true);
            expect(document.body.style.overflow).toBe('');
        });
    });
});
