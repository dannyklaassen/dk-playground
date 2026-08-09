<?php

declare(strict_types=1);

use App\Enums\ClueBand;

it('maps a level onto its band', function (int $level, ClueBand $band): void {
    expect(ClueBand::forLevel($level))->toBe($band);
})->with([
    'first level' => [1, ClueBand::Band1To10],
    'last of band 1' => [10, ClueBand::Band1To10],
    'first of band 2' => [11, ClueBand::Band11To20],
    'last of band 2' => [20, ClueBand::Band11To20],
    'first of band 3' => [21, ClueBand::Band21To30],
    'last of band 3' => [30, ClueBand::Band21To30],
    'first of band 4' => [31, ClueBand::Band31To40],
    'last of band 4' => [40, ClueBand::Band31To40],
    'first of band 5' => [41, ClueBand::Band41To50],
    'last level' => [50, ClueBand::Band41To50],
]);

it('refuses a level outside 1 to 50', function (int $level): void {
    ClueBand::forLevel($level);
})->with([
    'zero' => 0,
    'negative' => -1,
    'above the maximum' => 51,
])->throws(InvalidArgumentException::class);
