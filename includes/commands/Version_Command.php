<?php
/**
 * @package CzarSoft\WP_CLI_Freemius_Toolkit
 */

namespace CzarSoft\WP_CLI_Freemius_Toolkit\Commands;

use CzarSoft\WP_CLI_Freemius_Toolkit\Helpers\Freemius;
use CzarSoft\WP_CLI_Freemius_Toolkit\Helpers\Utils;
use PhpZip\ZipFile;
use PhpZip\Exception\ZipException;
use WP_CLI;
use WP_CLI\Utils as CLI_Utils;

/**
 * @when before_wp_load
 */
class Version_Command extends \WP_CLI_Command
{
    const ARCHIVE_NAME = 'new-version.zip';

    /**
     * Deploy new version of the plugin.
     *
     * ## OPTIONS
     *
     * [--local]
     * : Create a zip archive without deploying it to the API.
     *
     * [--add-freemius-contributor]
     * : Add Freemius as contributor of plugin. Default: false
     *
     * [--force]
     * : Force update if version already exists.
     *
     * [--fields=<fields>]
     * : Limit info about deployed version to specific fields. Defaults to all fields.
     *
     * [--format=<format>]
     * : Render info about deployed version in a particular format.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - ids
     *   - json
     *   - yaml
     * ---
     *
     * ## AVAILABLE FIELDS
     *
     * These fields will be displayed by default for deployed version:
     *
     * * id
     * * version
     * * sdk_version
     * * requires_platform_version
     * * requires_platform_version
     * * tested_up_to_version
     * * downloaded
     * * release_mode
     * * created
     * * updated
     */
    public function deploy($args, $assoc_args)
    {
        $local = CLI_Utils\get_flag_value($assoc_args, 'local');
        $add_contributor = (bool)CLI_Utils\get_flag_value($assoc_args, 'add-freemius-contributor', false);
        $force_update = (bool)CLI_Utils\get_flag_value($assoc_args, 'force', false);

        $api = Freemius::get_api('developer');
        $freemius_conf = Freemius::get_conf();

        if (empty($freemius_conf['include'])) {
            WP_CLI::error('The .freemius file does not contain the "include" key.');
        }

        $package_path = getcwd() . DIRECTORY_SEPARATOR . self::ARCHIVE_NAME;

        if (file_exists($package_path)) {
            unlink($package_path);
        }

        try {
            $zip = new ZipFile();
            foreach ($freemius_conf['include'] as $source) {
                $source = trim($source, '/\\');
                if (is_dir($source)) {
                    $source .= '/';
                    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($files as $file) {
                        if (Utils::endsWith($file, '.') || Utils::endsWith($file, '..')) {
                            continue;
                        }
                        $file = str_replace('\\', '/', $file);
                        if (is_dir($file) === true) {
                            $file = trim($file, '/');
                            $file .= '/';
                            $zip->addEmptyDir($file);
                        } else if (is_file($file) === true) {
                            $zip->addFile($file, str_replace($source . '/', '', $file));
                        }
                    }
                } else if (is_file($source)) {
                    $zip->addFile($source, basename($source));
                }
            }
            $zip->saveAsFile($package_path);
        } catch (ZipException $e) {
            WP_CLI::error('Failed to create archive with plugin files.');
        } finally {
            $zip->close();
        }

        if ($local) {
            WP_CLI::success(sprintf('The zip archive "%s" has been successfully created.', self::ARCHIVE_NAME));
        } else {
            $local_version = $this->get_plugin_header('Version');
            $is_update = false;

            WP_CLI::log(sprintf("Checking if version %s already exists...", $local_version));
            if ($this->version_exists($local_version)) {
                $is_update = true;
                if (!$force_update) {
                    WP_CLI::confirm(sprintf('Version %s already exists. Do you want to update its files?', $local_version), $assoc_args);
                }
            }

            if ($is_update) {
                WP_CLI::log("Updating existing version...");
            } else {
                WP_CLI::log("Deploying new version...");
            }
            $result = $api->Api('/plugins/' . $freemius_conf['plugin_id'] . '/tags.json', 'POST', array(
                'add_contributor' => $add_contributor
            ), array(
                'file' => $package_path
            ));

            if (file_exists($package_path)) {
                unlink($package_path);
            }
            if (isset($result->error->message)) {
                WP_CLI::error($result->error->message);
            }
            if (!isset($result->id)) {
                WP_CLI::error($result);
            }
            if ($is_update) {
                WP_CLI::success(sprintf('The version %s has been successfully updated.', $local_version));
            } else {
                WP_CLI::success(sprintf('The new version %s has been successfully deployed.', $local_version));
            }
            $this->show_tags([$result], $assoc_args);
            // TODO mozna dodać argument, który od razu ustawi release_mode na 'beta' lub 'released' (jako osobny request)
        }
    }

