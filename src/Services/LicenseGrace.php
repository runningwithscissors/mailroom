<?php

namespace BisonDigital\Mailroom\Services;

/**
 * Licence state with a grace period, for a commercial ExpressionEngine add-on.
 *
 * PORTABLE. Nothing in this file knows it is in Mason. To reuse it in another add-on, copy the
 * file and change the `namespace` line above — that is the whole port. Everything add-on-specific
 * (shortname, label, where the clock is stored) is constructor arguments.
 *
 * Deliberately copied per add-on rather than shared under one namespace: ExpressionEngine loads
 * whichever copy of a class name it meets first, so two add-ons shipping the same fully-qualified
 * class would silently share one implementation, and the older one could win. A copy per namespace
 * costs a re-copy when this improves and removes that failure entirely.
 *
 * ── What it decides ────────────────────────────────────────────────────────────
 *
 *   licensed   the licence is fine, or this is a dev host
 *   grace      something is wrong, and it was first noticed less than GRACE_DAYS ago
 *   lapsed     something is wrong and the grace period has run out
 *
 * What an add-on DOES with those is its own business. The recommendation, learned the hard way:
 * show a notice and never disable editing. An author staring at a read-only editor cannot fix a
 * billing problem, usually does not know what a licence is, and loses their afternoon to it — and
 * the person who can fix it is not the one looking at the screen.
 *
 * ── Why the clock runs from the first PROBLEM, not from install ────────────────
 *
 * A grace period measured from installation only ever helps a new site waiting on a licence. The
 * commoner support case is a site licensed for two years whose renewal lapses: its install date is
 * long past, so it would hit the hardest state the same afternoon the card expired. Measuring from
 * the first bad status gives both the same week, and clearing the clock on success means a site
 * that lapses, renews and lapses again next year gets the week each time rather than having spent
 * it in year one.
 *
 * ── The statuses, and the trap in them ─────────────────────────────────────────
 *
 * ExpressionEngine's Addon::checkCachedLicenseResponse() returns the ADD-ON's status unless that
 * status is `valid` while the EE INSTALL has no valid licence, in which case it returns the CORE
 * licence status instead:
 *
 *     if ($addonStatus === 'valid' && $data['validLicense'] === false) {
 *         return $data['licenseStatus'];
 *     }
 *
 * So two vocabularies arrive here. The add-on's own — valid, trial, na, invalid, expired — and
 * EE's core statuses: missing_license_key, invalid_license_key, invalid_domain, license_expired,
 * update_available. Every one of the second group means *this add-on is licensed and EE is not*,
 * because only a `valid` add-on status reaches that branch at all.
 *
 * Hence UNENTITLED is a blocklist of the two values that actually name the add-on, never an
 * allowlist of the good ones. An allowlist punishes a customer for their EE licence, punishes them
 * for `update_available` — which means only that a newer version exists — and produces the
 * paradox that the add-on behaves while UNregistered (status `na`) and complains the moment its
 * licence starts working. Note EE's core expiry is `license_expired`, distinct from `expired`.
 *
 * Also note the status is only ever as fresh as EE's cache, which is written by ExpressionEngine's
 * JS POSTing back to the control panel (Controller/License/License.php). It updates when somebody
 * loads a CP page in a browser, not when files are deployed.
 *
 * ── Dropping this into another add-on ──────────────────────────────────────────
 *
 *   1. Copy this file to <addon>/Service/LicenseGrace.php and change the namespace line.
 *   2. Wherever the add-on renders its control panel or its field, show the notice:
 *
 *          $license = new LicenseGrace('tab_ee', 'Tab-EE');
 *          $notice  = $license->notice();      // '' when there is nothing to say
 *
 *      There is nothing else to wire. With no storage callables it keeps the clock in EE's own
 *      exp_config table, which every install has, so an add-on with no settings table of its own
 *      needs no migration and no new table.
 *   3. If the add-on already has a settings store, hand it one instead, as Mason does in
 *      Service/Bouncer.php:
 *
 *          new LicenseGrace('mason', 'Mason', [
 *              'read'  => function ($key) { … return string … },
 *              'write' => function ($key, $value) { … },
 *          ]);
 *
 *   4. Do NOT gate writes, disable inputs, or make an editor read-only on the result. Show the
 *      notice. The reasoning is above and it was learned from Mason doing the opposite.
 *
 * A site can be marked as development for testing with a config array — `<shortname>_dev_domains`
 * or the shared `bison_dev_domains` — merged with DEV_SUFFIXES rather than replacing it.
 *
 * The publisher's own production sites go in `<shortname>_licensed_domains`, or the shared
 * `bison_licensed_domains` so one line in a site's config covers every add-on that carries this
 * file. Exact host match, so listing a domain cannot accidentally cover one that merely ends the
 * same way. This is not a security boundary and is not pretending to be one: anyone who can edit
 * config.php can already edit this file. Like all add-on licensing that runs on the customer's own
 * server, it is a speed bump for the honest, not a lock.
 */
