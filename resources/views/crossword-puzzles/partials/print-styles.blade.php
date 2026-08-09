@include('partials.print-base')
<style>
    header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: 10pt;
    }
    h1 { font-size: 18pt; margin: 0 0 1.5rem; }
    h2 {
        font-size: 14pt;
        margin: 0 0 .75rem;
        border-bottom: 1px solid #111;
        padding-bottom: .25rem;
    }
    /* Two fixed columns, horizontal left and vertical right. Multicol would let
       the second heading start wherever the first list happened to end.
       ponytail: one row, so a list longer than a page does not reflow into the
       next column; switch to multicol per direction if that ever happens. */
    .clues { display: grid; grid-template-columns: 1fr 1fr; gap: 0 2rem; align-items: start; }
    .clues h2 { break-after: avoid; }
    .clue { margin-bottom: .5rem; break-inside: avoid; font-size: 11pt; line-height: 1.4; }
    .clue .number { font-weight: bold; }
    /* On its own line and quiet: the clue leads, the source text only helps. */
    .clue .source {
        display: block;
        margin-top: .35rem;
        color: #888;
        font-size: 8.5pt;
        font-style: italic;
        line-height: 1.2;
    }
    @media print {
        .clues { break-before: page; }
    }
</style>
