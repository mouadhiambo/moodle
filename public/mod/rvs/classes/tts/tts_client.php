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

namespace mod_rvs\tts;

defined('MOODLE_INTERNAL') || die();

/**
 * Simple TTS client wrapper for ElevenLabs API.
 *
 * @package    mod_rvs
 */
class tts_client {

	/**
     * Synthesize audio for given text using configured provider.
     *
     * @param string $text
     * @param string $format One of mp3|wav|ogg
     * @return array [binary => string, mimetype => string, extension => string]
     * @throws \moodle_exception on configuration or HTTP errors
     */
    public static function synthesize(string $text, string $format = 'mp3'): array {
        $provider = get_config('mod_rvs', 'tts_provider') ?: 'elevenlabs';
        if ($provider !== 'elevenlabs') {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'Unsupported TTS provider: ' . $provider);
        }

        $apikey = get_config('mod_rvs', 'tts_api_key');
        $endpoint = rtrim(get_config('mod_rvs', 'tts_endpoint') ?: 'https://api.elevenlabs.io', '/');
        $voiceid = get_config('mod_rvs', 'tts_voice_id');
        $format = strtolower($format ?: 'mp3');

        if (empty($apikey) || empty($voiceid)) {
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'TTS is enabled but API key or voice ID is not configured.');
        }

        $url = $endpoint . '/v1/text-to-speech/' . rawurlencode($voiceid);

        $accept = 'audio/mpeg';
        $extension = 'mp3';
        if ($format === 'wav') { $accept = 'audio/wav'; $extension = 'wav'; }
        if ($format === 'ogg') { $accept = 'audio/ogg'; $extension = 'ogg'; }

        $payload = json_encode([
            'text' => $text,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ],
        ]);

        $curl = new \curl();
        $headers = [
            'Accept: ' . $accept,
            'Content-Type: application/json',
            'xi-api-key: ' . $apikey,
        ];

        $options = [
            'CURLOPT_HTTPHEADER' => $headers,
            'RETURNTRANSFER' => true,
            'FRESH_CONNECT' => true,
            'FORBID_REUSE' => true,
            'CONNECTTIMEOUT' => 20,
            'TIMEOUT' => 120,
        ];

        $response = $curl->post($url, $payload, $options);
        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;

        if ($response === false || $httpcode < 200 || $httpcode >= 300) {
            $err = method_exists($curl, 'error') ? $curl->error : 'HTTP ' . $httpcode;
            throw new \moodle_exception('aigenerationfailed', 'mod_rvs', '', null, 'TTS request failed: ' . $err);
        }

        $mimetype = $accept;
        return [
            'binary' => $response,
            'mimetype' => $mimetype,
            'extension' => $extension,
        ];
    }
}