    /**
     * Delete an existing version.
     *
     * ## OPTIONS
     *
     * <id>...
     * : One or more IDs of versions to delete.
     *
     * [--yes]
     * : Answer yes to the confirmation message.
     *
     * @subcommand delete
     */
    public function delete_($args, $assoc_args)
    {
        WP_CLI::confirm('Are you sure you want to delete this version?', $assoc_args);

        $api = Freemius::get_api('developer');
        $freemius_conf = Freemius::get_conf();
        foreach ($args as $id) {
            $result = $api->Api('plugins/' . $freemius_conf['plugin_id'] . '/tags/' . $id . '.zip', 'DELETE');
            if (isset($result->error->message)) {
                WP_CLI::error($result->error->message);
            }
            WP_CLI::success(sprintf('Version %s has been successfully deleted.', $id));
        }
    }

    /**
     * Download plugin version.
     *
     * ## OPTIONS
     *
     * <id>...
     * : ID of version to download.
     *
     * [--premium]
     * : Retrieve a premium version of the plugin.
     *
     * [--file=<file>]
     * : The name of the plugin file that will be saved to disk. Eg. "my-plugin.zip".
     *
     */
    public function download($args, $assoc_args)
    {
        if (intval($args[0]) <= 0) {
            WP_CLI::error('Invalid ID');
        }
        $premium = (bool)CLI_Utils\get_flag_value($assoc_args, 'premium', false);
        $filename = CLI_Utils\get_flag_value($assoc_args, 'file');

        $api = Freemius::get_api('developer');
        $freemius_conf = Freemius::get_conf();

        $plugin_slug = basename(getcwd());

        // API redirects to AWS S3, so we need allow to follow this redirect
        $api::$CURL_OPTS[CURLOPT_FOLLOWLOCATION] = true;
        $result = $api->Api('plugins/' . $freemius_conf['plugin_id'] . '/tags/' . $args[0] . '.zip' . ($premium ? '?is_premium=true' : ''), 'GET');
        if (isset($result->error->message)) {
            WP_CLI::error($result->error->message);
        }
        if (empty($result)) {
            WP_CLI::error('Invalid response.');
        }

        $ver = $this->get_single_version_by('id', $args[0]);
        if (empty($filename)) {
            $filename = sprintf('%s-%s%s.zip', $plugin_slug, isset($ver['version']) ? $ver['version'] : '', $premium ? '-premium' : '');
        }
        if (!file_put_contents($filename, $result)) {
            WP_CLI::error('Unable to save file.');
        }

        WP_CLI::success(sprintf('File %s has been successfully downloaded.', $filename));
    }

