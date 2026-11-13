@php
    use App\Services\LLMService;

    $r = $analysis->results;
    $high = $r['total_high'] ?? 0;
    $medium = $r['total_medium'] ?? 0;
    $low = $r['total_low'] ?? 0;
    $total = $high + $medium + $low;
    $verdict = $analysis->verdict();

    // Print palette: the interface's rose, amber and teal, darkened so they keep
    // sufficient contrast on white paper.
    $tone = [
        'High' => ['ink' => '#BE123C', 'wash' => '#FFF1F2', 'rule' => '#FECDD3'],
        'Medium' => ['ink' => '#B45309', 'wash' => '#FFFBEB', 'rule' => '#FDE68A'],
        'Low' => ['ink' => '#0F766E', 'wash' => '#F0FDFA', 'rule' => '#99F6E4'],
    ];
    $v = $tone[$verdict['level']];

    $model = LLMService::modelLabel($analysis->llm_model);
    $strategy = LLMService::STRATEGY_LABELS[$analysis->prompting_strategy] ?? $analysis->prompting_strategy;
    $hasConfidence = collect($r['risks'] ?? [])->contains(fn($x) => isset($x['confidence']));
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Regression risk report · {{ $analysis->repository() ?? 'Analysis ' . $analysis->id }}</title>
    <style>
        @page {
            margin: 22mm 18mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.5pt;
            line-height: 1.5;
            color: #1E293B;
            margin: 0;
        }

        h1,
        h2 {
            color: #0F172A;
            margin: 0;
        }

        .mono {
            font-family: "DejaVu Sans Mono", monospace;
        }

        .muted {
            color: #64748B;
        }

        .eyebrow {
            font-size: 7.5pt;
            letter-spacing: 1.4pt;
            text-transform: uppercase;
            color: #0D9488;
            font-weight: bold;
        }

        /* Footer repeated on every page */
        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #94A3B8;
            border-top: 0.6pt solid #E2E8F0;
            padding-top: 2mm;
        }

        /* Dompdf supports counter(page) but not counter(pages), so no total. */
        .footer .page:after {
            content: "Page " counter(page);
        }

        /* Keeps a section heading with the content beneath it. */
        .keep {
            page-break-inside: avoid;
        }

        /* Masthead */
        .masthead {
            width: 100%;
            border-bottom: 1.2pt solid #0D9488;
            padding-bottom: 4mm;
            margin-bottom: 6mm;
        }

        .brand {
            font-size: 15pt;
            font-weight: bold;
            color: #0F172A;
        }

        .brand span {
            color: #0D9488;
        }

        /* Key facts */
        table.facts {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6mm;
        }

        table.facts td {
            padding: 1.4mm 0;
            vertical-align: top;
            border-bottom: 0.5pt solid #F1F5F9;
        }

        table.facts td.k {
            width: 34mm;
            color: #64748B;
        }

        /* Verdict */
        .verdict {
            border: 0.8pt solid {{ $v['rule'] }};
            border-left: 3pt solid {{ $v['ink'] }};
            background: {{ $v['wash'] }};
            padding: 4mm 5mm;
            margin-bottom: 6mm;
        }

        .verdict .label {
            font-size: 14pt;
            font-weight: bold;
            color: {{ $v['ink'] }};
        }

        h2 {
            font-size: 11pt;
            margin: 7mm 0 2.5mm;
            padding-bottom: 1.5mm;
            border-bottom: 0.6pt solid #E2E8F0;
        }

        ul.tests {
            margin: 0;
            padding-left: 5mm;
        }

        ul.tests li {
            margin-bottom: 1.2mm;
        }

        /* Counts */
        table.counts {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2.5mm 0;
            margin: 0 -2.5mm;
        }

        table.counts td {
            width: 33%;
            padding: 3mm 4mm;
            border: 0.6pt solid #E2E8F0;
        }

        table.counts .n {
            font-size: 18pt;
            font-weight: bold;
        }

        /* Findings */
        table.findings {
            width: 100%;
            border-collapse: collapse;
        }

        table.findings th {
            text-align: left;
            font-size: 7.5pt;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
            color: #64748B;
            padding: 2mm 2mm 2mm 0;
            border-bottom: 0.8pt solid #CBD5E1;
        }

        table.findings td {
            padding: 2.6mm 2mm 2.6mm 0;
            vertical-align: top;
            border-bottom: 0.5pt solid #E2E8F0;
        }

        table.findings tr {
            page-break-inside: avoid;
        }

        .pill {
            display: inline-block;
            padding: 0.6mm 2mm;
            font-size: 8pt;
            font-weight: bold;
            border-radius: 2mm;
        }

        .files {
            font-size: 8.5pt;
            color: #475569;
        }

        .note {
            margin-top: 8mm;
            font-size: 8pt;
            color: #64748B;
            border-top: 0.6pt solid #E2E8F0;
            padding-top: 3mm;
        }
    </style>
