<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Every full datetime attribute (the automatic created_at/updated_at
 * timestamps, plus any explicit 'datetime'-cast column) is transparently
 * stored as true UTC — that's what actually ends up in the database column
 * — but handed back as Pakistan-local (Asia/Karachi) wherever it's read in
 * PHP/Blade, since that's the only timezone anyone using this app actually
 * operates in.
 *
 * Deliberately does NOT touch:
 * - Plain 'date'-cast attributes (a calendar day has no time-of-day/
 *   timezone concept — shifting it risks flipping across a midnight
 *   boundary for no benefit).
 * - Anything a model lists in $timezoneExempt (see Order::$timezoneExempt
 *   — shopify_created_at already has years of history stored as raw
 *   Pakistan wall-clock digits with no UTC conversion ever applied;
 *   converting it now, only going forward, would make old and new rows
 *   silently disagree by 5 hours with no way to tell which is which just
 *   by looking at the column).
 *
 * app.timezone stays 'UTC' (governs now() and how a naive DB string gets
 * interpreted on read) — this trait converts on top of that, per attribute,
 * rather than changing that global setting.
 */
trait HasLocalizedTimestamps
{
    protected function transformModelValue($key, $value)
    {
        $result = parent::transformModelValue($key, $value);

        if ($result instanceof Carbon && $this->isFullDateTimeAttribute($key) && ! $this->isTimezoneExempt($key)) {
            return $result->setTimezone('Asia/Karachi');
        }

        return $result;
    }

    public function setAttribute($key, $value)
    {
        if (! is_null($value) && $this->isFullDateTimeAttribute($key) && ! $this->isTimezoneExempt($key)) {
            // Reuses Laravel's own robust parsing (handles a Carbon
            // instance, a DateTimeInterface, or a raw string, offset or
            // not) via parent::asDateTime(), then normalizes to UTC before
            // handing off to the normal setAttribute() flow below, which
            // will just re-parse+reformat this already-UTC string as a
            // harmless no-op.
            $value = parent::asDateTime($value)->utc()->format($this->getDateFormat());
        }

        return parent::setAttribute($key, $value);
    }

    protected function isFullDateTimeAttribute(string $key): bool
    {
        return in_array($key, $this->getDates(), true)
            || $this->hasCast($key, ['datetime', 'custom_datetime', 'immutable_datetime', 'immutable_custom_datetime']);
    }

    /**
     * `$this->timezoneExempt ?? []` rather than declaring the property here
     * too — PHP treats a trait and its host class declaring the same
     * property (even with a different default) as a fatal conflict, not an
     * override. Only models that need an exemption (see Order) declare it
     * at all; `??` safely defaults to none for every other model without
     * triggering an undefined-property warning.
     */
    protected function isTimezoneExempt(string $key): bool
    {
        return in_array($key, $this->timezoneExempt ?? [], true);
    }
}
