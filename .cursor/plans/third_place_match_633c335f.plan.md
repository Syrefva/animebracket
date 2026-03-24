---
name: Third place match
overview: Third-place match runs in parallel with the title match on the same tier. `round.round_is_third_place_match` marks the consolation row; creation/finalization work for cross-group semifinals; labels and stats use that flag (not heuristics).
todos:
  - id: advance-universal
    content: _advanceBracket supports title+3rd creation/finalization for single and cross-group semifinal feeders
    status: completed
  - id: labels-stats-universal
    content: _isDualFinalsLayer + getVotingStats one round scan (roundsByTier); stats.php excludes isThirdPlaceMatch; public stats disclaimer beside Entrant Statistics heading (flex row)
    status: completed
  - id: verify-integrations
    content: Re-verify getResults, normalize, results UI, me.php, rollback for multi-group shapes
    status: pending
isProject: false
---

# Third-place match (with simultaneous title voting)

## Scope

- Show title match and 3rd-place match in the same voting layer.
- Schema: DB column `round.round_is_third_place_match`; `Api\Round` property `**isThirdPlaceMatch**` (camelCase, same as `bracketId` / `deleted`). Migration: `sql/add_round_is_third_place_match.sql` (backfill).
- Support single-group and cross-group semifinal feeders.

## Invariants

- Feature flag remains `Api\Bracket::THIRD_PLACE_MATCH_ENABLED`.
- Title and 3rd-place rows share the same `round_tier`.
- `order = 0` is title match; `order = 1` is 3rd-place match when `round_is_third_place_match = 1`.
- Canonical finals group is `min(group_a, group_b)` when two semifinal matchups feed the same finals tier.

## Core backend behavior

1. **Create title + 3rd rows** in `[api/bracket.php](api/bracket.php)` `_advanceBracket` after the dual-finals finalize branch is skipped: in the `matchupCount > 1` loop, set `$shouldCreateThird` when `THIRD_PLACE_MATCH_ENABLED`, `**matchupCount === 2`** (not four QF opens, etc.), and both opens share the same `**round_tier`** with `**tier >= 1**` (tournament only). No extra DB check for “lower tiers final”: `**getCurrentRounds**` → `**proc_GetBracketActiveGroupTier**` orders by `**round_tier ASC**`, so the active tier is always the minimum among open rounds—if both current opens are tier *T*, nothing below *T* is still open. Then create `order = 1` third row if both `getLoserId()` values exist, with `**isThirdPlaceMatch = 1`** before `sync()`.
2. **Finalize title + 3rd together** in `_advanceBracket` when `THIRD_PLACE_MATCH_ENABLED`, exactly two current rounds, same tier/group, orders are **0** and **1** (either order in the array), and `**isThirdPlaceMatch`** is set on the **order = 1** row.
3. **Dual-finals layer** in `[api/round.php](api/round.php)`: private `**_isDualFinalsLayer`** (feature flag + tier > 1 + scan of passed-in tier rounds; optional `**$group*`* filter). `**getVotingStats**` loads all non-deleted `**round**` rows once into `**$roundsByTier**` (no `**Character::getById**`); merge rules derive per-group counts from those rows; chart labels use `**count($roundsInTier)**`. `**getBracketTitleForRound**` uses `**getRoundsByTier**` once for count + dual-finals scan.
4. `**_advanceBracket` mechanics:** after `getCurrentRounds`, set `**$matchupCount`** once; `**$shouldCreateThird`** is computed inline from `**matchupCount === 2**`, same tier, `**tier >= 1**`; `**_closeBracketWithWinnerRound**` closes `BS_FINAL`. `**advance()**` (voting path) runs `**_advanceBracket**`, `**Stats::getEntrantPerformanceStats(..., true)**`, then forces `**getResults(true)**`, `**Round::getCurrentRounds(..., true)**`, and clears `**Api:Round:getVotingStates_{id}**` so results and admin voting chart labels stay fresh.

## Labels and chart copy

- `[getBracketTitleForRound](api/round.php)`:
  - `roundCount === 2`: `"Title and Third Place Matches"` vs `"Semi Finals"` via `**_isDualFinalsLayer**` on `**getRoundsByTier**` rows.
  - `roundCount === 1`: always `"Title Match"`.
- `[_votingStatsChartLabel](api/round.php)` uses `**count($roundsInTier)**` (4 / 2 / 1) for Quarter / Semi–Finals / Title; `**_isDualFinalsLayer**` for the two-matchup case.
- `[controller/me.php](controller/me.php)` keeps `nextIsFinal` true for `"Title Match"` and `"Title and Third Place Matches"`.

## Results and stats behavior