class LicenseGrace
{
    /** Statuses that name THIS add-on as unlicensed. Everything else is fine. See the docblock. */
    const UNENTITLED = ['invalid', 'expired'];

    /** Days between the first sighting of a problem and the notice hardening. */
    const GRACE_DAYS = 7;

    /** Hosts always treated as development. Extended, not replaced, by config. */
    const DEV_SUFFIXES = ['.ddev.site', '.test', '.local', '.localhost', 'localhost', '127.0.0.1'];

    private $shortname;
    private $label;
    private $graceDays;
    private $devConfigKeys;
    private $ownConfigKeys;
    private $read;
    private $write;

    private $status = null;
    private $since = null;

    /**
     * @param string $shortname The add-on's slug, as EE knows it ('mason').
     * @param string $label     Human name for the notice ('Mason').
     * @param array  $opts      graceDays, devConfigKeys, and read/write callables for storage.
     *                          Storage defaults to EE's own exp_config table, which exists on
     *                          every install — so an add-on with no settings table of its own
     *                          needs to supply nothing.
     */
    public function __construct($shortname, $label, array $opts = [])
    {
        $this->shortname = (string) $shortname;
        $this->label = (string) $label;
        $this->graceDays = isset($opts['graceDays']) ? (int) $opts['graceDays'] : self::GRACE_DAYS;
        $this->devConfigKeys = isset($opts['devConfigKeys'])
            ? (array) $opts['devConfigKeys']
            : [$this->shortname . '_dev_domains', 'bison_dev_domains'];
        $this->ownConfigKeys = isset($opts['ownConfigKeys'])
            ? (array) $opts['ownConfigKeys']
            : [$this->shortname . '_licensed_domains', 'bison_licensed_domains'];
        $this->read = isset($opts['read']) && is_callable($opts['read']) ? $opts['read'] : null;
        $this->write = isset($opts['write']) && is_callable($opts['write']) ? $opts['write'] : null;
    }

    // -- state ------------------------------------------------------------------

    /** EE's cached licence status for this add-on, memoized. 'na' when it cannot be resolved. */
    public function status()
    {
        if ($this->status !== null) {
            return $this->status;
        }

        $status = 'na';

        try {
            $addon = ee('Addon')->get($this->shortname);
            if ($addon && method_exists($addon, 'checkCachedLicenseResponse')) {
                $resolved = $addon->checkCachedLicenseResponse();
                // Every failure path in EE returns bool false; only a string is an answer.
                if (is_string($resolved) && $resolved !== '') {
                    $status = $resolved;
                }
            }
        } catch (\Exception $e) {
            $status = 'na'; // licensing must never be the thing that breaks the control panel
        }

        return $this->status = $status;
    }

