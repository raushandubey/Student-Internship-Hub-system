# Requirements Document

## Introduction

This document specifies requirements for upgrading the existing AI Resume Rewrite system in the Laravel-based Internship Platform. The upgrade transforms the current basic resume optimization into a professional ATS (Applicant Tracking System) optimizer that generates recruiter-approved, one-page resumes with accurate scoring, strong bullet points, and clean PDF formatting.

The system currently has functional components (ResumeOptimizationService, ResumePdfService, ResumeScore model, ResumeVersion model) but suffers from inaccurate ATS scores, generic output, formatting issues, and weak bullet points. This upgrade addresses these problems while maintaining backward compatibility with existing database tables and Laravel architecture.

## Glossary

- **ATS_Scoring_Engine**: The component that calculates match scores between resume content and job descriptions
- **Resume_Rewrite_Engine**: The AI-powered component that transforms resume content using OpenAI GPT
- **PDF_Generator**: The component that converts plain-text resume content into formatted PDF documents
- **Chatbot_Interface**: The user-facing conversational interface for resume improvement
- **Resume_Version**: A stored snapshot of resume content (original or AI-rewritten)
- **Resume_Score**: A stored record of calculated match scores with breakdown components
- **Job_Description**: The internship posting containing title, organization, required skills, and description
- **Bullet_Point**: A single line item in the Experience or Projects section describing an achievement or responsibility
- **Action_Verb**: Strong technical verbs used to begin bullet points (Developed, Implemented, Architected, etc.)
- **Measurable_Impact**: Quantified results in bullet points using numbers, percentages, or scale indicators
- **ATS_Format**: Resume structure optimized for Applicant Tracking System parsing (no tables, columns, graphics, or icons)
- **One_Page_Constraint**: Requirement that generated resumes fit within a single page (maximum 600 words)
- **Professional_Summary**: A 2-3 sentence section at the top of the resume tailored to the target job role
- **Technical_Skills_Section**: A section listing programming languages, frameworks, tools, and databases
- **Keyword_Density**: The frequency and coverage of job-required terms within resume content

## Requirements

### Requirement 1: Enhanced ATS Scoring Engine

**User Story:** As a student applying for internships, I want accurate ATS scores that reflect my true match with job requirements, so that I can make informed decisions about which positions to apply for.

#### Acceptance Criteria

1. WHEN a resume is analyzed against a job description, THE ATS_Scoring_Engine SHALL extract all required skills from the Job_Description
2. WHEN a resume is analyzed, THE ATS_Scoring_Engine SHALL extract all contextual keywords from the Job_Description title, description, and required skills
3. WHEN calculating skill match score, THE ATS_Scoring_Engine SHALL compare extracted resume skills against job required skills with case-insensitive matching
4. WHEN calculating keyword score, THE ATS_Scoring_Engine SHALL measure keyword density by comparing job keywords against resume content
5. WHEN calculating experience relevance score, THE ATS_Scoring_Engine SHALL analyze the experience section for job-related keywords and action verbs
6. WHEN calculating project relevance score, THE ATS_Scoring_Engine SHALL analyze the projects section for job-required skills
7. WHEN calculating completeness score, THE ATS_Scoring_Engine SHALL verify presence of required sections (Summary, Skills, Experience, Education, Projects, Contact)
8. THE ATS_Scoring_Engine SHALL compute overall score using weighted components: Skill Match (40%), Keyword Match (25%), Experience Relevance (20%), Project Relevance (10%), Completeness (5%)
9. WHEN overall score is below 60, THE ATS_Scoring_Engine SHALL classify match tier as "low"
10. WHEN overall score is between 60 and 70, THE ATS_Scoring_Engine SHALL classify match tier as "medium"
11. WHEN overall score is above 70, THE ATS_Scoring_Engine SHALL classify match tier as "high"
12. WHEN scoring detects missing required skills, THE ATS_Scoring_Engine SHALL include them in the missing_skills array
13. WHEN scoring detects weak phrasing (worked on, helped with, did some, was responsible, assisted in), THE ATS_Scoring_Engine SHALL flag it as an issue
14. WHEN scoring detects absence of measurable impact indicators, THE ATS_Scoring_Engine SHALL flag it as an issue
15. THE ATS_Scoring_Engine SHALL persist score results to the resume_scores table with all breakdown components

