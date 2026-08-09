@php
    // The base every print page shares. Everything a specific sheet needs on top
    // of this stays in that sheet's own style block.
    $fontSize ??= '12pt';
    $lineHeight ??= '1.6';
@endphp
<style>
    * { box-sizing: border-box; }
    body {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: {{ $fontSize }};
        line-height: {{ $lineHeight }};
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
    header .meta .line { display: inline-block; width: 10rem; border-bottom: 1px solid #111; }
    @media print {
        body { padding: 0; }
        @page { margin: 2cm; }
    }
</style>
