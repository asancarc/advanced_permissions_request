<?php

namespace Drupal\advanced_permissions_request\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\advanced_permissions_request\Service;

/**
 * Provides a Advanced permissions request form.
 */
class RequestRoleForm extends FormBase {

  /**
   * Service handler.
   *
   * @var \Drupal\advanced_permissions_request\Service
   */
  protected $service;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * UserId to request new role.
   *
   * @var int
   */
  protected $userRequest;

  /**
   * Class constructor.
   */
  public function __construct(Service $service, AccountProxyInterface $current_user) {    
    $this->service = $service;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(      
      $container->get('advanced_permissions_request.service'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_permissions_request_request_role';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {

    $account = $this->service->userLoadFromUid(intval($user));

    $this->userRequest = $account;

    $rolesUser = $this->service->getRolesFromUser($account);

    //if (count($rolesUser) > 0) {
      $form['message'] = [
        '#type' => 'radios',
        '#title' => $this->t("Now, you have this roles"),
        '#options' => $rolesUser,
        '#disabled' => TRUE,
        // Disable, hide text.
      ];
    //}

    $advice = 'You can select one new role to request';

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (mb_strlen($form_state->getValue('message')) < 10) {
      $form_state->setErrorByName('message', $this->t('Message should be at least 10 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

}