    /**
     * List plugin versions.
     *
     * ## OPTIONS
     * [--count=<count>]
     * : Limit the number of versions returned. Maximum: 50.
     * ---
     * default: 25
     *
     * [--fields=<fields>]
     * : Limit the output to specific fields. Defaults to all fields.
     *
     * [--format=<format>]
     * : Render output in a particular format.
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - ids
     *   - json
     *   - yaml
     * ---
     *
     * ## AVAILABLE FIELDS
     *
     * These fields will be displayed by default for each version:
     *
     * * id
     * * version
     * * sdk_version
     * * requires_platform_version
     * * requires_platform_version
     * * tested_up_to_version
     * * downloaded
     * * release_mode
     * * created
     * * updated
     *
     * @subcommand list
     */
    public function list_($args, $assoc_args)
    {
        if (intval($assoc_args['count']) <= 0 || intval($assoc_args['count']) > 50) {
            $assoc_args['count'] = 25;
        }

        $api = Freemius::get_api('developer');
        $freemius_conf = Freemius::get_conf();
        $params = array(
            'count' => $assoc_args['count'],
        );
        $result = $api->Api('plugins/' . $freemius_conf['plugin_id'] . '/tags.json?' . http_build_query($params));
        if (isset($result->error->message)) {
            WP_CLI::error($result->error->message);
        }
        if (!isset($result->tags)) {
            WP_CLI::error('Invalid API response.');
        }
        $tags = $result->tags;
        $this->show_tags($tags, $assoc_args);
    }

    private function version_exists($version)
    {
        $ver = $this->get_single_version_by('version', $version);

        return $ver !== false;
    }

    private function get_single_version_by($field, $version)
    {
        $versions = WP_CLI::runcommand('freemius-toolkit version list --format=json --count=50', array('return' => true));
        $versions = WP_CLI::read_value($versions, array('format' => 'json'));
        if (!is_array($versions)) {
            WP_CLI::error('Unable to get list of versions');
        }
        foreach ($versions as $ver) {
            if (isset($ver[$field]) && $ver[$field] === $version) {
                return $ver;
            }
        }

        return false;
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    private function get_local_version()
    {
        foreach (new \DirectoryIterator(getcwd()) as $file) {
            if (!$file->isFile() || strpos($file->getFilename(), '.php') === false) {
                continue;
            }
            $headers = Utils::get_file_data($file->getFilename(), array('Version' => 'Version'));
            if (!empty($headers['Version'])) {
                return $headers['Version'];
            }
        }
        return '';
    }

    private function get_plugin_header($header)
    {
        foreach (new \DirectoryIterator(getcwd()) as $file) {
            if (!$file->isFile() || strpos($file->getFilename(), '.php') === false) {
                continue;
            }
            $headers = Utils::get_file_data($file->getFilename(), array($header => $header));
            if (!empty($headers[$header])) {
                return $headers[$header];
            }
        }
        return '';
    }

    private function show_tags($tags, $assoc_args)
    {
        $default_fields = array(
            'id',
            'version',
            'sdk_version',
            'requires_platform_version',
            'tested_up_to_version',
            'downloaded',
            'release_mode',
            'created',
            'updated',
//            'is_released',
        );
        $defaults = array(
            'fields' => implode(',', $default_fields),
            'format' => 'table'
        );
        $assoc_args = array_merge($defaults, $assoc_args);

        $list = array();
        foreach ($tags as $tag) {
            $tag_output = array();
            $tag_output['id'] = $tag->id;
            $tag_output['version'] = $tag->version;
            $tag_output['sdk_version'] = $tag->sdk_version;
            $tag_output['requires_platform_version'] = $tag->requires_platform_version;
            $tag_output['tested_up_to_version'] = $tag->tested_up_to_version;
            $tag_output['downloaded'] = $tag->downloaded;
            $tag_output['release_mode'] = $tag->release_mode;
            $tag_output['created'] = $tag->created;
            $tag_output['updated'] = $tag->updated;
//            $tag_output['is_released'] = $tag->is_released;
            $list[$tag_output['version']] = $tag_output;
        }

        if ('ids' === $assoc_args['format']) {
            $list = array_keys($list);
        }
        CLI_Utils\format_items($assoc_args['format'], $list, $assoc_args['fields']);
    }
}

WP_CLI::add_command('freemius-toolkit version', __NAMESPACE__ . '\Version_Command', array('when' => 'before_wp_load'));