### Requirement 2: Professional Resume Structure Enforcement

**User Story:** As a recruiter reviewing resumes, I want all AI-generated resumes to follow a consistent professional structure, so that I can quickly find relevant information.

#### Acceptance Criteria

1. THE Resume_Rewrite_Engine SHALL generate resumes with sections in this exact order: Name and Contact, Professional Summary, Technical Skills, Experience, Projects, Education, Certifications
2. WHEN generating the Name and Contact section, THE Resume_Rewrite_Engine SHALL include full name, phone, email, and location on separate lines
3. WHEN generating the Professional Summary section, THE Resume_Rewrite_Engine SHALL create 2-3 sentences tailored to the target job role
4. WHEN generating the Professional Summary, THE Resume_Rewrite_Engine SHALL incorporate 2-3 required skills from the Job_Description naturally
5. WHEN generating the Technical Skills section, THE Resume_Rewrite_Engine SHALL group skills by category (Languages, Frameworks, Tools, Databases)
6. WHEN generating the Experience section, THE Resume_Rewrite_Engine SHALL format each entry as: Company Name, Role, Date Range, Location on first line
7. WHEN generating Experience entries, THE Resume_Rewrite_Engine SHALL include maximum 4 bullet points per role
8. WHEN generating the Projects section, THE Resume_Rewrite_Engine SHALL format each entry with project name and tech stack on first line
9. WHEN generating the Education section, THE Resume_Rewrite_Engine SHALL include degree, university, year, and CGPA if present in original
10. WHEN generating the Certifications section, THE Resume_Rewrite_Engine SHALL include it only if certifications exist in the original resume

### Requirement 3: ATS Format Compliance

**User Story:** As a student, I want my resume to pass ATS parsing systems, so that my application reaches human recruiters.

#### Acceptance Criteria

1. THE Resume_Rewrite_Engine SHALL generate resumes that fit within one page only
2. THE Resume_Rewrite_Engine SHALL limit total resume content to maximum 600 words
3. THE Resume_Rewrite_Engine SHALL generate resumes without tables, columns, graphics, or icons
4. THE Resume_Rewrite_Engine SHALL use clean spacing with consistent line breaks between sections
5. WHEN generating bullet points, THE Resume_Rewrite_Engine SHALL limit each bullet to maximum 2 lines
6. THE Resume_Rewrite_Engine SHALL use only approved action verbs (Developed, Built, Implemented, Designed, Architected, Optimized, Deployed, Integrated, Led, Automated, Reduced, Improved, Engineered, Delivered, Streamlined)
7. THE Resume_Rewrite_Engine SHALL remove any PDF binary artifacts, section dashes, or non-text characters from output
8. THE PDF_Generator SHALL use standard fonts (DejaVu Sans or similar) for maximum ATS compatibility
9. THE PDF_Generator SHALL apply consistent margins and spacing throughout the document
10. THE PDF_Generator SHALL ensure the final PDF renders as exactly one page

### Requirement 4: Job-Specific Resume Optimization

**User Story:** As a student applying for a specific internship, I want my resume tailored to that exact job description, so that I maximize my chances of getting an interview.

#### Acceptance Criteria

