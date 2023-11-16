<?php

declare(strict_types = 1);

namespace Drupal\advanced_permissions_request\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for User management routes.
 */
final class RequestController extends ControllerBase {

  use MessengerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail manager plugin.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailmanager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManager $mailManager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailmanager = $mailManager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('current_user')
    );
  }

  /**
   * Accept Request function.
   *
   * @param int $node
   *   The id of node.
   */
  public function acceptRequest($node) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($node);
    $node->set('status', '1');
    $this->currentUser->id();
    $node->set('uid', $this->currentUser->id());
    $node->save();

    $roleRequest = $node->get('field_request_role');
    $roleRequest = $roleRequest->getString();

    $user = $node->get('field_request_user');
    $userUid = $user->getString();

    $user_storage = $this->entityTypeManager->getStorage('user')
      ->loadByProperties([
        'uid' => $userUid,
      ]);

    $user_storage = reset($user_storage);
    $user_storage->addRole($roleRequest);
    $user_storage->save();

    $messageToShow = 'There user: ' . $user_storage->label() . " has new roles.";
    $build = [];
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $messageToShow,
    ];
    return $build;

  }

  /**
   * Denny Request function.
   *
   * @param int $node
   *   The id of node.
   *
   * @return array
   *   Description.
   */
  public function dennyRequest($node) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($node);

    $user = $node->get('field_request_user');
    $userUid = $user->getString();

    $user_storage = $this->entityTypeManager->getStorage('user')
      ->loadByProperties([
        'uid' => $userUid,
      ]);
    $user_storage = reset($user_storage);

    $module = 'user_management';
    $key = 'request_role';
    $userDestination = $user_storage->getEmail();
    if ($user_storage->getEmail() != NULL) {
      $params = [];
      $params['message'] = "Dear user, your request to update roles to was denied ";
      $params['subject'] = 'Dennied your request role';
      $langcode = 'en';
      $send = TRUE;
      $result = $this->mailmanager->mail($module, $key, $userDestination, $langcode, $params, NULL, $send);
      if ($result['result'] !== TRUE) {
        $this->messenger()->addError('There was a problem sending your message and it was not sent.');
      }
    }

    $node->delete();

    $messageToShow = 'Your denied request role to user: ' . $user_storage->label();
    $build = [];
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $messageToShow,
    ];
    return $build;
  }

}