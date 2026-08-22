<?php

use App\Models\ProgramEntry;
use App\Support\Placement;

function placementAwards(array $values): array
{
    return Placement::awards($values, ProgramEntry::POINTS_PLACEMENT);
}

it('gives the best group as many points as there are groups', function () {
    // 10 is best of four → 4 points, down to 1 for the worst.
    expect(placementAwards([1 => 10.0, 2 => 8.0, 3 => 6.0, 4 => 5.0]))
        ->toBe([1 => 4.0, 2 => 3.0, 3 => 2.0, 4 => 1.0]);
});

it('splits the places a tie occupies between the tied groups', function () {
    // Second and third place are worth 3 and 2, so both tied groups get 2.5.
    expect(placementAwards([1 => 10.0, 2 => 8.0, 3 => 8.0, 4 => 5.0]))
        ->toBe([1 => 4.0, 2 => 2.5, 3 => 2.5, 4 => 1.0]);
});

it('gives everyone the same when every group ties', function () {
    expect(placementAwards([1 => 7.0, 2 => 7.0, 3 => 7.0]))
        ->toBe([1 => 2.0, 2 => 2.0, 3 => 2.0]);
});

it('awards a single group one point', function () {
    expect(placementAwards([5 => 42.0]))->toBe([5 => 1.0]);
});

it('never lets a tie beat winning outright', function () {
    $tied = placementAwards([1 => 10.0, 2 => 10.0, 3 => 1.0]);
    $won = placementAwards([1 => 11.0, 2 => 10.0, 3 => 1.0]);

    expect($tied[1])->toBeLessThan($won[1]);
});

it('hands back the recorded numbers unchanged in raw mode', function () {
    $values = [1 => 12.5, 2 => 3.0];

    expect(Placement::awards($values, ProgramEntry::POINTS_RAW))->toBe($values);
});

it('awards nothing when the activity is not scored', function () {
    expect(Placement::awards([1 => 10.0], ProgramEntry::POINTS_NONE))->toBe([]);
});

it('copes with no results at all', function () {
    expect(placementAwards([]))->toBe([]);
});

it('ranks negative results too', function () {
    expect(placementAwards([1 => -5.0, 2 => -1.0]))->toBe([2 => 2.0, 1 => 1.0]);
});
