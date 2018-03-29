<?php

namespace Drupal\challenge_submission\Controller;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubmissionFormModuleController extends ControllerBase {
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
   * Constructs a SubmissionFormModuleController object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $aliasManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Language\LanguageManagerInterface $langManager
   * @param \Drupal\Core\Entity\Query\QueryFactory $query
   */
  public function __construct(AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $langManager, QueryFactory $query) {
    $this->aliasManager = $aliasManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->langManager = $langManager;
    $this->query = $query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.query')
    );
  }

  public function submissionSuccessPage($challenge, Request $request) {

    $language = $this->langManager->getCurrentLanguage()->getId();
    $nids = $this->query->get('node')->condition('type', 'challenge')->execute();
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nodes = $node_storage->loadMultiple($nids);
    $node = NULL;

    foreach ($nodes as $item) {
      if ($item->get('field_friendly_url')->getValue()) {
        $url = $item->get('field_friendly_url')->getValue()[0]['value'];

        // Check for french translation.
        if ($item->getTranslation($language)->get('field_friendly_url')->getValue()) {
          $url_french = $item->getTranslation($language)->get('field_friendly_url')->getValue()[0]['value'];
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

    if(!$node->get('field_challenge_submission_email')->getValue()) {
      throw new NotFoundHttpException();
    }

    $page['#theme'] = 'challenge_submission_page_theme';
    $page['#attached']['library'][] = 'challenge_submission/submission-form';

    $page['#challenge_name'] = $node->title->value;
    $page['#challenge_department'] = $node->get('field_challenge_department')->getValue()[0]['value'];
    $page['#challenge_image'] = file_create_url($node->field_challenge_image->entity->uri->value);
    $page['#challenge_url'] = '/node/' . $node->id();
    $page['#submission_success'] = TRUE;
    $page['#submission_email'] = $request->get('email');

    return $page;
  }

  public function submissionFormPage($challenge, Request $request) {
    $submission_error = $request->get('error');

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

    if(!$node->get('field_challenge_submission_email')->getValue()) {
      throw new NotFoundHttpException();
    }

    if (!array_key_exists($language, $node->getTranslationLanguages())) {
      $node = $node->getTranslation($defaultLang);
    }
    else {
      $node = $node->getTranslation($language);
    }

    $form = $this->formBuilder()->getForm('Drupal\challenge_submission\Form\SubmissionForm');

    // Add hidden field to form.
    $form['friendly_url']['#value'] = $node->get('field_friendly_url')->getValue()[0]['value'];

    // Wrap the theme with WET4 validation tag.
    $form['#prefix'] = '<div class="wb-frmvld">';
    $form['#suffix'] = '</div>';

    $form['#theme'] = 'challenge_submission_page_theme';
    $form['#attached']['library'][] = 'challenge_submission/submission-form';
    $form['#challenge_url'] = '/node/' . $node->id();
    $form['#challenge_name'] = $node->title->value;
    $form['#challenge_department'] = $node->get('field_challenge_department')->getValue()[0]['value'];
    $form['#challenge_image'] = file_create_url($node->field_challenge_image->entity->uri->value);

    $form['#submission_error'] = $submission_error;

    return $form;

  }

  public function submissionSuccessPageFrench($challenge, Request $request) {

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

    if(!$node->get('field_challenge_submission_email')->getValue()) {
      throw new NotFoundHttpException();
    }

    if (!array_key_exists($language, $node->getTranslationLanguages())) {
      $node = $node->getTranslation($defaultLang);
    }
    else {
      $node = $node->getTranslation($language);
    }

    $page['#theme'] = 'challenge_submission_french_page_theme';
    $page['#attached']['library'][] = 'challenge_submission/submission-form';

    $page['#challenge_name'] = $node->title->value;
    $page['#challenge_department'] = $node->get('field_challenge_department')->getValue()[0]['value'];
    $page['#challenge_image'] = file_create_url($node->field_challenge_image->entity->uri->value);
    $page['#challenge_url'] = '/node/' . $node->id();
    $page['#submission_success'] = TRUE;
    $page['#submission_email'] = $request->get('email');

    return $page;
  }

  public function submissionFormPageFrench($challenge, Request $request) {
    $submission_error = $request->get('error');
    die;
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

    if(!$node->get('field_challenge_submission_email')->getValue()) {
      throw new NotFoundHttpException();
    }

    if (!array_key_exists($language, $node->getTranslationLanguages())) {
      $node = $node->getTranslation($defaultLang);
    }
    else {
      $node = $node->getTranslation($language);
    }

    $form = $this->formBuilder()->getForm('Drupal\challenge_submission\Form\SubmissionForm');

    // Add hidden field to form.
    $form['friendly_url']['#value'] = $node->get('field_friendly_url')->getValue()[0]['value'];

    // Wrap the theme with WET4 validation tag.
    $form['#prefix'] = '<div class="wb-frmvld">';
    $form['#suffix'] = '</div>';

    $form['#theme'] = 'challenge_submission_french_page_theme';
    $form['#attached']['library'][] = 'challenge_submission/submission-form';

    $form['#challenge_name'] = $node->title->value;
    $form['#challenge_department'] = $node->get('field_challenge_department')->getValue()[0]['value'];
    $form['#challenge_image'] = file_create_url($node->field_challenge_image->entity->uri->value);
    $form['#challenge_url'] = '/node/' . $node->id();
    $form['#submission_error'] = $submission_error;

    return $form;

  }

}
