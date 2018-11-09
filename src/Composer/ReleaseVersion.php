<?php

namespace Drupal\pco_cities\Composer;

use Drupal\pco_cities\IniEncoder;
use Composer\Script\Event;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Updates the version number in pco_cities's component info files.
 */
class ReleaseVersion {

  /**
   * Script entry point.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function execute(Event $event) {
    $arguments = $event->getArguments();
    $version = reset($arguments);

    static::updateMakeFile($version);

    // Find all .info files in pco_cities...except in the installed code base.
    $finder = (new Finder())
      ->name('*.info.yml')
      ->in('.')
      ->exclude('html');

    /** @var \Symfony\Component\Finder\SplFileInfo $info_file */
    foreach ($finder as $info_file) {
      $info = Yaml::parse($info_file->getContents());

      // Wrapping the version number in << and >> will cause the dumper to quote
      // the string, which is necessary for compliance with the strict PECL
      // parser.
      $info['version'] = '<<' . $version . '>>';
      $info = Yaml::dump($info, 2, 2);
      // Now that the version number will be quoted, strip out the << and >>
      // escape sequence.
      $info = str_replace(['<<', '>>'], NULL, $info);

      file_put_contents($info_file->getPathname(), $info);
    }
  }

  /**
   * Updates the legacy Drush make file(s).
   *
   * @param string $version
   *   The target version.
   */
  protected static function updateMakeFile($version) {
    $encoder = new IniEncoder();

    $finder = (new Finder())
      ->name('build-pco_cities.make')
      ->in('.');

    /** @var \Symfony\Component\Finder\SplFileInfo $make_file */
    foreach ($finder as $make_file) {
      $make = $encoder->parse($make_file->getContents());
      $make['projects']['pco_cities']['download']['tag'] = $version;
      file_put_contents($make_file->getPathname(), $encoder->encode($make));
    }
  }

}