1. WHEN rewriting a resume, THE Resume_Rewrite_Engine SHALL extract all required skills from the target Job_Description
2. WHEN rewriting a resume, THE Resume_Rewrite_Engine SHALL extract contextual keywords from the job title, description, and requirements
3. WHEN rewriting a resume, THE Resume_Rewrite_Engine SHALL incorporate required skills naturally into Experience and Projects bullet points where truthful
4. THE Resume_Rewrite_Engine SHALL preserve all real experience, companies, dates, and educational institutions from the original resume
5. THE Resume_Rewrite_Engine SHALL NOT fabricate experience, companies, dates, or metrics that do not exist in the original
6. WHEN the original resume lacks specific metrics, THE Resume_Rewrite_Engine SHALL add qualitative impact language without inventing numbers
7. WHEN rewriting the Professional Summary, THE Resume_Rewrite_Engine SHALL reference the target job title and organization
8. WHEN rewriting bullet points, THE Resume_Rewrite_Engine SHALL prioritize experiences and projects most relevant to the target role
9. THE Resume_Rewrite_Engine SHALL ensure all job-required skills appear at least once in the Technical Skills section
10. WHEN a required skill is not present in the original resume, THE Resume_Rewrite_Engine SHALL NOT add it to Experience or Projects sections

### Requirement 5: Bullet Point Enhancement

**User Story:** As a student, I want my resume bullet points to demonstrate technical depth and measurable impact, so that recruiters see my value.

#### Acceptance Criteria

1. WHEN rewriting bullet points, THE Resume_Rewrite_Engine SHALL replace weak verbs (worked on, helped with, did some, was responsible, assisted in) with strong action verbs
2. WHEN rewriting bullet points, THE Resume_Rewrite_Engine SHALL follow the structure: Action Verb + What + Technology + Impact
3. WHEN the original bullet contains measurable metrics, THE Resume_Rewrite_Engine SHALL preserve and emphasize them
4. WHEN the original bullet lacks metrics but describes significant work, THE Resume_Rewrite_Engine SHALL add qualitative impact language (improved performance, enhanced scalability, increased efficiency)
5. WHEN rewriting technical bullet points, THE Resume_Rewrite_Engine SHALL include specific technologies, frameworks, or tools used
6. WHEN rewriting bullet points, THE Resume_Rewrite_Engine SHALL use architecture and engineering terminology (RESTful APIs, microservices, database optimization, CI/CD pipeline)
7. THE Resume_Rewrite_Engine SHALL ensure each bullet point demonstrates technical depth appropriate for the target role
8. WHEN rewriting Experience bullets, THE Resume_Rewrite_Engine SHALL prioritize bullets that align with job requirements
9. WHEN rewriting Projects bullets, THE Resume_Rewrite_Engine SHALL emphasize technical implementation details and scale
10. THE Resume_Rewrite_Engine SHALL limit each bullet point to maximum 2 lines for readability

### Requirement 6: Before and After Score Comparison

**User Story:** As a student, I want to see how much my resume improved after AI optimization, so that I understand the value of the rewrite.

#### Acceptance Criteria

1. WHEN a resume rewrite is requested, THE Resume_Rewrite_Engine SHALL calculate and store the before score using the original resume content
2. WHEN a resume rewrite completes, THE Resume_Rewrite_Engine SHALL calculate the after score using the rewritten resume content
3. WHEN calculating the after score, THE ATS_Scoring_Engine SHALL use the same Job_Description and scoring algorithm as the before score
4. THE Resume_Rewrite_Engine SHALL return both before_score and after_score in the rewrite response
5. THE Resume_Rewrite_Engine SHALL generate an improvements list comparing before and after breakdown components
6. WHEN skill match score increases, THE Resume_Rewrite_Engine SHALL include the percentage increase in the improvements list
7. WHEN keyword score increases, THE Resume_Rewrite_Engine SHALL include the percentage increase in the improvements list
8. WHEN new matching skills are added, THE Resume_Rewrite_Engine SHALL list up to 3 newly added skills in the improvements list
9. WHEN issues are resolved, THE Resume_Rewrite_Engine SHALL report the count of resolved issues in the improvements list
10. THE Resume_Rewrite_Engine SHALL persist the after score to the resume_scores table linked to the new Resume_Version

### Requirement 7: Professional PDF Generation

**User Story:** As a student, I want to download my optimized resume as a clean, professional PDF, so that I can submit it to employers.