</head>

<body>
    <div class="footer">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td>RegRadar · Regression risk report · generated {{ $generatedAt->format('j M Y, H:i') }}</td>
                <td style="text-align:right;" class="page"></td>
            </tr>
        </table>
    </div>

    <table class="masthead">
        <tr>
            <td>
                <div class="brand">Reg<span>Radar</span></div>
                <div class="eyebrow" style="margin-top:1mm;">Regression risk report</div>
            </td>
            <td style="text-align:right; vertical-align:bottom;" class="muted">
                Analysis #{{ $analysis->id }}
            </td>
        </tr>
    </table>

    <table class="facts">
        <tr>
            <td class="k">Pull request</td>
            <td class="mono">{{ $analysis->pr_url }}</td>
        </tr>
        @if ($analysis->repository())
            <tr>
                <td class="k">Repository</td>
                <td class="mono">{{ $analysis->repository() }}</td>
            </tr>
        @endif
        <tr>
            <td class="k">Analysed</td>
            <td>
                {{ $analysis->created_at->format('j M Y, H:i') }}
                @if ($analysis->user)
                    <span class="muted">by {{ $analysis->user->name }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="k">Model and strategy</td>
            <td>{{ $model }} · {{ $strategy }}</td>
        </tr>
        @if ($analysis->duration_ms)
            <tr>
                <td class="k">Run cost</td>
                <td>
                    {{ number_format($analysis->duration_ms / 1000, 1) }} seconds
                    @if ($analysis->prompt_tokens)
                        · {{ number_format($analysis->prompt_tokens + $analysis->completion_tokens) }} tokens
                    @endif
                </td>
            </tr>
        @endif
    </table>

    <div class="verdict">
        <div class="label">{{ $verdict['label'] }}</div>
        <div style="margin-top:1mm;">{{ $verdict['copy'] }}</div>
        <div class="muted" style="margin-top:1.5mm;">{{ $total }} {{ Str::plural('finding', $total) }}</div>
    </div>

    @if (!empty($r['summary']))
        <h2>Summary</h2>
        <p style="margin:0;">{{ $r['summary'] }}</p>
    @endif

    @if (!empty($r['testing_focus']))
        <h2>What to test</h2>
        <ul class="tests">
            @foreach ($r['testing_focus'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Findings by severity</h2>
    <table class="counts">
        <tr>
            @foreach (['High' => $high, 'Medium' => $medium, 'Low' => $low] as $lvl => $n)
                <td style="border-top: 2pt solid {{ $tone[$lvl]['ink'] }};">
                    <div class="muted">{{ $lvl }} risk</div>
                    <div class="n" style="color: {{ $tone[$lvl]['ink'] }};">{{ $n }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="keep">
    <h2>Features at risk</h2>
    @if (!empty($r['risks']))
        <table class="findings">
            <thead>
                <tr>
                    <th style="width:30%;">Feature</th>
                    <th style="width:14%;">Risk</th>
                    @if ($hasConfidence)
                        <th style="width:13%;">Confidence</th>
                    @endif
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($r['risks'] as $risk)
                    @php $t = $tone[$risk['risk_level'] ?? 'Low'] ?? $tone['Low']; @endphp
                    <tr>
                        <td><strong>{{ $risk['feature'] }}</strong></td>
                        <td>
                            <span class="pill" style="color: {{ $t['ink'] }}; background: {{ $t['wash'] }}; border: 0.6pt solid {{ $t['rule'] }};">
                                {{ $risk['risk_level'] ?? 'Unknown' }}
                            </span>
                        </td>
                        @if ($hasConfidence)
                            <td class="mono">{{ isset($risk['confidence']) ? $risk['confidence'] . '%' : 'n/a' }}</td>
                        @endif
                        <td>{{ $risk['reason'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted" style="margin:0;">No feature in the registry was found to be at risk from this pull request.</p>
    @endif
    </div>

    @if (!empty($r['changed_files']))
        <h2>Files changed</h2>
        <div class="files mono">
            @foreach ($r['changed_files'] as $file)
                <div>{{ $file }}</div>
            @endforeach
        </div>
    @endif

    <div class="note">
        Risk ratings are produced by a large language model from the pull request diff and the Feature Registry. They
        are intended to inform a QA engineer's judgement, not to replace it.
        @if ($hasConfidence)
            Confidence is reported by the model itself and is not a calibrated probability.
        @endif
    </div>
</body>

</html>
