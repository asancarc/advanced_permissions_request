<?php

namespace Drupal\advanced_permissions_request\Form;

use Drupal\advanced_permissions_request\Service;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Advanced permissions request settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Service handler.
   *
   * @var Drupal\advanced_permissions_request\Service
   */
  protected $service;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Service $service) {
    parent::__construct($config_factory);
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('advanced_permissions_request.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_permissions_request_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advanced_permissions_request.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entityType = $this->service->getEntityTypeMananger();
    $roles = $entityType->getStorage('user_role')->loadMultiple();

    $roles_array = [];

    foreach ($roles as $role) {
      $roles_array[$role->id()] = $role->label();
    }

    $config = $this->config('advanced_permissions_request.settings');
    $selectedValues = $config->get('roles_to_offer');

    $form['roles_to_offer'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Please select what roles offer to users'),
      '#options' => $roles_array,
      '#default_value' => $selectedValues,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advanced_permissions_request.settings')
      ->set('roles_to_offer', $form_state->getValue('roles_to_offer'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
