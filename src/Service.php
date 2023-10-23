<?php

namespace Drupal\advanced_permissions_request;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

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

}
