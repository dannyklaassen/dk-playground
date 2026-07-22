<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $exercise->title }} — {{ __('admin.comprehension_exercise.print.answer_sheet_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #111;
            max-width: 48rem;
            margin: 0 auto;
            padding: 2rem;
        }
        header {
            border-bottom: 2px solid #111;
            padding-bottom: .5rem;
            margin-bottom: 1.5rem;
        }
        h1 { font-size: 16pt; margin: 0; }
        h1 small { font-weight: normal; font-size: 11pt; display: block; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; table-layout: fixed; }
        th.center { width: 15%; }
        th, td { border: 1px solid #111; padding: .4rem .6rem; text-align: left; vertical-align: top; }
        th { background: #eee; }
        td.center, th.center { text-align: center; }
        .evidence { margin-bottom: .75rem; break-inside: avoid; }
        .evidence strong { display: block; }
        @media print {
            body { padding: 0; }
            @page { margin: 2cm; }
        }
    </style>
</head>
<body>
    <header>
        <h1>
            {{ __('admin.comprehension_exercise.print.answer_sheet_title') }}
            <small>{{ $exercise->title }}</small>
        </h1>
    </header>

    <table>
        <thead>
            <tr>
                <th class="center">{{ __('admin.comprehension_exercise.print.question') }}</th>
                <th class="center">{{ __('admin.comprehension_exercise.print.answer') }}</th>
                <th class="center">{{ __('admin.comprehension_exercise.print.paragraph') }}</th>
                <th>{{ __('admin.comprehension_exercise.print.skill') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($exercise->questions as $index => $question)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $question['answer']['choice'] }}</td>
                    <td class="center">{{ $question['answer']['paragraph'] }}</td>
                    <td>{{ \App\Enums\ReadingSkill::from($question['skill'])->getLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach ($exercise->questions as $index => $question)
        <div class="evidence">
            <strong>{{ __('admin.comprehension_exercise.print.question') }} {{ $index + 1 }} — {{ __('admin.comprehension_exercise.print.evidence') }}:</strong>
            @if (filled($question['answer']['evidence'] ?? null))
                <em>&ldquo;{{ $question['answer']['evidence'] }}&rdquo;</em>
            @else
                {{ __('common.not_applicable') }}
            @endif
        </div>
    @endforeach
</body>
</html>
