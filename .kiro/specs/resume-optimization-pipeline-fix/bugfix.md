# Bugfix Requirements Document

## Introduction

The AI-powered resume optimization pipeline in this Laravel-based internship platform suffers from critical architectural failures across 8 interconnected subsystems. The system is designed to accept candidate resumes, analyze them against job descriptions, optimize content using AI providers (OpenAI/Anthropic/OpenRouter), generate ATS-friendly PDFs via LaTeX, provide skill matching recommendations, and orchestrate workflows via n8n webhooks. However, the current implementation exhibits systematic failures in semantic analysis, identity preservation, output validation, scoring accuracy, PDF rendering, production API reliability, orchestration stability, and content quality detection. This bugfix addresses the complete architectural reconstruction of the resume optimization pipeline to restore functional integrity across all subsystems.

## Bug Analysis

### Current Behavior (Defect)

#### 1. Semantic Skill Matching Failures

1.1 WHEN a resume contains semantically equivalent skills (e.g., "debugging", "architecture", "REST APIs") THEN the system incorrectly marks related skills (e.g., "problem solving") as "missing" in the UI

1.2 WHEN the skill matching engine compares resume skills to job requirements THEN the system uses primitive exact-match keyword checking instead of semantic inference

1.3 WHEN synonymous or contextually equivalent skills exist in the resume THEN the system generates false negative skill gap reports

#### 2. Identity Corruption During Optimization

1.4 WHEN a Backend Engineer or Full Stack Engineer resume is submitted for optimization THEN the system transforms the professional identity into unrelated roles (e.g., "UI Designer Intern")

1.5 WHEN the AI optimization process rewrites resume content THEN the system fails to enforce identity preservation constraints

1.6 WHEN career domain information is present in the original resume THEN the system allows complete professional identity destruction during rewrite

#### 3. Malformed AI Outputs

1.7 WHEN the AI provider returns resume optimization results THEN the system receives markdown code block wrappers (```json...```) instead of pure JSON

1.8 WHEN the AI response is parsed THEN the system encounters invalid JSON structures, malformed arrays, and schema violations

1.9 WHEN AI output validation fails THEN the system throws AI_OUTPUT_VALIDATION_FAILED errors and crashes the pipeline

#### 4. Fake ATS Score Engine

1.10 WHEN a resume is optimized THEN the ATS score remains static at 69% before and after optimization

1.11 WHEN the scoring engine compares original and optimized resumes THEN the system uses cached scores or ignores the optimized resume entirely

1.12 WHEN users view optimization results THEN the system displays identical before/after scores, making the optimization appear non-functional

#### 5. LaTeX/PDF Rendering Failures

1.13 WHEN the system generates PDF output from optimized resumes THEN the resulting PDFs contain broken spacing, giant whitespace blocks, and malformed bullet points

1.14 WHEN the AI generates LaTeX formatting and layout logic directly THEN the system produces layout corruption and unprofessional document structure

1.15 WHEN PDFs are generated THEN the system creates documents that trigger antivirus detection or appear invalid

#### 6. Production API Failures

1.16 WHEN the system runs in production environments THEN OpenAI, OpenRouter, LaTeX API, and n8n webhook calls fail despite working correctly in local development

1.17 WHEN API requests are made in production THEN the system encounters unknown failures related to environment variables, config caching, SSL verification, timeouts, or permissions

1.18 WHEN production API errors occur THEN the system lacks comprehensive logging and error diagnostics to identify root causes

#### 7. N8N Orchestration Instability

1.19 WHEN n8n webhook orchestration is triggered THEN the system fails without retry logic, timeout recovery, or proper validation

1.20 WHEN webhook requests fail THEN the system loses requests permanently without fallback mechanisms

1.21 WHEN orchestration errors occur THEN the system provides no structured error handling or diagnostic information

#### 8. Weak Resume Over-Optimization

