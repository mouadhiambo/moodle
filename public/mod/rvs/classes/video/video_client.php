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

namespace mod_rvs\video;

defined('MOODLE_INTERNAL') || die();

/**
 * Video generation client that chooses a provider based on available credentials.
 * Supports OpenAI Sora and Google Gemini Nano Banana via configurable endpoints.
 *
 * @package    mod_rvs
 */
class video_client {

    /**
     * Generate a video binary from a script using the first available provider.
     *
     * @param string $script
     * @return array [binary => string, mimetype => string, extension => string]
     * @throws \moodle_exception When no provider is configured or request fails
     */
    public static function generate(string $script): array {
        $oaiKey = get_config('mod_rvs', 'openai_video_api_key');
        $gKey = get_config('mod_rvs', 'google_video_api_key');

        if (!empty($oaiKey)) {
            return self::generate_with_openai($script, $oaiKey);
        }

        if (!empty($gKey)) {
            return self::generate_with_google($script, $gKey);
        }

        throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'No video provider credentials available');
    }

    /**
     * Generate using OpenAI Sora (configurable endpoint/model).
     *
     * @param string $script
     * @param string $apikey
     * @return array
     * @throws \moodle_exception
     */
    private static function generate_with_openai(string $script, string $apikey): array {
        $endpoint = rtrim(get_config('mod_rvs', 'openai_video_endpoint') ?: 'https://api.openai.com/v1/videos', '/');
        $model = get_config('mod_rvs', 'openai_video_model') ?: 'sora-1.0';

        $url = $endpoint;

        $payload = json_encode([
            'model' => $model,
            'prompt' => $script,
            // You may add duration, size, etc. via settings later.
        ]);

        $curl = new \curl();
        $headers = [
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
        ];
        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'RETURNTRANSFER' => true,
            'FRESH_CONNECT' => true,
            'FORBID_REUSE' => true,
            'CONNECTTIMEOUT' => 30,
            'TIMEOUT' => 600,
        ];

        $response = $curl->post($url, $payload, $options);
        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;

        if ($response === false || $httpcode < 200 || $httpcode >= 300) {
            $errdetail = !empty($httpcode) ? ('HTTP ' . $httpcode) : ($curl->error ?? 'Unknown cURL error');
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'OpenAI video request failed: ' . $errdetail);
        }

        // Assume binary video is returned directly; if JSON->URL is returned, this must be adjusted.
        $mimetype = 'video/mp4';
        $extension = 'mp4';
        return [
            'binary' => $response,
            'mimetype' => $mimetype,
            'extension' => $extension,
        ];
    }

    /**
     * Generate using Google Gemini Nano Banana (configurable endpoint/model).
     *
     * @param string $script
     * @param string $apikey
     * @return array
     * @throws \moodle_exception
     */
    private static function generate_with_google(string $script, string $apikey): array {
        $base = rtrim(get_config('mod_rvs', 'google_video_endpoint') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = get_config('mod_rvs', 'google_video_model') ?: 'gemini-nano-banana';

        $url = $base . '/models/' . rawurlencode($model) . ':generateVideo?key=' . rawurlencode($apikey);

        $payload = json_encode([
            'prompt' => [
                'text' => $script,
            ],
        ]);

        $curl = new \curl();
        $headers = [
            'Content-Type: application/json',
        ];
        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'RETURNTRANSFER' => true,
            'FRESH_CONNECT' => true,
            'FORBID_REUSE' => true,
            'CONNECTTIMEOUT' => 30,
            'TIMEOUT' => 600,
        ];

        $response = $curl->post($url, $payload, $options);
        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;

        if ($response === false || $httpcode < 200 || $httpcode >= 300) {
            $errdetail = !empty($httpcode) ? ('HTTP ' . $httpcode) : ($curl->error ?? 'Unknown cURL error');
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'Google video request failed: ' . $errdetail);
        }

        // Assume binary is returned; adjust if API returns JSON with URL or base64.
        $mimetype = 'video/mp4';
        $extension = 'mp4';
        return [
            'binary' => $response,
            'mimetype' => $mimetype,
            'extension' => $extension,
        ];
    }
}


