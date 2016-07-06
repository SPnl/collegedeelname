<?php

class CRM_Collegedeelname_Form_Search_CollegeDeelname extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    // set page title
    CRM_Utils_System::setTitle(ts('Raadsleden en wethouders uit afdelingen waar we in het college zitten'));

    // add department filter
    $departments = array('' => '- alle SP afdelingen -') + $this->_getSPDepartments();
    $form->addElement('select', 'afdeling', 'SP afdeling', $departments);
    
    // add function filter
    $jobTitles = $this->_getJobTitles();
    foreach($jobTitles as $job_title_id => $job_title) 
      $form->addElement('checkbox', "job_title[{$job_title_id}]", 'Functie', $job_title);
    }    
    
    //$form->assign('elements', array('afdeling'));
  }

  function &columns() {
    // link between column header and database field
    $columns = array(
      'Naam' => 'display_name',
      'Functie' => 'label_a_b',
      'Afdeling' => 'organization_name',
    );
    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  function select() {
    // select database fields
    return "
	  contact_a.id contact_id
	  , contact_a.display_name
	  , rt.`label_a_b`
	  , afd.`organization_name`
    ";
  }

  function from() {
    return "
	  FROM
	    civicrm_contact contact_a
	  INNER JOIN
	    civicrm_relationship r ON r.`contact_id_a` = contact_a.id
	  INNER JOIN
	    civicrm_contact afd ON r.`contact_id_b` = afd.`id`
	  INNER JOIN
	    `civicrm_relationship_type` rt ON rt.id = r.`relationship_type_id`
	";
  }

  function where($includeContactIDs = FALSE) {
    $params = array();
    $where = "
      rt.`label_a_b` IN ('Gemeenteraadslid', 'Wethouder', 'Fractievoorzitter afd', 'Voorzitter')
      AND r.`is_active` = 1
    ";
    
    // see if a department was chosen
    $department = CRM_Utils_Array::value('afdeling', $this->_formValues);
    if ($department) {
      $params[1] = array($department, 'Integer');
      $where .= " AND afd.id = %1";
    }

    return $this->whereClause($where, $params);
  }
  
  private function _getSPDepartments() {  
    $departments = array();
	
	// get a list of all SP departments (= contact sub type)  
    $sql = "
      SELECT 
        c.id
        , c.`organization_name`
      FROM
        civicrm_contact c
      WHERE
        contact_sub_type LIKE '%SP_Afdeling%'
      ORDER BY
        c.sort_name    
    ";
    
    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    while ($dao->fetch()) {
      $departments = $departments + array($dao->id => $dao->organization_name);
    }
  
    return $departments;	  
  }
  
  private function _getJobTitles() {
    $jobTitles = array();
    
    // get a list of some specific relationship types
    $sql = "
      SELECT
        r.id as job_title_id
        , r.label_a_b as job_title
      FROM
        civicrm_relationship_type r
      WHERE
        r.label_a_b IN ('Gemeenteraadslid', 'Wethouder', 'Fractievoorzitter afd', 'Voorzitter')    
    ";
    
    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    while ($dao->fetch()) {
      $jobTitles = $jobTitles + array($dao->job_title_id => $dao->job_title);
    }
  
    return $jobTitles;	      
  }
}
