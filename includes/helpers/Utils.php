<?php


namespace CzarSoft\WP_CLI_Freemius_Toolkit\Helpers;


class Utils
{
    public static function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    /**
     * Retrieve metadata from a file.
     *
     * Searches for metadata in the first 8 KB of a file, such as a plugin or theme.
     * Each piece of metadata must be on its own line. Fields can not span multiple
     * lines, the value will get cut at the end of the first line.
     *
     * @param string $file Absolute path to the file.
     * @param array $headers List of headers, in the format `array( 'HeaderKey' => 'Header Name' )`.
     * @return string[] Array of file header values keyed by header name.
     */
    public static function get_file_data($file, $headers)
    {
        // We don't need to write to the file, so just open for reading.
        $fp = fopen($file, 'r');

        // Pull only the first 8 KB of the file in.
        $file_data = fread($fp, 8 * 1024);

        // PHP will close file handle, but we are good citizens.
        fclose($fp);

        // Make sure we catch CR-only line endings.
        $file_data = str_replace("\r", "\n", $file_data);

        foreach ($headers as $field => $regex) {
            if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $file_data, $match) && $match[1]) {
                $headers[$field] = self::cleanup_header_comment($match[1]);
            } else {
                $headers[$field] = '';
            }
        }

        return $headers;
    }

    /**
     * Strip close comment and close php tags from file headers used by WP.
     *
     * @param string $str Header comment to clean up.
     * @return string
     */
    private static function cleanup_header_comment($str)
    {
        return trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $str));
    }
}
