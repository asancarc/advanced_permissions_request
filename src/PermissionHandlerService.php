<?php

declare(strict_types = 1);

namespace Drupal\advanced_permissions_request;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to include support functions for manage module.
 */
class PermissionHandlerService implements ContainerInjectionInterface {

  /**
   * The entity.type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $manager;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The configFactory service.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $configFactory;

  /**
   * Constructs a Service object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity.type.manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The logger channel factory.
   */
  public function __construct(EntityTypeManagerInterface $manager, LoggerChannelFactoryInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->manager = $manager;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * Creates an instance of the class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   A new instance of the class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.default')
    );
  }

  /**
   * To return EntityTypeManager service to use in other places.
   */
  public function getEntityTypeMananger() {
    return $this->manager;
  }

  /**
   * UserLoadFromUid function.
   *
   * @param int $account
   *   The uid from user.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   User entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function userLoadFromUid(int $account) {
    $user_storage = $this->manager->getStorage('user')
      ->loadByProperties([
        'uid' => $account,
      ]);
    $user_storage = $user_storage[key($user_storage)];

    return $user_storage;

  }

  /**
   * Method getRolesFromUser.
   *
   *   To get all roles from some user and return with format.
   *
   * @var \Drupal\user\Entity\User $account
   *   User account object.
   *
   * @return array
   *   With correct format.
   */
  public function getRolesFromUser(User $account) {
    $user_storage = $this->manager->getStorage('user')
      ->loadByProperties([
        'uid' => $account->id(),
      ]);

    $account = $user_storage[key($user_storage)];

    $userRoles = $this->manager->getStorage('user_role')->loadMultiple($account->getRoles());

    $userRoles = $this->getRolesArrayCleared($userRoles);

    return $userRoles;
  }

  /**
   * GetAllRolesFromSystem function.
   *
   * @return array
   *   With the roles can request.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllRolesFromSystem() {
    $rolesAvailable = $this->manager->getStorage('user_role')->loadMultiple();
    $rolesAvailable = $this->getRolesArrayCleared($rolesAvailable);
    return $rolesAvailable;
  }

  /**
   * GetRolesArrayCleared function.
   *
   *   Create array with a defined structure with role_id and role label and
   *   exclude some roles as anonymous, authenticated and administrator.
   *
   * @param array $roles
   *   With roles to create array.
   *
   * @return array
   *   Array with correct format.
   */
  public function getRolesArrayCleared(array $roles): array {
    $rolesCleared = [];

    $rolesToExclude = [
      'anonymous',
      'authenticated',
    ];

    foreach ($roles as $role) {
      if (!in_array($role->id(), $rolesToExclude)) {
        $rolesCleared[$role->id()] = $role->label();
      }
    }
    return $rolesCleared;
  }

  /**
   * CreateRequestRoleContentType.
   *
   * @param string $requestRole
   *   String with role selected by user (machine name).
   * @param \Drupal\user\Entity\User $userRequestNewRole
   *   Object of current user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createRequestRoleContentType(string $requestRole, User $userRequestNewRole) {
    $newRequest = $this->manager->getStorage('node')->create([
      'type' => 'request_role',
      'title' => 'Request role for: ' . $userRequestNewRole->label(),
      'field_user' => $userRequestNewRole->id(),
      'field_role' => $requestRole,
      'status' => '0',
      'field_actual_roles' => $userRequestNewRole->getRoles(),
    ]);
    $newRequest->save();

  }

  /**
   * Advanced permissions request check user roles request function.
   *
   *   This function check if user has any RoleRequest awaiting.
   *
   * @return array|null
   *   If user has any Role await return TRUE if not return FALSE
   */
  public function checkUserRolesRequest($userId) {
    // Check if this user has any Role request openned.
    $nid = $this->manager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'request_role')
      ->condition('uid', $userId)
      ->condition('status', '0')
      ->execute();
    $nid = reset($nid);
    $node = $this->manager->getStorage('node')->load($nid);
    if (is_object($node)) {
      $role = $this->manager->getStorage('user_role')->load($node->get("field_role")->getValue()["0"]["target_id"]);
      $requestPending = [
        "nid" => $nid,
        "role" => $role->label(),
      ];
      return $requestPending;
    }

  }

  /**
   * GetConfigRoles function.
   *
   *   Return roles allowed to request.
   *
   * @return array
   *   With roles admin set allowed to user can request.
   */
  public function getConfigRoles() {
    $rolesAllowed = $this->configFactory->get('roles_to_offer');
    return $rolesAllowed;

  }

}