- `[api/bracket.php](api/bracket.php)` `getResults()`: each `Round` from the DB carries `**isThirdPlaceMatch**` as today. When the finals tier has **more** DB rows than championship tree slots (`$roundCount > $slotsThisTier`), the **extra** row (real third-place match) is normalized in one block—the same numericize / unset shape as other real rows—not a synthetic PHP placeholder for “pending” third.
- **Results page flag:** `[controller/results.php](controller/results.php)` sets `**thirdPlaceMatchEnabled`** on the bracket object from `**Api\Bracket::THIRD_PLACE_MATCH_ENABLED`** so `**window.bracketData**` matches the PHP feature flag.
- `**[static/js/views/bracket-display.js](static/js/views/bracket-display.js)**`
  - Strips `**isThirdPlaceMatch**` rounds out of each tier before building `**Tier**` models so the main tree stays championship-only; stores the first third-place row in `**_thirdPlaceRound**`.
  - Renders the **“3rd Place Match”** strip when `**thirdPlaceMatchEnabled`** is true **and** the view is full/finals (`**group` is `null` / `undefined`**), not for single-group letter tabs. Markup is still appended after the tree, but `**_positionThirdPlaceBelowTitle`** (scheduled with `**requestAnimationFrame**` after `**renderBracket**`) sets `**position: absolute**` on `**.bracket-third-place-wrap**` so the strip sits at **title-match cell vertical center + `THIRD_PLACE_GAP_BELOW_TITLE_PX`**, instead of clearing after the tallest float column (bottom of the page). `**[static/scss/bracket-view.scss](static/scss/bracket-view.scss)**` gives `**.bracket-display**` `**position: relative**` so `**top**` is relative to the bracket container.
  - `**_renderThirdPlaceBlock**`: left/right `**tier.hbs**` single-entrant columns with `**views/winner.hbs**` in the center; passes `**modifierClass: 'winner--third-place'**`. `**[views/winner.hbs](views/winner.hbs)**` optionally appends that class on the inner `**.winner**` div. `**_pickShownWinnerEntrant**` mirrors championship winner pick logic (votes, then seed) for the center portrait.
  - Row height uses fixed `**THIRD_PLACE_ROW_HEIGHT**` (not full `**bracketHeight**`), so the strip stays compact under the heading.
  - `**_getThirdPlaceRawForView**`: use `**_thirdPlaceRound**` when present; if none yet, **synthesize** a minimal raw object (`filler`, `isThirdPlaceMatch`, no `**character1`**) so `**Round`** renders the same **TBD / `.nobody`** path as PHP tree fillers until the API returns a real row.
- `**[static/scss/bracket-view.scss](static/scss/bracket-view.scss)**`: `**.bracket-third-place-wrap**` — `**clear: none**`, padding for heading; `**.bracket-display**` — `**position: relative**`. Scoped under `**.bracket-third-place-match**`: strip width = two full entrant columns + narrower center; third-place winner portrait uses `**$ENTRANT_HEIGHT**` (same as entrant row), `**dl**` height `**$ENTRANT_HEIGHT + 48px**`, portrait `**margin-top**` **(dl - img) / 2** to center in `**dl`**. Main championship winner sizing remains `**$WINNER_IMG_SIZE` / `$WINNER_DL_HEIGHT`**. No extra horizontal connector length beyond default bracket rules (that experiment was reverted); no JS `**padding-bottom**` reserve on the bracket container (dropped as unnecessary).
- `[api/stats.php](api/stats.php)` `getEntrantPerformanceStats`: finalized `tier > 0` rounds first, else all `tier > 0`; then excludes `isThirdPlaceMatch` so winner/loser narratives stay correct.
- **Public entrant stats page:** `[controller/stats.php](controller/stats.php)` passes `**entrantStatsExcludesThirdPlace`** when `**THIRD_PLACE_MATCH_ENABLED`**. `[views/stats.hbs](views/stats.hbs)` uses `**.entrant-stats__heading**`: `**h3` “Entrant Statistics”** plus, when the flag is on, an italic `**.entrant-stats__note`** (current copy: `*Third-place match not included.`). `**[static/js/pages/Stats/EntrantStats.scss](static/js/pages/Stats/EntrantStats.scss)`**: `**display: flex**`, `**align-items: flex-start**`, column/row gap so the note sits on the same row as the title when width allows; `**h3**` `**margin-bottom: 0**`; heading block `**margin-bottom: 40px**` before `**#reactApp**` (entrant table).

## Acceptance checklist

- Verify both paths:
  - single-group bracket,
  - cross-group semifinal bracket (e.g. `test-7903e6` style shape).
- Advance flow: semis -> title+3rd same tier -> finalize both -> bracket reaches `BS_FINAL`.
- Labels: `"Semi Finals"` before finals layer, `"Title and Third Place Matches"` on dual-finals layer, `"Title Match"` when only one row exists.
- Results UI: 3rd-place block when `**THIRD_PLACE_MATCH_ENABLED**` (via `**thirdPlaceMatchEnabled**` on bracket data) **and** Finals/Full (`**group` null/undefined**); hidden on per-group views; TBD row from JS synthetic raw if API has not returned a third row yet; heading **“3rd Place Match”**; compact strip + `**winner--third-place`** styling; absolute vertical placement under title match per `**_positionThirdPlaceBelowTitle`** + gap constant; details in `**bracket-display.js**` + `**bracket-view.scss**` as above.
- Integrity checks:
  - `[controller/admin/normalize.php](controller/admin/normalize.php)` skips reconcile steps when `**$includesThirdPlaceMatch**` (championship + 3rd adjacent in results); does not duplicate or drop consolation,
  - `[sql/proc_RollbackBracket.sql](sql/proc_RollbackBracket.sql)` soft-deletes rounds at the next tier and above (so same-tier title + 3rd at the finals layer go together when rolling back from the feeder tier) and clears vote tallies on open rounds; confirm behavior for your `roundGroup` / cross-group shapes.

## Out of scope

- No per-bracket feature toggle yet.

