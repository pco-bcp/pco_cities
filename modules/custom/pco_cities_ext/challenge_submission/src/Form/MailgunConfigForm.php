<?php

namespace Drupal\challenge_submission\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MailgunConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'mailgun_config';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('challenge_submission.settings');

    $form['mailgun_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailgun API Key'),
      '#default_value' => $config->get('challenge_submission.mailgun_key'),
    ];

    $form['mailgun_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailgun Domain'),
      '#default_value' => $config->get('challenge_submission.mailgun_domain'),
    ];

    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailgun Domain'),
      '#default_value' => $config->get('challenge_submission.from_email'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('challenge_submission.settings');

    $config->set('challenge_submission.mailgun_key', $form_state->getValue('mailgun_key'));
    $config->set('challenge_submission.mailgun_domain', $form_state->getValue('mailgun_domain'));
    $config->set('challenge_submission.from_email', $form_state->getValue('from_email'));

    $config->save();

    drupal_set_message($this->t("Settings saved"));

    return parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {

    return [

      'challenge_submission.settings',

    ];

  }

}
