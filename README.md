## CONTENTS OF THIS FILE

- Introduction

- Requirements

- Installation

- How it works

- Uninstall

## INTRODUCTION

The advanced permission request module provides users with the 
ability to request a higher system role from site admins.

## REQUIREMENTS

This module requires module drupal:toolbar.

## INSTALLATION

The installation of this module is like other Drupal modules.

1. If your site is [managed via Composer](https://www.drupal.org/node/2718229),
   use Composer to download the webform module running
   ```composer require "drupal/advanced_permissions_request"```. Otherwise copy/upload the advanced_permissions_request
   module to the modules directory of your Drupal installation.

2. Enable the 'Advanced permission request' module in 'Extend'.
   (`/admin/modules`)

3. Enable the system roles that you plan to offer to the rest of the users. If none are selected, 
   users will not have the option to request a higher role from administrators. 
   (`/admin/config/system/advanced-permissions-request`)
   There are also fields where you can put both the subject and the body of the emails to be sent,
   if the new rolo request is accepted or denied.

## HOW IT WORKS

1. Once these roles are selected, users will be able to request a higher role from their user profile.
  - "If users have a pending new role request for review by admins, instead of displaying the 'New Role Request' button,
    a notification will appear indicating that there is an active new role request process pending. 
    The notification will provide information and offer the option to cancel the request."

2. Once users have submitted a request for a new role, 
   system administrators will be able to view the list of these requests.
  (`/role-requests`)
  - This process will create content of type 'role request,' which will only be accessible to site administrators.
  - Such content will remain in an unpublished state.  

3. At this point, administrators will have the discretion to decide whether or not to grant the role requested by the user.
  - If administrators grant the new role to the user, the created content will transition to a published state. 
  - To keep a record of which user has granted this new role, they will be recorded as the author of the last update to the relevant node.
  - If site admins have declined/accept the user's request, they will receive an email notification informing them of this.

## UNINSTALL

When uninstalling the module, all content created in relation to this module will be deleted.