1.22 WHEN high-quality resumes with strong content are submitted THEN the system applies aggressive optimization that degrades elite engineering content

1.23 WHEN the optimization engine processes resumes THEN the system treats all resumes equally without quality detection or section-level protection

1.24 WHEN strong resume sections exist THEN the system fails to preserve them and applies unnecessary rewrites

### Expected Behavior (Correct)

#### 1. Semantic Skill Matching with Intelligence

2.1 WHEN a resume contains semantically equivalent skills THEN the system SHALL recognize synonyms, related concepts, and contextual equivalents using semantic inference

2.2 WHEN the skill matching engine compares resume skills to job requirements THEN the system SHALL use semantic similarity algorithms (e.g., embedding-based matching, synonym dictionaries) instead of exact keyword matching

2.3 WHEN skill gap analysis is performed THEN the system SHALL only flag skills as "missing" when no semantically equivalent skill exists in the resume

#### 2. Identity Preservation with 3-Layer Protection

2.4 WHEN a resume is submitted for optimization THEN the system SHALL detect and preserve the candidate's professional identity (job title, career domain, seniority level)

2.5 WHEN the AI optimization process rewrites content THEN the system SHALL enforce identity preservation constraints through explicit prompt instructions and post-processing validation

2.6 WHEN career domain information is extracted THEN the system SHALL implement a 3-layer protection mechanism: (1) quality detection, (2) identity engine validation, (3) weak section optimization only

#### 3. Strict JSON Validation with Sanitization

2.7 WHEN the AI provider returns resume optimization results THEN the system SHALL sanitize responses by stripping markdown wrappers, code blocks, and extraneous formatting

2.8 WHEN the AI response is parsed THEN the system SHALL validate JSON structure against a strict schema and reject malformed outputs

2.9 WHEN AI output validation fails THEN the system SHALL implement retry logic with corrective prompts and fallback to alternative AI providers

#### 4. Dual-Resume Semantic ATS Scoring

2.10 WHEN a resume is optimized THEN the system SHALL compute separate ATS scores for the original and optimized versions using the same scoring algorithm

2.11 WHEN the scoring engine compares resumes THEN the system SHALL use semantic analysis to detect keyword density, skill coverage, and ATS compatibility improvements

2.12 WHEN users view optimization results THEN the system SHALL display realistic before/after scores with measurable improvement deltas (minimum 1% improvement threshold)

#### 5. Deterministic PHP LaTeX Rendering

2.13 WHEN the system generates PDF output THEN the system SHALL use deterministic PHP-based LaTeX template rendering with fixed layout logic

2.14 WHEN the AI returns optimization results THEN the system SHALL receive structured JSON data only (no LaTeX formatting or layout instructions)

2.15 WHEN PDFs are generated THEN the system SHALL produce professional, ATS-compliant documents with consistent spacing, proper bullet formatting, and clean layout

#### 6. Enterprise-Grade API Logging and Error Handling

2.16 WHEN the system runs in production environments THEN the system SHALL successfully execute OpenAI, OpenRouter, LaTeX API, and n8n webhook calls with comprehensive logging

2.17 WHEN API requests are made THEN the system SHALL log request/response payloads, headers, timing, and error details for diagnostic purposes

2.18 WHEN production API errors occur THEN the system SHALL capture detailed error context (environment variables, config state, SSL settings, timeout values) and provide actionable diagnostics

#### 7. Fault-Tolerant N8N Orchestration

2.19 WHEN n8n webhook orchestration is triggered THEN the system SHALL implement exponential backoff retry logic (3 attempts with 2s, 4s, 8s delays)

2.20 WHEN webhook requests fail THEN the system SHALL queue failed requests for retry and log failure reasons with full context

2.21 WHEN orchestration errors occur THEN the system SHALL provide structured error responses with diagnostic information and fallback mechanisms

#### 8. Intelligent Resume Quality Detection

