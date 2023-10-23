<?php

namespace Drupal\advanced_permissions_request;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;

/**
 * Service to include support functions for manage module.
 */
class Service {

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
   * Constructs a Service object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity.type.manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(EntityTypeManagerInterface $manager, LoggerChannelFactoryInterface $logger) {
    $this->manager = $manager;
    $this->logger = $logger;
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
   * @return EntityInterface
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
      'administrator',
    ];

    foreach ($roles as $role) {
      if (!in_array($role->id(), $rolesToExclude)) {
        $rolesCleared[$role->id()] = $role->label();
      }
    }
    return $rolesCleared;
  }

}