#### Acceptance Criteria

1. WHEN a PDF download is requested, THE PDF_Generator SHALL retrieve the latest AI-rewritten Resume_Version for the user and job
2. WHEN generating a PDF, THE PDF_Generator SHALL parse the plain-text resume content into structured sections
3. WHEN generating a PDF, THE PDF_Generator SHALL apply professional typography with consistent font sizes and weights
4. WHEN generating a PDF, THE PDF_Generator SHALL use proper margins (0.5 inch on all sides)
5. WHEN generating a PDF, THE PDF_Generator SHALL apply consistent spacing between sections (1.5x line height)
6. WHEN generating a PDF, THE PDF_Generator SHALL render section headers in bold or slightly larger font
7. WHEN generating a PDF, THE PDF_Generator SHALL render bullet points with proper indentation and bullet symbols
8. WHEN generating a PDF, THE PDF_Generator SHALL ensure the complete resume fits on exactly one page
9. WHEN the PDF is generated, THE PDF_Generator SHALL return a download response with filename format: AI_Optimised_{Name}_{JobTitle}.pdf
10. THE PDF_Generator SHALL use DomPDF library configured for A4 portrait orientation with 150 DPI

### Requirement 8: Chatbot Integration

**User Story:** As a student using the platform chatbot, I want to improve my resume through conversation, so that I can get personalized guidance.

#### Acceptance Criteria

1. WHEN a user types "improve resume" or "optimize resume" in the Chatbot_Interface, THE Chatbot_Interface SHALL prompt the user to select a target internship
2. WHEN a user selects an internship, THE Chatbot_Interface SHALL call the resume analysis endpoint with the internship ID
3. WHEN analysis completes, THE Chatbot_Interface SHALL display the overall score, skill match score, and keyword coverage score
4. WHEN analysis detects missing skills, THE Chatbot_Interface SHALL list up to 4 missing skills in the response
5. WHEN analysis detects issues, THE Chatbot_Interface SHALL list up to 3 issues in the response
6. THE Chatbot_Interface SHALL display the match tier gate message (high/medium/low)
7. WHEN the user confirms they want to rewrite, THE Chatbot_Interface SHALL call the rewrite endpoint
8. WHEN rewrite completes, THE Chatbot_Interface SHALL display the before score, after score, and improvements list
9. WHEN rewrite completes, THE Chatbot_Interface SHALL provide a download link for the PDF
10. WHEN the user clicks the download link, THE Chatbot_Interface SHALL trigger the PDF download endpoint with the version ID

### Requirement 9: Output Quality Validation

**User Story:** As a platform administrator, I want to ensure generated resumes meet professional quality standards, so that students receive valuable output.

#### Acceptance Criteria

1. WHEN a resume is rewritten, THE Resume_Rewrite_Engine SHALL validate that the output contains all required sections (Summary, Skills, Experience, Education)
2. WHEN a resume is rewritten, THE Resume_Rewrite_Engine SHALL validate that the output word count does not exceed 600 words
3. WHEN a resume is rewritten, THE Resume_Rewrite_Engine SHALL validate that all bullet points start with approved action verbs
4. WHEN a resume is rewritten, THE Resume_Rewrite_Engine SHALL validate that no bullet point exceeds 2 lines
5. WHEN a resume is rewritten, THE Resume_Rewrite_Engine SHALL validate that all original company names and dates are preserved
6. WHEN a resume is rewritten, THE Resume_Rewrite_Engine SHALL validate that the Professional Summary mentions the target job title
7. WHEN a resume is rewritten, THE Resume_Rewrite_Engine SHALL validate that the Technical Skills section includes all job-required skills
8. WHEN a PDF is generated, THE PDF_Generator SHALL validate that the output is exactly one page
9. WHEN a PDF is generated, THE PDF_Generator SHALL validate that no content is truncated or cut off
10. IF any validation fails, THE Resume_Rewrite_Engine SHALL log the validation error and return the rule-based fallback output

### Requirement 10: System Integration and Compatibility

