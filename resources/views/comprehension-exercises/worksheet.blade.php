<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $exercise->title }} — {{ __('admin.comprehension_exercise.print.worksheet_title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #111;
            max-width: 48rem;
            margin: 0 auto;
            padding: 2rem;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-bottom: 2px solid #111;
            padding-bottom: .5rem;
            margin-bottom: 1.5rem;
            font-size: 10pt;
        }
        header .meta span { display: inline-block; margin-left: 1.5rem; }
        header .meta .line { display: inline-block; width: 10rem; border-bottom: 1px solid #111; }
        h1 { font-size: 18pt; margin: 0 0 1rem; }
        .paragraph {
            margin-bottom: .75rem;
            position: relative;
            padding-left: 1.75rem;
        }
        .paragraph .number {
            position: absolute;
            left: 0;
            top: .3rem;
            font-size: 8pt;
            color: #999;
        }
        h2 {
            font-size: 14pt;
            margin: 2rem 0 1rem;
            border-bottom: 1px solid #111;
            padding-bottom: .25rem;
        }
        .question { margin-bottom: 1.25rem; break-inside: avoid; }
        .question p { margin: 0 0 .5rem; font-weight: bold; }
        .option { display: flex; align-items: baseline; gap: .5rem; margin: .2rem 0 .2rem 1rem; }
        .option .bubble {
            display: inline-block;
            width: .9rem;
            height: .9rem;
            border: 1.5px solid #111;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative;
            top: .1rem;
        }
        .option .letter { font-weight: bold; width: 1.25rem; }
        @media print {
            body { padding: 0; }
            @page { margin: 2cm; }
            h2 { break-before: page; margin-top: 0; }
        }
    </style>
</head>
<body>
    <header>
        <strong>{{ __('admin.comprehension_exercise.print.worksheet_title') }}</strong>
        <span class="meta">
            <span>{{ __('admin.comprehension_exercise.print.name') }}: <span class="line"></span></span>
        </span>
    </header>

    <h1>{{ $exercise->title }}</h1>

    @foreach ($exercise->paragraphs as $index => $paragraph)
        <p class="paragraph"><span class="number">{{ $index + 1 }}.</span>{{ $paragraph }}</p>
    @endforeach

    <h2>{{ __('admin.comprehension_exercise.print.questions') }}</h2>

    @foreach ($exercise->questions as $index => $question)
        <div class="question">
            <p>{{ $index + 1 }}. {{ $question['question'] }}</p>
            @foreach ($question['options'] as $letter => $option)
                <div class="option">
                    <span class="bubble"></span>
                    <span class="letter">{{ $letter }}.</span>
                    <span>{{ $option }}</span>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