    /** True on a local/dev host. */
    public function isDev()
    {
        $host = $this->host();
        if ($host === '') {
            return false;
        }

        $needles = self::DEV_SUFFIXES;
        foreach ($this->devConfigKeys as $key) {
            $extra = ee()->config->item($key);
            if (is_array($extra)) {
                $needles = array_merge($needles, $extra);
            }
        }

        foreach ($needles as $needle) {
            $needle = strtolower((string) $needle);
            if ($needle === '') {
                continue;
            }
            if ($host === $needle || substr($host, -strlen($needle)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Domains the publisher owns, from config, treated as licensed.
     *
     * Kept apart from the dev-host list on purpose: a production site someone owns is not a
     * development site, and folding the two together would spend a staging-versus-live
     * distinction that is worth still having later.
     *
     * Exact host match only — no suffix matching, so listing `example.com` cannot accidentally
     * cover `notexample.com`. Subdomains are listed individually.
     */
    public function isOwnDomain()
    {
        $host = $this->host();
        if ($host === '') {
            return false;
        }

        foreach ($this->ownConfigKeys as $key) {
            $domains = ee()->config->item($key);
            if (!is_array($domains)) {
                continue;
            }
            foreach ($domains as $domain) {
                if ($host === strtolower(trim((string) $domain))) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Whether the licence itself is in order. NOT "may they edit" — they always may. */
    public function isLicensed()
    {
        if ($this->isDev() || $this->isOwnDomain()) {
            return true;
        }

        return !in_array($this->status(), self::UNENTITLED, true);
    }

    /** 'licensed' | 'grace' | 'lapsed'. */
    public function state()
    {
        if ($this->isLicensed()) {
            $this->clear();

            return 'licensed';
        }

        $since = $this->since();
        if ($since <= 0) {
            return 'grace'; // storage unavailable: stay soft rather than harden on a guess
        }

        return (time() - $since) < ($this->graceDays * 86400) ? 'grace' : 'lapsed';
    }

    /** Whole days left in the grace period; 0 once spent. */
    public function daysLeft()
    {
        $since = $this->since();
        if ($since <= 0) {
            return $this->graceDays;
        }

        $left = ($this->graceDays * 86400) - (time() - $since);

        return $left > 0 ? (int) ceil($left / 86400) : 0;
    }

    /**
     * A sentence to show, or '' when there is nothing to say.
     *
     * Names what is wrong, who can fix it, and — importantly — that nothing has stopped working,
     * because the first thing anyone seeing this wants to know is whether their work is at risk.
     */
    public function notice()
    {
        $state = $this->state();
        if ($state === 'licensed') {
            return '';
        }

        if ($state === 'grace') {
            $days = $this->daysLeft();

            return $this->label . ' is not licensed on this domain. '
                . 'Editing continues as normal for ' . $days . ' more ' . ($days === 1 ? 'day' : 'days') . ' — '
                . 'add a licence key in the control panel to clear this.';
        }

        return $this->label . ' is not licensed on this domain. '
            . 'Nothing has been disabled and your content is unaffected, but this notice will stay '
            . 'until a licence key is added.';
    }

    // -- the clock --------------------------------------------------------------

    /** When the problem was first seen, starting the clock on first sighting. 0 if unstorable. */
    private function since()
    {
        if ($this->since !== null) {
            return $this->since;
        }

        $stored = (int) $this->get();
        if ($stored > 0) {
            return $this->since = $stored;
        }

        $now = time();
        $this->set((string) $now);

        return $this->since = $now;
    }

    /** Stop the clock, so a site that lapses again later gets the full period again. */
    private function clear()
    {
        if ($this->since === 0) {
            return;
        }
        if ($this->get() !== '') {
            $this->set('');
        }
        $this->since = 0;
    }

    private function storageKey()
    {
        return $this->shortname . '_license_grace_since';
    }

    private function get()
    {
        if ($this->read) {
            return (string) call_user_func($this->read, $this->storageKey());
        }

        try {
            $row = ee()->db->select('value')
                ->where('key', $this->storageKey())
                ->where('site_id', (int) ee()->config->item('site_id'))
                ->get('config')->row('value');

            return (string) ($row ?? '');
        } catch (\Exception $e) {
            return '';
        }
    }

    private function set($value)
    {
        if ($this->write) {
            call_user_func($this->write, $this->storageKey(), $value);

            return;
        }

        try {
            $site = (int) ee()->config->item('site_id');
            $exists = ee()->db->select('config_id')
                ->where('key', $this->storageKey())
                ->where('site_id', $site)
                ->get('config')->row('config_id');

            if ($exists) {
                ee()->db->where('config_id', $exists)->update('config', ['value' => $value]);
            } else {
                ee()->db->insert('config', [
                    'site_id' => $site,
                    'key'     => $this->storageKey(),
                    'value'   => $value,
                ]);
            }
        } catch (\Exception $e) {
            // Unstorable: state() stays in 'grace', which is the forgiving direction.
        }
    }

    private function host()
    {
        $host = '';
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        }

        $host = strtolower((string) $host);
        if (($pos = strpos($host, ':')) !== false) {
            $host = substr($host, 0, $pos);
        }

        return $host;
    }
}