**User Story:** As a platform developer, I want the upgrade to integrate seamlessly with existing systems, so that no existing functionality breaks.

#### Acceptance Criteria

1. THE ATS_Scoring_Engine SHALL use the existing resume_scores table schema without modifications
2. THE Resume_Rewrite_Engine SHALL use the existing resume_versions table schema without modifications
3. THE Resume_Rewrite_Engine SHALL maintain backward compatibility with existing Resume_Score model methods (scoreTier, gateMessage, badgeClass)
4. THE Resume_Rewrite_Engine SHALL maintain backward compatibility with existing ResumeVersion model methods (isAiVersion, original, latestAiVersion)
5. THE Resume_Rewrite_Engine SHALL integrate with the existing OpenAI GPT-4o-mini configuration
6. THE PDF_Generator SHALL integrate with the existing DomPDF library configuration
7. THE Resume_Rewrite_Engine SHALL use the existing Profile model resume_path field for PDF text extraction
8. THE Resume_Rewrite_Engine SHALL support both local storage and S3 storage for resume file retrieval
9. THE Chatbot_Interface SHALL use the existing ResumeOptimizerController endpoints without breaking changes
10. THE Resume_Rewrite_Engine SHALL log all operations using the existing Laravel Log facade with appropriate context

### Requirement 11: Resume Text Extraction and Parsing

**User Story:** As a student, I want the system to accurately extract text from my uploaded PDF resume, so that the AI can analyze and improve it.

#### Acceptance Criteria

1. WHEN extracting text from a PDF, THE Resume_Rewrite_Engine SHALL use smalot/pdfparser as the primary extraction method
2. IF smalot/pdfparser extraction yields less than 80 characters, THE Resume_Rewrite_Engine SHALL fall back to BT/ET regex stream extraction
3. WHEN extracting text, THE Resume_Rewrite_Engine SHALL decode PDF escape sequences (\\n, \\r, \\t, \\(, \\), \\\\)
4. WHEN extracting text, THE Resume_Rewrite_Engine SHALL remove PDF binary artifacts (headers, object markers, hex strings)
5. WHEN extracting text, THE Resume_Rewrite_Engine SHALL strip non-printable characters while preserving newlines and tabs
6. WHEN extracting text, THE Resume_Rewrite_Engine SHALL collapse excessive whitespace (3+ newlines to 2, multiple spaces to 1)
7. WHEN the resume is stored on S3, THE Resume_Rewrite_Engine SHALL download it to a temporary file for parsing
8. WHEN the resume is stored locally, THE Resume_Rewrite_Engine SHALL resolve the absolute file path from storage/app/public
9. WHEN text extraction completes, THE Resume_Rewrite_Engine SHALL clean up any temporary files created
10. IF text extraction fails or yields empty content, THE Resume_Rewrite_Engine SHALL return an empty score with appropriate error message

### Requirement 12: AI Prompt Engineering for Quality Output

**User Story:** As a student, I want the AI to generate professional, recruiter-approved resume content, so that my resume stands out to employers.

#### Acceptance Criteria

1. WHEN calling OpenAI, THE Resume_Rewrite_Engine SHALL use GPT-4o-mini model with temperature 0.4 for consistent output
2. WHEN constructing the AI prompt, THE Resume_Rewrite_Engine SHALL include the complete job description (title, organization, location, required skills, description)
3. WHEN constructing the AI prompt, THE Resume_Rewrite_Engine SHALL include the original resume text
4. WHEN constructing the AI prompt, THE Resume_Rewrite_Engine SHALL specify the exact output structure with mandatory section order
5. WHEN constructing the AI prompt, THE Resume_Rewrite_Engine SHALL specify strict rules (one page, no fabrication, strong action verbs, no tables/graphics)
6. WHEN constructing the AI prompt, THE Resume_Rewrite_Engine SHALL provide example transformations (weak bullet to strong bullet)
7. WHEN constructing the AI prompt, THE Resume_Rewrite_Engine SHALL instruct the AI to output only resume text without commentary or markdown headers
8. WHEN constructing the AI prompt, THE Resume_Rewrite_Engine SHALL set max_tokens to 1200 to allow complete one-page resume generation
9. IF the OpenAI API key is not configured, THE Resume_Rewrite_Engine SHALL fall back to rule-based rewrite without error
10. IF the OpenAI call fails or times out, THE Resume_Rewrite_Engine SHALL fall back to rule-based rewrite and log the error

