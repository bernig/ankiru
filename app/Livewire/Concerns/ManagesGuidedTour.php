<?php

namespace App\Livewire\Concerns;

/**
 * One-off onboarding of the CsvEditor: the guided tour and the tips shown
 * the first time a user reaches a given step. Each is marked as seen on the
 * user as soon as it is triggered, so it never fires twice.
 *
 * @property array<int, array<int, string>> $csvRows
 * @property bool $hasCsvLoaded
 */
trait ManagesGuidedTour
{
    /**
     * Auto-starts the full guided tour the first time this user has a loaded
     * file with at least one row, so there is something on screen to point
     * at. Marked as seen immediately so it never fires again automatically,
     * regardless of whether the user finishes or dismisses it; the header's
     * "Guided tour" button remains available to replay it manually.
     */
    private function maybeAutoStartMainTour(): void
    {
        $user = auth()->user();

        if (! $user || $user->tour_completed_at || ! $this->hasCsvLoaded || count($this->csvRows) === 0) {
            return;
        }

        $user->tour_completed_at = now();
        $user->save();

        $this->dispatch('start-main-tour');
    }

    /**
     * Shows a one-off tip pointing at the row's action icons the first time
     * this user ever adds a row to a file, since that's the moment the
     * translate/stress/audio icons first become relevant.
     */
    private function maybeStartFirstRowTip(): void
    {
        $user = auth()->user();

        if (! $user || $user->tour_first_row_tip_seen_at) {
            return;
        }

        $user->tour_first_row_tip_seen_at = now();
        $user->save();

        $this->dispatch('start-first-row-tip');
    }

    /**
     * Shows a one-off tip pointing at the "add row" area the first time
     * this user ever creates a new empty file, since that's the moment
     * they most need to know how to get started.
     */
    private function maybeStartNewFileTip(): void
    {
        $user = auth()->user();

        if (! $user || $user->tour_new_file_tip_seen_at) {
            return;
        }

        $user->tour_new_file_tip_seen_at = now();
        $user->save();

        $this->dispatch('start-new-file-tip');
    }
}
