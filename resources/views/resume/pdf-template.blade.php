{{--
    PREMIUM ATS Resume PDF Template
    ─────────────────────────────────────────────────────
    Engine  : DomPDF (barryvdh/laravel-dompdf)
    Paper   : A4 portrait  |  DPI: 150
    Font    : DejaVu Sans (bundled — guaranteed in Dompdf)

    Dompdf CSS rules strictly followed:
      ✓ float:right  — for date/location columns
      ✓ overflow:hidden — clearfix on row containers
      ✓ page-break-inside: avoid — keeps entries together
      ✓ word-wrap: break-word — prevents bullet overflow
      ✗ NO flexbox / grid / position:absolute in content rows
      ✗ NO external fonts / CDN / remote assets
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>

/* ═══════════════════════════════════════════════
   RESET
═══════════════════════════════════════════════ */
* { margin: 0; padding: 0; box-sizing: border-box; }

/* ═══════════════════════════════════════════════
   PAGE — A4 with generous but tight margins
   Printable area: 210 − 14 − 14 = 182mm wide
                   297 − 14 − 12 = 271mm tall
═══════════════════════════════════════════════ */
@page {
    size: A4 portrait;
    margin: 14mm 14mm 12mm 14mm;
}

/* ═══════════════════════════════════════════════
   BASE
═══════════════════════════════════════════════ */
body {
    font-family: 'Inter', Arial, Helvetica, sans-serif;
    font-size: 8pt;
    color: #1c1c1c;
    line-height: 1.4;
    background: #ffffff;
    width: 100%;
}

/* ═══════════════════════════════════════════════
   HEADER — Name + Contact bar
═══════════════════════════════════════════════ */
.hdr {
    text-align: center;
    padding-bottom: 7pt;
    margin-bottom: 10.5pt;
    border-bottom: 1.5pt solid #111111;
}
.hdr-name {
    font-size: 18pt;
    font-weight: bold;
    color: #111111;
    letter-spacing: 0.5pt;
    text-transform: uppercase;
    line-height: 1.2;
    margin-bottom: 3pt;
}
.hdr-contact {
    font-size: 8pt;
    color: #333333;
    line-height: 1.4;
}
.hdr-role {
    font-size: 8pt;
    color: #555555;
    font-style: italic;
    margin-top: 2pt;
}

/* ═══════════════════════════════════════════════
   SECTION WRAPPER
═══════════════════════════════════════════════ */
.sec {
    margin-bottom: 10.5pt;
    page-break-inside: avoid;
}

/* ═══════════════════════════════════════════════
   SECTION TITLE — with left accent bar
═══════════════════════════════════════════════ */
.sec-title {
    font-size: 9pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.75pt;
    color: #111111;
    border-bottom: 1pt solid #111111;
    padding-bottom: 1.5pt;
    margin-bottom: 5pt;
    page-break-after: avoid;
}

/* ═══════════════════════════════════════════════
   PROFESSIONAL SUMMARY
═══════════════════════════════════════════════ */
.summary {
    font-size: 8pt;
    color: #1c1c1c;
    line-height: 1.4;
    text-align: justify;
    word-wrap: break-word;
}

/* ═══════════════════════════════════════════════
   TECHNICAL SKILLS
═══════════════════════════════════════════════ */
.skills-wrap {
    font-size: 8pt;
    color: #1c1c1c;
    line-height: 1.4;
    word-wrap: break-word;
}
.skill-row {
    margin-bottom: 2pt;
}
.skill-cat {
    font-weight: bold;
    color: #111111;
}

/* ═══════════════════════════════════════════════
   ENTRY — Experience & Projects
   Two-row header:
     Row 1: [Company/Project]        [Date  ▸ float:right]
     Row 2: [Role/Tech]              [Location ▸ float:right]
═══════════════════════════════════════════════ */
.entry {
    margin-bottom: 7pt;
    page-break-inside: avoid;
}

/* clearfix + date row */
.e-row1 {
    overflow: hidden;
    margin-bottom: 0.5pt;
}
.e-date {
    float: right;
    font-size: 8pt;
    color: #555555;
    white-space: nowrap;
    margin-left: 8pt;
}
.e-company {
    font-size: 9pt;
    font-weight: bold;
    color: #111111;
    display: block;
    overflow: hidden;
    word-wrap: break-word;
}
.e-proj-title {
    font-size: 9pt;
    font-weight: bold;
    color: #111111;
    display: block;
    overflow: hidden;
    word-wrap: break-word;
}

/* role + location row */
.e-row2 {
    overflow: hidden;
    margin-bottom: 3pt;
}
.e-location {
    float: right;
    font-size: 8pt;
    color: #777777;
    white-space: nowrap;
    margin-left: 8pt;
}
.e-role {
    font-size: 8pt;
    font-style: italic;
    color: #333333;
    display: block;
    overflow: hidden;
    word-wrap: break-word;
}
.e-tech {
    font-size: 8pt;
    color: #555555;
    font-style: italic;
    display: block;
    overflow: hidden;
    word-wrap: break-word;
    margin-bottom: 2pt;
}

/* bullets */
.bullets {
    margin-top: 2pt;
    padding-left: 12pt;
}
.bullets li {
    font-size: 8pt;
    color: #1c1c1c;
    line-height: 1.4;
    margin-bottom: 3pt;
    word-wrap: break-word;
    text-align: left;
}

/* ═══════════════════════════════════════════════
   EDUCATION
═══════════════════════════════════════════════ */
.edu-entry {
    margin-bottom: 5pt;
    page-break-inside: avoid;
}
.edu-row1 {
    overflow: hidden;
    margin-bottom: 0.5pt;
}
.edu-year {
    float: right;
    font-size: 8pt;
    color: #555555;
    white-space: nowrap;
    margin-left: 8pt;
}
.edu-school {
    font-size: 9pt;
    font-weight: bold;
    color: #111111;
    display: block;
    overflow: hidden;
    word-wrap: break-word;
}
.edu-degree {
    font-size: 8pt;
    color: #333333;
    margin-top: 1pt;
    word-wrap: break-word;
}
.edu-meta {
    font-size: 8pt;
    color: #555555;
    margin-top: 1pt;
}

/* ═══════════════════════════════════════════════
   CERTIFICATIONS
═══════════════════════════════════════════════ */
.cert-list {
    padding-left: 12pt;
}
.cert-list li {
    font-size: 8pt;
    color: #1c1c1c;
    line-height: 1.4;
    margin-bottom: 3pt;
    word-wrap: break-word;
}

</style>
</head>
<body>

{{-- ══ HEADER ══ --}}
<div class="hdr">
    <div class="hdr-name">{{ strtoupper($name) }}</div>
    <div class="hdr-contact">
        @php
            $cp = [];
            if (!empty($phone))    $cp[] = $phone;
            if (!empty($email))    $cp[] = $email;
            if (!empty($location)) $cp[] = $location;
        @endphp
        @if(!empty($cp)){{ implode('  ·  ', $cp) }}@endif
    </div>
    @if(!empty($targetRole))
    <div class="hdr-role">Optimised for: {{ $targetRole }}</div>
    @endif
</div>

{{-- ══ PROFESSIONAL SUMMARY ══ --}}
@if(!empty($sections['summary']))
<div class="sec">
    <div class="sec-title">Professional Summary</div>
    <div class="summary">{{ $sections['summary'] }}</div>
</div>
@endif

{{-- ══ TECHNICAL SKILLS ══ --}}
@if(!empty($sections['skills']))
<div class="sec">
    <div class="sec-title">Technical Skills</div>
    <div class="skills-wrap">
        @php
            $skillArr  = is_array($sections['skills']) ? $sections['skills'] : [$sections['skills']];
            $skillStr  = implode(' | ', $skillArr);
            $isGrouped = preg_match(
                '/\b(Languages|Frameworks|Tools|Databases|Cloud|DevOps|Libraries|Platforms)\s*:/i',
                $skillStr
            );
        @endphp

        @if($isGrouped)
            @php $groups = preg_split('/\s*\|\s*|\n/', $skillStr); @endphp
            @foreach($groups as $grp)
                @php $grp = trim($grp, ' ,•'); @endphp
                @if(strlen($grp) > 3)
                    <div class="skill-row">
                        @php
                            if (preg_match('/^([^:]+):\s*(.+)$/', $grp, $gm)) {
                                $lbl = trim($gm[1]);
                                $val = trim($gm[2]);
                            } else { $lbl = null; $val = $grp; }
                        @endphp
                        @if($lbl)<span class="skill-cat">{{ $lbl }}:</span> @endif{{ $val }}
                    </div>
                @endif
            @endforeach
        @else
            <div class="skill-row">
                <span class="skill-cat">Core Skills:</span>
                {{ implode(', ', array_filter($skillArr, fn($s) => strlen(trim($s)) > 1)) }}
            </div>
        @endif
    </div>
</div>
@endif

{{-- ══ PROFESSIONAL EXPERIENCE ══ --}}
@if(!empty($sections['experience']))
<div class="sec">
    <div class="sec-title">Professional Experience</div>
    @foreach($sections['experience'] as $exp)
    <div class="entry">

        {{-- Row 1: Company (left) ·· Date (right) --}}
        <div class="e-row1">
            @if(!empty($exp['date']))<span class="e-date">{{ $exp['date'] }}</span>@endif
            <span class="e-company">{{ $exp['org'] ?: ($exp['title'] ?? '') }}</span>
        </div>

        {{-- Row 2: Role (left) ·· Location (right) --}}
        @php
            $roleText = (!empty($exp['org']) && !empty($exp['title'])) ? $exp['title'] : '';
            $hasRow2  = !empty($roleText) || !empty($exp['location']);
        @endphp
        @if($hasRow2)
        <div class="e-row2">
            @if(!empty($exp['location']))<span class="e-location">{{ $exp['location'] }}</span>@endif
            @if(!empty($roleText))<span class="e-role">{{ $roleText }}</span>@endif
        </div>
        @endif

        {{-- Bullets (max 4) --}}
        @if(!empty($exp['bullets']))
        <ul class="bullets">
            @foreach(array_slice($exp['bullets'], 0, 4) as $b)
                <li>{{ $b }}</li>
            @endforeach
        </ul>
        @endif

    </div>
    @endforeach
</div>
@endif

{{-- ══ PROJECTS ══ --}}
@if(!empty($sections['projects']))
<div class="sec">
    <div class="sec-title">Projects</div>
    @foreach(array_slice($sections['projects'], 0, 3) as $proj)
    <div class="entry">

        {{-- Row 1: Project title (left) ·· Date (right) --}}
        <div class="e-row1">
            @if(!empty($proj['date']))<span class="e-date">{{ $proj['date'] }}</span>@endif
            <span class="e-proj-title">{{ $proj['title'] }}</span>
        </div>

        {{-- Tech stack (below title if present) --}}
        @if(!empty($proj['tech']))
        <div class="e-tech">{{ $proj['tech'] }}</div>
        @endif

        {{-- Bullets (max 3) --}}
        @if(!empty($proj['bullets']))
        <ul class="bullets">
            @foreach(array_slice($proj['bullets'], 0, 3) as $b)
                <li>{{ $b }}</li>
            @endforeach
        </ul>
        @endif

    </div>
    @endforeach
</div>
@endif

{{-- ══ EDUCATION ══ --}}
@if(!empty($sections['education']))
<div class="sec">
    <div class="sec-title">Education</div>
    @foreach($sections['education'] as $edu)
    <div class="edu-entry">

        {{-- Row 1: Institution (left) ·· Year (right) --}}
        <div class="edu-row1">
            @if(!empty($edu['meta']))<span class="edu-year">{{ $edu['meta'] }}</span>@endif
            <span class="edu-school">{{ $edu['school'] ?: $edu['degree'] }}</span>
        </div>

        @if(!empty($edu['school']) && !empty($edu['degree']))
            <div class="edu-degree">{{ $edu['degree'] }}</div>
        @endif

    </div>
    @endforeach
</div>
@endif

{{-- ══ CERTIFICATIONS & ACHIEVEMENTS ══ --}}
@if(!empty($sections['certifications']))
<div class="sec">
    <div class="sec-title">Certifications &amp; Achievements</div>
    <ul class="cert-list">
        @foreach($sections['certifications'] as $cert)
            <li>{{ $cert }}</li>
        @endforeach
    </ul>
</div>
@endif

</body>
</html>
