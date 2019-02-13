<?php

namespace Drupal\challenge_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChallengePageController extends ControllerBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  private $aliasManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $langManager;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $query;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a ChallengePageController object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $aliasManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Language\LanguageManagerInterface $langManager
   * @param \Drupal\Core\Entity\Query\QueryFactory $query
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  public function __construct(AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $langManager, QueryFactory $query, AccountInterface $currentUser, RequestStack $requestStack) {
    $this->aliasManager = $aliasManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->langManager = $langManager;
    $this->query = $query;
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.query'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  public function challengePage($challenge) {
    $language = $this->langManager->getCurrentLanguage()->getId();
    $defaultLang = $this->langManager->getDefaultLanguage()->getId();
    $nids = $this->query->get('node')->condition('type', 'challenge')->execute();
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    $node = NULL;

    foreach ($nodes as $item) {
      if ($item->get('field_friendly_url')->getValue()) {
        $url = $item->get('field_friendly_url')->getValue()[0]['value'];

        // Check for french translation.
        if (array_key_exists($language, $item->getTranslationLanguages())) {
          if ($item->getTranslation($language)->get('field_friendly_url')->getValue()) {
            $url_french = $item->getTranslation($language)->get('field_friendly_url')->getValue()[0]['value'];
          }
        }

        if ($url == $challenge) {
          $node = $item;
          break;
        }

        if ($url_french == $challenge) {
          $node = $item;
          break;
        }
      }
    }

    // If no matching node, then we throw an exception.
    if (!$node) {
      throw new NotFoundHttpException();
    }

    if (!array_key_exists($language, $node->getTranslationLanguages())) {
      $node = $node->getTranslation($defaultLang);
    }
    else {
      $node = $node->getTranslation($language);
    }

    // User variables.
    $user = $this->currentUser;
    $page['#logged_in'] = $user->isAuthenticated();


    // Create the Menu.
    $page['#challenge_menu_array'] = $this->generateMenuBar($node);

    // Page System Variables.
    $page['#theme'] = 'challenge_pages_page_theme';
    $page['#attached']['library'][] = 'challenge_pages/challenge-page';

    // Page Variables.
    $page['#challenge_name'] = $node->title->value;
    $page['#challenge_department'] = $node->get('field_challenge_department')->getValue()[0]['value'];
    if($node->get('field_challenge_image')->getValue()) {
      $page['#challenge_image'] = file_create_url($node->field_challenge_image->entity->uri->value);
    }
    $page['#challenge_home'] = '/challenges/' . $challenge;
    $page['#challenge_root'] = '/challenges/' . $challenge;
    $page['#challenge_node'] = '/node/' . $node->id();

    // Page Content.
    $page['#challenge_details_title'] = $node->get('field_challenge_details_title')->getValue() ? $node->get('field_challenge_details_title')->getValue()[0]['value'] : "";
    if (empty($page['#challenge_details_title'])) {
        $page['#challenge_details_title'] = t('Challenge details');
    }
    $page['#challenge_description'] = $node->get('field_challenge_description')->getValue() ? $node->get('field_challenge_description')->getValue()[0] : "";
    $page['#challenge_details'] = $node->get('field_challenge_details_block')->getValue();
    $fldval = $node->get('field_challenge_details_footer')->getValue();
    $page['#challenge_details_footer'] = isset($fldval[0]['value']) ? $fldval[0]['value'] : '';

    return $page;
  }

  public function challengeSubpage($challenge, $url) {
    $language = $this->langManager->getCurrentLanguage()->getId();
    $defaultLang = $this->langManager->getDefaultLanguage()->getId();
    $nids = $this->query->get('node')->condition('type', 'challenge')->execute();
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    $node = NULL;

    foreach ($nodes as $item) {
      if ($item->get('field_friendly_url')->getValue()) {
        $friendly_url = $item->get('field_friendly_url')->getValue()[0]['value'];

        // Check for french translation.
        if (array_key_exists($language, $item->getTranslationLanguages())) {
          if ($item->getTranslation($language)->get('field_friendly_url')->getValue()) {
            $url_french = $item->getTranslation($language)->get('field_friendly_url')->getValue()[0]['value'];
          }
        }

        if ($friendly_url == $challenge) {
          $node = $item;
          break;
        }

        if ($url_french == $challenge) {
          $node = $item;
          break;
        }
      }
    }

    // If no matching node, then we throw an exception.
    if (!$node) {
      throw new NotFoundHttpException();
    }

    if (!array_key_exists($language, $node->getTranslationLanguages())) {
      $node = $node->getTranslation($defaultLang);
    }
    else {
      $node = $node->getTranslation($language);
    }

    $challenge_path = $this->requestStack->getCurrentRequest()->getRequestUri();

    if (substr($challenge_path, -1) == '/') {
      $challenge_path = substr($challenge_path, 0, -1);
    }

    list($challenge_url, $subpage_url) = explode($node->get('field_friendly_url')->getValue()[0]['value'], $challenge_path);
    $url = str_replace("/", "", $subpage_url);
    $url = strpos($url, "?q=") ? substr($url, 0, strpos($url, "?q=")) : $url;
    unset($challenge_url);

    // Create the Menu.
    $page['#challenge_menu_array'] = $this->generateMenuBar($node);

    // Page System Variables.
    $page['#theme'] = 'challenge_subpage_module_page_theme';
    $page['#attached']['library'][] = 'challenge_pages/challenge-page';

    // Page Variables.
    $page['#challenge_name'] = $node->title->value;
    $page['#challenge_department'] = $node->get('field_challenge_department')->getValue()[0]['value'];
    if($node->get('field_challenge_image')->getValue()) {
      $page['#challenge_image'] = file_create_url($node->field_challenge_image->entity->uri->value);
    }
    $page['#challenge_home'] = '/challenges/' . $challenge;
    $page['#challenge_root'] = '/challenges/' . $challenge;
    $page['#challenge_node'] = '/node/' . $node->id();

    // User variables.
    $user = $this->currentUser;
    $page['#logged_in'] = $user->isAuthenticated();

    // Find out which page we're on.
    if (($this->challengePageUrl($node, 'field_challeng_subpage_url_1') == $url) && ($node->get('field_challenge_subpage_enable_1')->getValue()[0]['value'])) {
      $page['#challenge_subpage_title'] = $node->get('field_challenge_subpage_title_1')->getValue()[0]['value'] ?? "";
      $page['#challenge_subpage_body'] = $node->get('field_challenge_subpage_body_1')->getValue()[0] ?? "";
    }
    elseif (($this->challengePageUrl($node, 'field_challeng_subpage_url_2') == $url) && ($node->get('field_challenge_subpage_enable_2')->getValue()[0]['value'])) {
      $page['#challenge_subpage_title'] = $node->get('field_challenge_subpage_title_2')->getValue()[0]['value'] ?? "";
      $page['#challenge_subpage_body'] = $node->get('field_challenge_subpage_body_2')->getValue()[0] ?? "";
    }
    elseif (($this->challengePageUrl($node, 'field_challeng_subpage_url_3') == $url) && ($node->get('field_challenge_subpage_enable_3')->getValue()[0]['value'])) {
      $page['#challenge_subpage_title'] = $node->get('field_challenge_subpage_title_3')->getValue()[0]['value'] ?? "";
      $page['#challenge_subpage_body'] = $node->get('field_challenge_subpage_body_3')->getValue()[0] ?? "";
    }
    elseif (($this->challengePageUrl($node, 'field_challeng_subpage_url_4') == $url) && ($node->get('field_challenge_subpage_enable_4')->getValue()[0]['value'])) {
      $page['#challenge_subpage_title'] = $node->get('field_challenge_subpage_title_4')->getValue()[0]['value'] ?? "";
      $page['#challenge_subpage_body'] = $node->get('field_challenge_subpage_body_4')->getValue()[0] ?? "";
    }
    elseif (($this->challengePageUrl($node, 'field_challeng_subpage_url_5') == $url) && ($node->get('field_challenge_subpage_enable_5')->getValue()[0]['value'])) {
      $page['#challenge_subpage_title'] = $node->get('field_challenge_subpage_title_5')->getValue()[0]['value'] ?? "";
      $page['#challenge_subpage_body'] = $node->get('field_challenge_subpage_body_5')->getValue()[0] ?? "";
    }

    $page['#challenge_details_title'] = $node->get('field_challenge_details_title')->getValue() ? $node->get('field_challenge_details_title')->getValue()[0]['value'] : "";
    if (empty($page['#challenge_details_title'])) {
            $page['#challenge_details_title'] = t('Challenge details');
    }
    $page['#challenge_details'] = $node->get('field_challenge_details_block')->getValue();
    $fldval = $node->get('field_challenge_details_footer')->getValue();
    $page['#challenge_details_footer'] = isset($fldval[0]['value']) ? $fldval[0]['value'] : '';

    return $page;
  }

  private function challengePageUrl($node, $field) {
    if ($fld = $node->get($field)) {
      $val = $fld->getValue();
      if (isset($val[0]['value'])) {
        return $val[0]['value'];
      }
    }
    return null;
  }

  public function challengeNewsPage($challenge) {
    $language = $this->langManager->getCurrentLanguage()->getId();
    $defaultLang = $this->langManager->getDefaultLanguage()->getId();
    $nids = $this->query->get('node')->condition('type', 'challenge')->execute();
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    $node = NULL;

    foreach ($nodes as $item) {
      if ($item->get('field_friendly_url')->getValue()) {
        $friendly_url = $item->get('field_friendly_url')->getValue()[0]['value'];

        // Check for french translation.
        if (array_key_exists($language, $item->getTranslationLanguages())) {
          if ($item->getTranslation($language)->get('field_friendly_url')->getValue()) {
            $url_french = $item->getTranslation($language)->get('field_friendly_url')->getValue()[0]['value'];
          }
        }

        if ($friendly_url == $challenge) {
          $node = $item;
          break;
        }

        if ($url_french == $challenge) {
          $node = $item;
          break;
        }
      }
    }

    // If no matching node, then we throw an exception.
    if (!$node) {
      throw new NotFoundHttpException();
    }

    if (!array_key_exists($language, $node->getTranslationLanguages())) {
      $node = $node->getTranslation($defaultLang);
    }
    else {
      $node = $node->getTranslation($language);
    }

    $news_nids = $this->query->get('node')->condition('type', 'challenge_news')->sort('created', 'DESC')->pager(5)->execute();
    $news_nodes = $node_storage->loadMultiple($news_nids);
    $news_array = [];

    foreach ($news_nodes as $item) {
      if (!array_key_exists($language, $item->getTranslationLanguages())) {
        $item = $item->getTranslation($defaultLang);
      }
      else {
        $item = $item->getTranslation($language);
      }

      $target_id = $item->get('field_challenge')->getValue()[0]['target_id'];

      if ($target_id == $node->id()) {
        array_push($news_array, [
          'title' => $item->get('title')->getValue()[0]['value'],
          'header' => $item->get('field_type')->getValue()[0]['value'],
          'body' => $item->get('body')->getValue()[0],
          'sidebar' => $item->get('field_sidebar')->getValue()[0] ?? '',
        ]);
      }
    }

    // User variables.
    $user = $this->currentUser;
    $page['#logged_in'] = $user->isAuthenticated();

    // Create the Menu.
    $page['#challenge_menu_array'] = $this->generateMenuBar($node);

    // Articles.
    $page['#news'] = $news_array;

    // Page System Variables.
    $page['#theme'] = 'challenge_news_module_page_theme';
    $page['#attached']['library'][] = 'challenge_pages/challenge-page';

    // Page Variables.
    $page['#challenge_name'] = $node->title->value;
    $page['#challenge_department'] = $node->get('field_challenge_department')->getValue()[0]['value'];
    if($node->get('field_challenge_image')->getValue()) {
      $page['#challenge_image'] = file_create_url($node->field_challenge_image->entity->uri->value);
    }
    $page['#challenge_home'] = '/challenges/' . $challenge;
    $page['#challenge_root'] = '/challenges/' . $challenge;
    $page['#challenge_node'] = '/node/' . $node->id();

    $page['#pager'] = [
      '#type' => 'pager',
    ];

    return $page;
  }

  private function generateMenuBar($node) {
    $menu = [];

    $challenge_path = $this->requestStack->getCurrentRequest()->getRequestUri();
    $language = $this->langManager->getCurrentLanguage()->getId();

    if (substr($challenge_path, -1) == '/') {
      $challenge_path = substr($challenge_path, 0, -1);
    }

    list($challenge_url) = explode($node->get('field_friendly_url')->getValue()[0]['value'], $challenge_path);

    $challenge_url = $challenge_url . $node->get('field_friendly_url')->getValue()[0]['value'];

    $challenge_path = str_replace('/news', "", $challenge_path);

    // Add home menu item.
    if (isset($node->get('field_nav_title')->getValue()[0]['value'])) {
      $home_label = $node->get('field_nav_title')->getValue()[0]['value'];
    }
    if (empty($home_label)) {
      $home_label = $language == 'fr' ? 'Accueil' : 'Home';
    }
    array_push($menu, [
      'title' => $home_label,
      'url' => $challenge_url,
    ]);

    // Add subpage menu items.
    if ($node->get('field_challenge_subpage_enable_1')->getValue()[0]['value']) {
      if ($node->get('field_challenge_subpage_title_1')->getValue() && $node->get('field_challeng_subpage_url_1')->getValue()) {
        array_push($menu, [
          'title' => $node->get('field_challenge_subpage_title_1')->getValue()[0]['value'],
          'url' => $challenge_url . '/' . $node->get('field_challeng_subpage_url_1')->getValue()[0]['value'],
        ]);
      }
    }
    if ($node->get('field_challenge_subpage_enable_2')->getValue()[0]['value']) {
      if ($node->get('field_challenge_subpage_title_2')->getValue() && $node->get('field_challeng_subpage_url_2')->getValue()) {
        array_push($menu, [
          'title' => $node->get('field_challenge_subpage_title_2')->getValue()[0]['value'],
          'url' => $challenge_url . '/' . $node->get('field_challeng_subpage_url_2')->getValue()[0]['value'],
        ]);
      }
    }
    if ($node->get('field_challenge_subpage_enable_3')->getValue()[0]['value']) {
      if ($node->get('field_challenge_subpage_title_3')->getValue() && $node->get('field_challeng_subpage_url_3')->getValue()) {
        array_push($menu, [
          'title' => $node->get('field_challenge_subpage_title_3')->getValue()[0]['value'],
          'url' => $challenge_url . '/' . $node->get('field_challeng_subpage_url_3')->getValue()[0]['value'],
        ]);
      }
    }
    if ($node->get('field_challenge_subpage_enable_4')->getValue()[0]['value']) {
      if ($node->get('field_challenge_subpage_title_4')->getValue() && $node->get('field_challeng_subpage_url_4')->getValue()) {
        array_push($menu, [
          'title' => $node->get('field_challenge_subpage_title_4')->getValue()[0]['value'],
          'url' => $challenge_url . '/' . $node->get('field_challeng_subpage_url_4')->getValue()[0]['value'],
        ]);
      }
    }
    if ($node->get('field_challenge_subpage_enable_5')->getValue()[0]['value']) {
      if ($node->get('field_challenge_subpage_title_5')->getValue() && $node->get('field_challeng_subpage_url_5')->getValue()) {
        array_push($menu, [
          'title' => $node->get('field_challenge_subpage_title_5')->getValue()[0]['value'],
          'url' => $challenge_url . '/' . $node->get('field_challeng_subpage_url_5')->getValue()[0]['value'],
        ]);
      }
    }

    // Add news.. add check to include or not.
    if (0) { // News is disabled
        $node_storage = $this->entityTypeManager->getStorage('node');
        $news_nids = $this->query->get('node')->condition('type', 'challenge_news')->execute();
        $news_nodes = $node_storage->loadMultiple($news_nids);
        $news_exists = FALSE;

        foreach ($news_nodes as $item) {
          $target_id = $item->get('field_challenge')->getValue()[0]['target_id'];

          if ($target_id == $node->id()) {
            $news_exists = TRUE;
          }
        }
        if ($news_exists) {
          array_push($menu, [
            'title' => $language == 'fr' ? 'Nouvelles' : 'News',
            'url' => $language == 'fr' ? $challenge_url . '/nouvelles' : $challenge_url . '/news',
          ]);
        }
    }

    return $menu;
  }

}
