<?php

declare(strict_types = 1);

namespace Drupal\advanced_permissions_request\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManager $mailManager, AccountProxyInterface $current_user, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailmanager = $mailManager;
    $this->currentUser = $current_user;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get("entity_type.manager"),
      $container->get("plugin.manager.mail"),
      $container->get("current_user"),
      $container->get("config.factory"),
    );
  }

  /**
   * Accept Request function.
   *
   *   This function get node, set published, agree new role to user created
   *   request and finally send email to user to notice this request was accept.
   *
   * @param int $node
   *   The id of node.
   */
  public function acceptRequest($node) {
    $node_storage = $this->entityTypeManager->getStorage("node");
    $node = $node_storage->load($node);
    $node->set("status", "1");
    $this->currentUser->id();
    $node->set("uid", $this->currentUser->id());
    $node->save();

    $roleRequest = $node->get("field_role");
    $roleRequest = $roleRequest->getString();

    $user = $node->get("field_user");
    $userUid = $user->getString();

    $user_storage = $this->entityTypeManager->getStorage("user")
      ->loadByProperties([
        "uid" => $userUid,
      ]);

    $user_storage = reset($user_storage);
    $user_storage->addRole($roleRequest);
    $user_storage->save();

    if ($user_storage != NULL) {
      $subject = $this->configFactory->getEditable("advanced_permissions_request.settings")->get("subjectemailaccept");
      $body = $this->configFactory->getEditable("advanced_permissions_request.settings")->get("bodyemailaccept");
      $this->sendEmail($subject, $body, $user_storage->getEmail());
    }

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
   *   This funcion get user from node, compare with the user create denied
   *   process,if is the same user, not send email. If is differents user,
   *   send email. Finally, delete request node.
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

    $user = $node->get('field_user');
    $userUid = $user->getString();

    $user_storage = $this->entityTypeManager->getStorage('user')
      ->loadByProperties([
        'uid' => $userUid,
      ]);
    $user_storage = reset($user_storage);

    /*
    If user was has canceled request was been the same from request content
    type, is th userself who canceled, not send an email.
     */
    if ($user_storage != NULL && $this->currentUser->id() != $userUid) {
      $subject = $this->configFactory->getEditable("advanced_permissions_request.settings")->get("subjectemaildenny");
      $body = $this->configFactory->getEditable("advanced_permissions_request.settings")->get("bodyemaildenny");
      $this->sendEmail($subject, $body, $user_storage->getEmail());
    }

    $node->delete();

    $messageToShow = "Your denied request role to user: " . $user_storage->label();
    $build = [];
    $build["content"] = [
      "#type" => "item",
      "#markup" => $messageToShow,
    ];
    return $build;
  }

  /**
   * SendEmail function.
   *
   *   Send email after denny or accept request.
   *
   * @param string $subject
   *   The subject of email.
   * @param string $message
   *   The message of email.
   * @param string $userDestinationMail
   *   The email of user.
   *
   * @return void
   *   No return.
   */
  public function sendEmail(string $subject, string $message, string $userDestinationMail) {
    $module = "advanced_permissions_request";
    $key = "request_role";
    $params = [];
    $params["subject"] = $subject;
    $params["message"] = $message;
    $langcode = "en";
    $send = TRUE;
    $result = $this->mailmanager->mail($module, $key, $userDestinationMail, $langcode, $params, NULL, $send);
    if ($result["result"] !== TRUE) {
      $this->messenger()->addError("There was a problem sending your message and it was not sent.");
    }
  }

}
