<?php

namespace App\Support;

use App\Models\ProgramEntry;

/**
 * Turns what leaders recorded for an activity into the points groups actually
 * earn. Awards are always derived here and never stored, so editing a value,
 * removing a group or switching an activity's mode re-settles the standings.
 */
class Placement
{
    /**
     * Points per group for one activity.
     *
     * In 'raw' mode the recorded number is the score. In 'placement' mode the
     * numbers only rank the groups: the best gets as many points as there are
     * ranked groups, the worst gets one. Groups that tie split the points their
     * places would have earned, so a tie can never beat winning outright.
     *
     * @param  array<int, float>  $valuesByGroupId  what each group recorded
     * @return array<int, float> points earned, keyed the same way
     */
    public static function awards(array $valuesByGroupId, string $mode): array
    {
        if ($mode === ProgramEntry::POINTS_RAW) {
            return $valuesByGroupId;
        }

        if ($mode !== ProgramEntry::POINTS_PLACEMENT || $valuesByGroupId === []) {
            return [];
        }

        $count = count($valuesByGroupId);

        // Group ids sharing a value, best value first.
        $tiers = [];
        foreach ($valuesByGroupId as $groupId => $value) {
            $tiers[(string) $value][] = $groupId;
        }
        krsort($tiers, SORT_NUMERIC);

        $awards = [];
        $rank = 1;

        foreach ($tiers as $groupIds) {
            $tied = count($groupIds);

            // The places this tier occupies are worth (count - place + 1) each.
            $pot = 0.0;
            for ($place = $rank; $place < $rank + $tied; $place++) {
                $pot += $count - $place + 1;
            }

            $share = $pot / $tied;
            foreach ($groupIds as $groupId) {
                $awards[$groupId] = $share;
            }

            $rank += $tied;
        }

        return $awards;
    }
}
