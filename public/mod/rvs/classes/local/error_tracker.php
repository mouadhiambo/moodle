<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_rvs\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores the last generation error per module so the UI can surface real failures.
 */
class error_tracker {
    /** @var string Prefix used when persisting error messages via set_config. */
    private const CONFIG_PREFIX = 'lastrvs_error_';

    /**
     * Remember the most recent error for the given module.
     */
    public static function store(int $rvsid, string $module, string $message): void {
        if ($rvsid <= 0 || $module === '') {
            return;
        }

        set_config(self::build_key($rvsid, $module), trim($message), 'mod_rvs');
    }

    /**
     * Clear any stored error for the given module.
     */
    public static function clear(int $rvsid, string $module): void {
        if ($rvsid <= 0 || $module === '') {
            return;
        }

        unset_config(self::build_key($rvsid, $module), 'mod_rvs');
    }

    /**
     * Retrieve the most recent error message for the module, if any.
     */
    public static function get(int $rvsid, string $module): ?string {
        if ($rvsid <= 0 || $module === '') {
            return null;
        }

        $value = get_config('mod_rvs', self::build_key($rvsid, $module));
        return $value !== false && $value !== '' ? $value : null;
    }

    /**
     * Build the config key for the module/rvs combination.
     */
    private static function build_key(int $rvsid, string $module): string {
        return self::CONFIG_PREFIX . $module . '_' . $rvsid;
    }
}
