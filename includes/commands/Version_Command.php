<?php
/**
 * @package CzarSoft\WP_CLI_Freemius_Toolkit
 */

namespace CzarSoft\WP_CLI_Freemius_Toolkit\Commands;

use ZipArchive;
use WP_CLI;
use WP_CLI\Utils as CLI_Utils;
use CzarSoft\WP_CLI_Freemius_Toolkit\Helpers\Freemius;
use CzarSoft\WP_CLI_Freemius_Toolkit\Helpers\Utils;

/**
 * @when before_wp_load
 */
class Version_Command extends \WP_CLI_Command
{
    const ARCHIVE_NAME = 'justified-gallery.zip';
    const SLUG = 'justified-gallery';

    /**
     * Deploy new version of the plugin
     *
     * ## OPTIONS
     *
     * [--local]
     * : Create a zip archive without deploying it to the API.
     *
     * [--add-freemius-contributor]
     * : Add Freemius as contributor of plugin. Default: false
     */
    public function deploy($args, $assoc_args)
    {
        $local = CLI_Utils\get_flag_value($assoc_args, 'local');
        $add_contributor = CLI_Utils\get_flag_value($assoc_args, 'add-freemius-contributor', false);
        $add_contributor = $add_contributor !== false;

        $api = Freemius::get_api('developer');
        $freemius_conf = Freemius::get_conf();

        if (empty($freemius_conf['include'])) {
            WP_CLI::error('The .freemius file does not contain the "include" key.');
        }

        $package_path = getcwd() . DIRECTORY_SEPARATOR . self::ARCHIVE_NAME;
        WP_CLI::print_value($package_path);
//        $package_path = self::ARCHIVE_NAME;

        $zip = new ZipArchive;
        if (file_exists($package_path)) {
            unlink($package_path);
        }

        /*
        $zip_result = false;
        // zip creation
        // based on https://gist.github.com/menzerath/4185113/72db1670454bd707b9d761a9d5e83c54da2052ac
        if ($zip->open($package_path, \ZIPARCHIVE::CREATE)) {
            foreach ($freemius_conf['include'] as $source) {
                $source = trim($source, '/\\');
                if (is_dir($source)) {
                    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($files as $file) {
                        if (Utils::endsWith($file, '.') || Utils::endsWith($file, '..')) {
                            continue;
                        }
                        if (is_dir($file) === true) {
                            $zip->addEmptyDir($file);
                        } else if (is_file($file) === true) {
                            $zip->addFile($file, str_replace($source . '/', '', $file));
                        }
                    }
                } else if (is_file($source)) {
                    $zip->addFile($source, basename($source));
                }
            }
            $zip_result = $zip->close();
        }
        */


//        $zip->saveAsFile($package_path) // save the archive to a file
//        ->close(); // close archive
//        if (!$zip_result) {
//            WP_CLI::error('Failed to create archive with plugin files.');
//        }

        try{
            $zip = new \PhpZip\ZipFile();
            foreach ($freemius_conf['include'] as $source) {
                $source = trim($source, '/\\');
                if (is_dir($source)) {
//                    $zip->addDir(__DIR__, 'to/path/');
                    $zip->addDirRecursive($source, $source, \PhpZip\Constants\ZipCompressionMethod::STORED);
//                    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
//                    foreach ($files as $file) {
//                        if (Utils::endsWith($file, '.') || Utils::endsWith($file, '..')) {
//                            continue;
//                        }
//                        if (is_dir($file) === true) {
//                            $zip->addEmptyDir($file);
//                        } else if (is_file($file) === true) {
//                            $zip->addFile($file, str_replace($source . '/', '', $file));
//                        }
//                    }
                } else if (is_file($source)) {
                    $zip->addFile($source, basename($source), \PhpZip\Constants\ZipCompressionMethod::STORED);
                }
            }
            $zip->saveAsFile($package_path); // save the archive to a file
        }
        catch(\PhpZip\Exception\ZipException $e){
            WP_CLI::error('Failed to create archive with plugin files.');
        }
        finally{
            $zip->close();
        }

        if ($local) {
            WP_CLI::success(sprintf('The zip archive "%s" has been successfully created.', self::ARCHIVE_NAME));
        } else {
            // TODO sprawdzic czy taka wersja nie jest już opublikowana
            // TODO poprawic patchem Freemius.php gdzie spradzany jest typ pliku
            $result = $api->Api('/plugins/' . $freemius_conf['plugin_id'] . '/tags.json', 'POST', array(
                'add_contributor' => $add_contributor
            ), array(
                'file' => $package_path
//                'file' => getcwd() . DIRECTORY_SEPARATOR . 'a.zip'
            ));
//            if (file_exists($package_path)) {
//                unlink($package_path);
//            }
            if (isset($result->error->message)) {
                WP_CLI::error($result->error->message);
            }
            WP_CLI::print_value($result);
            WP_CLI::success('The new version has been successfully deployed:');
            //
            // TODO spradzic czy mamy poprawny result
            $this->show_tags([$result], $assoc_args);
            // TODO mozna dodać argument, który od razu ustawi release_mode na 'beta' lub 'released' (jako osobny request)
            //
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
     * @subcommand delete
     */
    public function delete_($args, $assoc_args)
    {
        $api = Freemius::get_api('developer');
        $freemius_conf = Freemius::get_conf();
        foreach ($args as $id) {
            $result = $api->Api('plugins/' . $freemius_conf['plugin_id'] . '/tags/' . $id . '.zip', 'DELETE');
            if (isset($result->error->message)) {
                WP_CLI::error($result->error->message);
            }
            WP_CLI::print_value($result);
        }
        // TODO sprawdzic czy jest obiekt $result->tags
//        $tags = $result->tags;
//        $this->show_tags($tags, $assoc_args);
    }

    /**
     * Lists plugin's versions
     * @subcommand list
     */
    public function list_($args, $assoc_args)
    {
        $api = Freemius::get_api('developer');
        $freemius_conf = Freemius::get_conf();
        $result = $api->Api('plugins/' . $freemius_conf['plugin_id'] . '/tags.json');
        if (isset($result->error->message)) {
            WP_CLI::error($result->error->message);
        }
        // TODO sprawdzic czy jest obiekt $result->tags
        $tags = $result->tags;
        $this->show_tags($tags, $assoc_args);
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