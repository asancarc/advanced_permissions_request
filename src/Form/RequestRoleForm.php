<?php

declare(strict_types = 1);

namespace Drupal\advanced_permissions_request\Form;

use Drupal\advanced_permissions_request\Service;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    $roleToOffer = $this->service->getConfigRoles();

    if ($roleToOffer != NULL) {
      $account = $this->service->userLoadFromUid(intval($user));

      $this->userRequest = $account;
      $rolesUser = $this->service->getRolesFromUser($account);

      // WIP, try to show actual roles.
      // If user has only one role, is only authenticated, not show.
      /*
      $form['message'] = [
      '#type' => 'radios',
      '#title' => $this->t("Now, you have this roles"),
      '#options' => $rolesUser,
      '#disabled' => TRUE,
      ];
       */

      // I need some helps, text not appear.
      $rolesUser = $this->service->getRolesFromUser($account);
      $rolesAvailable = $this->service->getAllRolesFromSystem();

      /*
       *  Compare roles from system with roles from user to offer only
       *  differences.
       */
      $rolesToRequest = array_diff($rolesAvailable, $rolesUser);

      $advice = 'You can select one new role to request';
      $form['roles'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select one role'),
        '#options' => $rolesToRequest,
        '#description' => $advice,
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send'),
      ];

      return $form;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
    $requestRole = $form_state->getValue('roles');
    $this->service->createRequestRoleContentType($requestRole, $this->userRequest);
    $form_state->setRedirect('entity.user.canonical', ['user' => $this->currentUser->id()]);
  }

}
