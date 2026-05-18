{{--
    PROFESSIONAL ATS Resume PDF Template — v2
    ─────────────────────────────────────────────────────
    Engine  : DomPDF (barryvdh/laravel-dompdf)
    Paper   : A4 portrait  |  DPI: 150
    Font    : DejaVu Sans (guaranteed bundled in Dompdf)

    ATS Design Rules:
      ✅ Single-column layout
      ✅ No tables, no columns, no icons, no gradients
      ✅ No external fonts / CDN / remote assets
      ✅ float:right for date/location (Dompdf-safe)
      ✅ page-break-inside: avoid on entries
      ✅ word-wrap: break-word on all text
      ❌ NO flexbox / grid / position:absolute in content rows
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>

* { margin: 0; padding: 0; box-sizing: border-box; }

@page {
    size: A4 portrait;
    margin: 13mm 14mm 11mm 14mm;
}

body {
    font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
    font-size: 8pt;
    color: #1a1a1a;
    line-height: 1.38;
    background: #ffffff;
    width: 100%;
}

/* ── HEADER ─────────────────────────────────────── */
.hdr {
    text-align: center;
    padding-bottom: 7pt;
    margin-bottom: 9pt;
    border-bottom: 1.8pt solid #111;
}
.hdr-name {
    font-size: 19pt;
    font-weight: bold;
    color: #111111;
    letter-spacing: 1pt;
    text-transform: uppercase;
    line-height: 1.15;
    margin-bottom: 3pt;
}
.hdr-contact {
    font-size: 8pt;
    color: #333333;
    line-height: 1.5;
}
.hdr-separator {
    color: #777777;
    font-size: 9pt;
    padding: 0 3pt;
}
.hdr-role {
    font-size: 7.5pt;
    color: #555555;
    font-style: italic;
    margin-top: 2pt;
}

/* ── SECTION WRAPPER ─────────────────────────────── */
.sec {
    margin-bottom: 7pt;
    page-break-inside: avoid;
}
.sec:empty { display: none; }

/* ── SECTION TITLE ───────────────────────────────── */
.sec-title {
    font-size: 8.5pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.9pt;
    color: #111111;
    border-bottom: 1pt solid #222222;
    padding-bottom: 1.5pt;
    margin-bottom: 5pt;
    page-break-after: avoid;
}

/* ── SUMMARY ─────────────────────────────────────── */
.summary {
    font-size: 8pt;
    color: #1a1a1a;
    line-height: 1.45;
    text-align: justify;
    word-wrap: break-word;
}

/* ── SKILLS ──────────────────────────────────────── */
.skills-wrap {
    font-size: 8pt;
    color: #1a1a1a;
    line-height: 1.5;
    word-wrap: break-word;
}
.skill-row { margin-bottom: 2pt; }
.skill-cat {
    font-weight: bold;
    color: #111111;
}

/* ── ENTRY (Experience & Projects) ───────────────── */
.entry {
    margin-bottom: 5pt;
    page-break-inside: avoid;
}

/* Row 1: Company + Date (float:right) */
.e-row1 {
    overflow: hidden;
    margin-bottom: 0.5pt;
}
.e-date {
    float: right;
    font-size: 7.5pt;
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

/* Row 2: Role + Location (float:right) */
.e-row2 {
    overflow: hidden;
    margin-bottom: 2.5pt;
}
.e-location {
    float: right;
    font-size: 7.5pt;
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
    font-size: 7.5pt;
    color: #555555;
    font-style: italic;
    display: block;
    overflow: hidden;
    word-wrap: break-word;
    margin-bottom: 1.5pt;
}

/* Bullets */
.bullets {
    margin-top: 1pt;
    padding-left: 10pt;
}
.bullets li {
    font-size: 7.5pt;
    color: #1a1a1a;
    line-height: 1.42;
    margin-bottom: 2pt;
    word-wrap: break-word;
    text-align: left;
}

/* ── EDUCATION ───────────────────────────────────── */
.edu-entry {
    margin-bottom: 5pt;
    page-break-inside: avoid;
}
.edu-row1 { overflow: hidden; margin-bottom: 0.5pt; }
.edu-year {
    float: right;
    font-size: 7.5pt;
    color: #555555;
    white-space: nowrap;
    margin-left: 8pt;
}
.edu-school {
    font-size: 8.5pt;
    font-weight: bold;
    color: #111111;
    display: block;
    overflow: hidden;
    word-wrap: break-word;
}
.edu-degree {
    font-size: 7.5pt;
    color: #333333;
    margin-top: 1pt;
    word-wrap: break-word;
}
.edu-meta {
    font-size: 7.5pt;
    color: #555555;
    margin-top: 0.5pt;
}

/* ── CERTIFICATIONS ──────────────────────────────── */
.cert-list { padding-left: 11pt; }
.cert-list li {
    font-size: 7.5pt;
    color: #1a1a1a;
    line-height: 1.45;
    margin-bottom: 2.5pt;
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
        @if(!empty($cp))
            {{ implode('  ·  ', $cp) }}
        @endif
        @if(!empty($links))
            <br>
            @foreach(array_slice($links, 0, 2) as $link)
                {{ preg_replace('/https?:\/\//', '', $link) }}@if(!$loop->last)  ·  @endif
            @endforeach
        @endif
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
            $isGrouped = preg_match('/\b(Languages|Frameworks|Tools|Databases|Cloud|DevOps|Libraries)\s*:/i', $skillStr);
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

        {{-- Row 1: Company · Date --}}
        <div class="e-row1">
            @if(!empty($exp['date']))<span class="e-date">{{ $exp['date'] }}</span>@endif
            <span class="e-company">{{ $exp['org'] ?: ($exp['title'] ?? '') }}</span>
        </div>

        {{-- Row 2: Role · Location --}}
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

        {{-- Bullets --}}
        @if(!empty($exp['bullets']))
        <ul class="bullets">
            @foreach(array_slice($exp['bullets'], 0, 5) as $b)
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
    @foreach(array_slice($sections['projects'], 0, 4) as $proj)
    <div class="entry">

        <div class="e-row1">
            @if(!empty($proj['date']))<span class="e-date">{{ $proj['date'] }}</span>@endif
            <span class="e-proj-title">{{ $proj['title'] }}</span>
        </div>

        @if(!empty($proj['tech']))
        <div class="e-tech">{{ $proj['tech'] }}</div>
        @endif

        @if(!empty($proj['bullets']))
        <ul class="bullets">
            @foreach(array_slice($proj['bullets'], 0, 4) as $b)
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

        <div class="edu-row1">
            @if(!empty($edu['meta'] ?? null) || !empty($edu['year'] ?? null))
                <span class="edu-year">{{ ($edu['meta'] ?? '') ?: ($edu['year'] ?? '') }}</span>
            @endif
            <span class="edu-school">{{ ($edu['school'] ?? '') ?: ($edu['degree'] ?? '') }}</span>
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