### Requirement 13: Rule-Based Fallback Rewrite

**User Story:** As a student, I want to receive an improved resume even when the AI service is unavailable, so that I can continue my application process.

#### Acceptance Criteria

1. WHEN OpenAI is unavailable, THE Resume_Rewrite_Engine SHALL generate a rule-based rewrite using job description data
2. WHEN generating a rule-based rewrite, THE Resume_Rewrite_Engine SHALL parse the original resume to extract name, contact, skills, experience, education, and projects
3. WHEN generating a rule-based rewrite, THE Resume_Rewrite_Engine SHALL create a Professional Summary mentioning the target job title and organization
4. WHEN generating a rule-based rewrite, THE Resume_Rewrite_Engine SHALL incorporate 2-4 required skills naturally into the Professional Summary
5. WHEN generating a rule-based rewrite, THE Resume_Rewrite_Engine SHALL merge job-required skills with extracted skills in the Technical Skills section
6. WHEN generating a rule-based rewrite, THE Resume_Rewrite_Engine SHALL preserve the original Experience, Projects, and Education sections exactly
7. WHEN generating a rule-based rewrite, THE Resume_Rewrite_Engine SHALL prepend the new Summary and Skills sections to the preserved body content
8. WHEN generating a rule-based rewrite, THE Resume_Rewrite_Engine SHALL ensure the output follows the standard resume structure
9. THE Resume_Rewrite_Engine SHALL sanitize the rule-based output to remove any PDF artifacts before storage
10. THE Resume_Rewrite_Engine SHALL log when rule-based fallback is used with the reason (API key missing or API call failed)

### Requirement 14: Resume Version Management

**User Story:** As a student, I want to track different versions of my resume for each job application, so that I can see my optimization history.

#### Acceptance Criteria

1. WHEN a resume is first analyzed for a job, THE Resume_Rewrite_Engine SHALL create an original Resume_Version snapshot if it does not exist
2. WHEN creating an original snapshot, THE Resume_Rewrite_Engine SHALL set type to "original" and version_number to 1
3. WHEN a resume rewrite completes, THE Resume_Rewrite_Engine SHALL create a new Resume_Version with type "ai_rewrite"
4. WHEN creating an AI rewrite version, THE Resume_Rewrite_Engine SHALL increment version_number based on the maximum existing version for that user and job
5. WHEN creating a Resume_Version, THE Resume_Rewrite_Engine SHALL store the plain-text resume content in the content field
6. WHEN creating a Resume_Version, THE Resume_Rewrite_Engine SHALL store the score at creation time in the score_at_creation field
7. WHEN creating a Resume_Version, THE Resume_Rewrite_Engine SHALL set a descriptive label (e.g., "Original" or "AI Optimized — v2")
8. WHEN creating a Resume_Version, THE Resume_Rewrite_Engine SHALL sanitize the content to remove PDF binary artifacts before storage
9. THE Resume_Rewrite_Engine SHALL link each Resume_Version to the user_id and internship_id
10. WHEN retrieving a resume for PDF generation, THE PDF_Generator SHALL fetch the latest AI-rewritten Resume_Version by version_number

### Requirement 15: Error Handling and Logging

**User Story:** As a platform developer, I want comprehensive error handling and logging, so that I can diagnose and fix issues quickly.

#### Acceptance Criteria

