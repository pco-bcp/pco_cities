<?php

namespace Drupal\pco_cities;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Helper object to locate pco_cities components and sub-components.
 */
class ComponentDiscovery {

  /**
   * The extension discovery iterator.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected $discovery;

  /**
   * The pco_cities profile extension object.
   *
   * @var Extension
   */
  protected $profile;

  /**
   * Cache of all discovered components.
   *
   * @var Extension[]
   */
  protected $components;

  /**
   * ComponentDiscovery constructor.
   *
   * @param string $app_root
   *   The application root directory.
   */
  public function __construct($app_root) {
    $this->discovery = new ExtensionDiscovery($app_root);
  }

  /**
   * Returns an extension object for the pco_cities profile.
   *
   * @return \Drupal\Core\Extension\Extension
   *   The pco_cities profile extension object.
   *
   * @throws \RuntimeException
   *   If the pco_cities profile is not found in the system.
   */
  protected function getProfile() {
    if (empty($this->profile)) {
      $profiles = $this->discovery->scan('profile');

      if (empty($profiles['pco_cities'])) {
        throw new \RuntimeException('pco_cities profile not found.');
      }
      $this->profile = $profiles['pco_cities'];
    }
    return $this->profile;
  }

  /**
   * Returns the base path for all pco_cities components.
   *
   * @return string
   *   The base path for all pco_cities components.
   */
  protected function getBaseComponentPath() {
    return $this->getProfile()->getPath() . '/modules/custom';
  }

  /**
   * Returns extension objects for all pco_cities components.
   *
   * @return Extension[]
   *   Array of extension objects for all pco_cities components.
   */
  public function getAll() {
    if (is_null($this->components)) {
      $base_path = $this->getBaseComponentPath();

      $filter = function (Extension $module) use ($base_path) {
        return strpos($module->getPath(), $base_path) === 0;
      };

      $this->components = array_filter($this->discovery->scan('module'), $filter);
    }
    return $this->components;
  }

  /**
   * Returns extension objects for all main pco_cities components.
   *
   * @return Extension[]
   *   Array of extension objects for top-level pco_cities components.
   */
  public function getMainComponents() {
    $base_path = $this->getBaseComponentPath();

    $filter = function (Extension $module) use ($base_path) {
      return dirname($module->getPath()) == $base_path;
    };

    return array_filter($this->getAll(), $filter);
  }

  /**
   * Returns extension object for all pco_cities sub-components.
   *
   * @return Extension[]
   *   Array of extension objects for pco_cities sub-components.
   */
  public function getSubComponents() {
    $base_path = $this->getBaseComponentPath();

    $filter = function (Extension $module) use ($base_path) {
      return strlen(dirname($module->getPath())) > strlen($base_path);
    };

    return array_filter($this->getAll(), $filter);
  }

}