2.22 WHEN high-quality resumes are submitted THEN the system SHALL detect strong sections using quality metrics (keyword density, action verb usage, quantifiable achievements) and preserve them

2.23 WHEN the optimization engine processes resumes THEN the system SHALL apply section-level quality scoring and only optimize weak sections below quality thresholds

2.24 WHEN strong resume sections exist THEN the system SHALL protect them from modification and focus optimization on weak areas only

### Unchanged Behavior (Regression Prevention)

#### Core Resume Processing

3.1 WHEN a valid resume PDF is uploaded THEN the system SHALL CONTINUE TO extract text content using the existing PDF parser (smalot/pdfparser)

3.2 WHEN resume text is extracted THEN the system SHALL CONTINUE TO store the original resume file in the configured storage disk (local/S3/R2)

3.3 WHEN resume metadata is processed THEN the system SHALL CONTINUE TO save candidate profile information to the database

#### Job Description Matching

3.4 WHEN a job description is provided THEN the system SHALL CONTINUE TO extract required skills, qualifications, and keywords

3.5 WHEN job matching is performed THEN the system SHALL CONTINUE TO generate skill gap analysis and recommendations

3.6 WHEN matching results are displayed THEN the system SHALL CONTINUE TO show job compatibility scores and missing skill lists

#### AI Provider Integration

3.7 WHEN AI optimization is requested THEN the system SHALL CONTINUE TO support multiple AI providers (OpenAI, Anthropic, OpenRouter) with fallback logic

3.8 WHEN AI provider selection occurs THEN the system SHALL CONTINUE TO use the configured primary provider with automatic failover to secondary providers

3.9 WHEN AI API calls are made THEN the system SHALL CONTINUE TO include API keys, model names, and endpoint URLs from environment configuration

#### PDF Generation

3.10 WHEN optimized resume content is ready THEN the system SHALL CONTINUE TO generate PDF output using the LaTeXLite API

3.11 WHEN PDF generation is requested THEN the system SHALL CONTINUE TO use the configured API key, timeout, and retry settings

3.12 WHEN PDF generation completes THEN the system SHALL CONTINUE TO store the generated PDF in the storage system and return a download URL

#### User Interface

3.13 WHEN users view the resume optimization page THEN the system SHALL CONTINUE TO display the original resume, optimization suggestions, and ATS score

3.14 WHEN users trigger optimization THEN the system SHALL CONTINUE TO show loading states and progress indicators

3.15 WHEN optimization completes THEN the system SHALL CONTINUE TO display before/after comparison views with download options

#### Light Mode Optimization

3.16 WHEN RESUME_OPTIMIZER_LIGHT_MODE is enabled THEN the system SHALL CONTINUE TO preserve original resume structure and only add ATS keywords

3.17 WHEN light mode is active THEN the system SHALL CONTINUE TO avoid full content rewrites and maintain candidate voice

3.18 WHEN minimum score delta thresholds are configured THEN the system SHALL CONTINUE TO respect RESUME_OPTIMIZER_MIN_SCORE_DELTA and RESUME_OPTIMIZER_MIN_TEXT_DELTA settings

#### Authentication and Authorization

3.19 WHEN users access resume optimization features THEN the system SHALL CONTINUE TO enforce authentication requirements

3.20 WHEN candidates view their resumes THEN the system SHALL CONTINUE TO restrict access to resume owners only

3.21 WHEN recruiters access candidate resumes THEN the system SHALL CONTINUE TO enforce application-based access permissions

#### Database Operations

3.22 WHEN resume optimization data is saved THEN the system SHALL CONTINUE TO use Laravel Eloquent ORM for database interactions

3.23 WHEN optimization history is tracked THEN the system SHALL CONTINUE TO store optimization attempts, scores, and timestamps

3.24 WHEN database transactions are required THEN the system SHALL CONTINUE TO use transaction wrappers for data consistency
