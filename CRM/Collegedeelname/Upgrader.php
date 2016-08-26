<?php

class CRM_Collegedeelname_Upgrader extends CRM_Collegedeelname_Upgrader_Base {
  public function install() {
    // check if the tag 'Deelname college' exists
    $params = array(
      'name' => 'Deelname college',
      'sequential' => 1,
    );
    $tags = civicrm_api3('Tag', 'get', $params);
    
    if ($tags['count'] > 0) {        
      // do nothing
    }
    else {
      // the tag does not exist, create it
      $params['description'] = 'Om aan te duiden of een SP-afdeling deelneemt aan het college';
      $params['used_for'] = 'civicrm_contact';
      $params['is_selectable'] = 1;
      $params['is_reserved'] = 0;
      $params['is_tagset'] = 0;
      $tags = civicrm_api3('Tag', 'create', $params);
    }
  }
}