1. WHEN PDF text extraction fails, THE Resume_Rewrite_Engine SHALL log the error with profile_id and error message
2. WHEN OpenAI API call fails, THE Resume_Rewrite_Engine SHALL log the error with user_id, internship_id, and error message
3. WHEN PDF generation fails, THE PDF_Generator SHALL log the error with user_id, internship_id, and error message
4. WHEN a resume analysis completes successfully, THE Resume_Rewrite_Engine SHALL log the operation with user_id, internship_id, and overall score
5. WHEN a resume rewrite completes successfully, THE Resume_Rewrite_Engine SHALL log the operation with user_id, internship_id, before_score, after_score, and version_id
6. WHEN a PDF is generated successfully, THE PDF_Generator SHALL log the operation with user_id, internship_id, version_id, and filename
7. IF a user has no resume uploaded, THE Resume_Rewrite_Engine SHALL return an error response with message "No resume uploaded. Please upload your resume from your profile page."
8. IF a resume file is not found on disk or S3, THE Resume_Rewrite_Engine SHALL log a warning and return an error response
9. IF PDF text extraction yields empty content, THE Resume_Rewrite_Engine SHALL return an error response with message "Unable to read your resume. Please ensure it is a valid PDF."
10. IF a Resume_Version is not found for PDF download, THE PDF_Generator SHALL return a 404 error with message "Resume version not found. Please run the AI rewrite first."

### Requirement 16: Performance and Scalability

**User Story:** As a platform user, I want resume analysis and rewriting to complete quickly, so that I can apply to jobs without delays.

#### Acceptance Criteria

1. WHEN analyzing a resume, THE ATS_Scoring_Engine SHALL complete scoring within 2 seconds for resumes up to 5 pages
2. WHEN rewriting a resume, THE Resume_Rewrite_Engine SHALL complete the OpenAI API call within 15 seconds
3. WHEN generating a PDF, THE PDF_Generator SHALL complete rendering within 3 seconds
4. THE ATS_Scoring_Engine SHALL perform all scoring calculations locally without external API calls
5. THE Resume_Rewrite_Engine SHALL reuse the OpenAI client instance across multiple requests within the same process
6. THE PDF_Generator SHALL parse resume text efficiently using regex patterns with maximum 2000 character windows
7. WHEN extracting PDF text, THE Resume_Rewrite_Engine SHALL limit fallback regex extraction to the first 10,000 characters
8. THE Resume_Rewrite_Engine SHALL cache the OpenAI client instance as a private property to avoid repeated initialization
9. THE PDF_Generator SHALL limit section parsing to maximum 5 experience entries, 4 projects, and 3 education entries
10. THE Resume_Rewrite_Engine SHALL set OpenAI max_tokens to 1200 to balance output completeness with response time

### Requirement 17: Testing and Validation Endpoints

**User Story:** As a platform developer, I want to test the resume optimization system, so that I can verify it works correctly before deployment.

#### Acceptance Criteria

1. THE ResumeOptimizerController SHALL expose a GET endpoint /resume-optimizer/score/{internship} for resume analysis
2. THE ResumeOptimizerController SHALL expose a POST endpoint /resume-optimizer/rewrite/{internship} for AI rewrite
3. THE ResumeOptimizerController SHALL expose a GET endpoint /resume-optimizer/download/{internship} for PDF download
4. THE ResumeOptimizerController SHALL expose a GET endpoint /resume-optimizer/chatbot/analyse for chatbot integration
5. THE ResumeOptimizerController SHALL expose a GET endpoint /resume-optimizer/internships for active internships list
6. WHEN the score endpoint is called, THE ResumeOptimizerController SHALL return JSON with success flag and score data
7. WHEN the rewrite endpoint is called, THE ResumeOptimizerController SHALL return JSON with success flag, before_score, after_score, improvements, and version_id
8. WHEN the download endpoint is called, THE ResumeOptimizerController SHALL return a PDF file download response
9. WHEN any endpoint encounters an error, THE ResumeOptimizerController SHALL return JSON with success: false and error message
10. THE ResumeOptimizerController SHALL require authentication for all endpoints using Laravel Auth middleware

