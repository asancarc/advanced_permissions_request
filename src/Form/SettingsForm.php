<?php

declare(strict_types = 1);

namespace Drupal\advanced_permissions_request\Form;

use Drupal\advanced_permissions_request\PermissionHandlerService;
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
   * @var \Drupal\advanced_permissions_request\PermissionHandlerService
   */
  protected $service;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PermissionHandlerService $service) {
    parent::__construct($config_factory);
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get("config.factory"),
      $container->get("advanced_permissions_request.service")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "advanced_permissions_request_settings";
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ["advanced_permissions_request.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all roles from the system and remove basic roles.
    $entityType = $this->service->getEntityTypeMananger();
    $roles = $entityType->getStorage("user_role")->loadMultiple();
    unset($roles["anonymous"]);
    unset($roles["authenticated"]);

    // Create array cleared with roles to offer.
    $rolesToOffer = [];
    foreach ($roles as $role) {
      $rolesToOffer[$role->id()] = $role->label();
    }

    // Get default values from config.
    $config = $this->config("advanced_permissions_request.settings");
    $selectedValues = $config->get("roles_to_offer");

    $form["roles_to_offer"] = [
      "#type" => "checkboxes",
      "#title" => $this->t("Please select what roles offer to users"),
      "#options" => $rolesToOffer,
      "#default_value" => $selectedValues,
    ];

    // Accept new request role email information section.
    $form["accept"] = [
      "#type" => "details",
      "#title" => $this
        ->t("Accept role email information"),
      "#collapsible" => TRUE,
      "#collapsed" => TRUE,
    ];
    $form["accept"]["subjectemailaccept"] = [
      "#type" => "textfield",
      "#title" => $this
        ->t("Subject"),
      "#default_value" => $config->get("subjectemailaccept"),
      "#size" => 60,
      "#maxlength" => 128,
      "#required" => TRUE,
    ];
    $form["accept"]["bodyemailaccept"] = [
      "#type" => "textarea",
      "#title" => $this
        ->t("Body"),
      "#default_value" => $config->get("bodyemailaccept"),
      "#required" => TRUE,
    ];

    // Denny new request role email information section.
    $form['denny'] = [
      "#type" => "details",
      "#title" => $this
        ->t("Denny role email information"),
      "#collapsible" => TRUE,
      "#collapsed" => TRUE,
    ];
    $form["denny"]["subjectemaildenny"] = [
      "#type" => "textfield",
      "#title" => $this
        ->t("Subject"),
      "#default_value" => $config->get("subjectemaildenny"),
      "#size" => 60,
      "#maxlength" => 128,
      "#required" => TRUE,
    ];
    $form["denny"]["bodyemaildenny"] = [
      "#type" => "textarea",
      "#title" => $this
        ->t("Body"),
      "#default_value" => $config->get("bodyemaildenny"),
      "#required" => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Check if any option is selected.
    $rolesToOffer = array_filter($form_state->getValue("roles_to_offer"));

    if (count($rolesToOffer) != 0) {
      // If all values are 0, no values checked.
      $this->config("advanced_permissions_request.settings")
        ->set("roles_to_offer", $rolesToOffer)
        ->save();
    }

    // Email information fields.
    $this->config("advanced_permissions_request.settings")
      ->set("subjectemailaccept", $form_state->getValue("subjectemailaccept"))
      ->save();
    $this->config("advanced_permissions_request.settings")
      ->set("bodyemailaccept", $form_state->getValue("bodyemailaccept"))
      ->save();
    $this->config("advanced_permissions_request.settings")
      ->set("subjectemaildenny", $form_state->getValue("subjectemaildenny"))
      ->save();
    $this->config("advanced_permissions_request.settings")
      ->set("bodyemaildenny", $form_state->getValue("bodyemaildenny"))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
