<?php

class CRM_Collegedeelname_Form_Search_CollegeDeelname extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_formValues;
  private $_collegeTagID = -1;
  
  function __construct(&$formValues) {
    try {
      $params = array(
        'name' => 'Deelname college',
        'return' => 'id',
      );
      $this->_collegeTagID = civicrm_api3('Tag', 'getvalue', $params);  
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Session::setStatus("Het kenmerk 'Deelname college' bestaat niet. Maak het aan en duid de contacten aan die deelnemen aan het college.", ts('Error'), 'error');
    }
  
    parent::__construct($formValues);
  }

  function buildForm(&$form) {
    $elements = array();
    
    // set page title
    CRM_Utils_System::setTitle(ts('Raadsleden en wethouders uit afdelingen waar we in het college zitten'));
    
    // add department filter
    $departments = array('' => '- alle SP afdelingen -') + $this->_getSPDepartments();
    $form->addElement('select', 'afdeling', 'SP afdeling', $departments);
    $elements[] = 'afdeling';
    
    // add some checkboxes for specific relationships
    $relationships = $this->_getCollegeRelations();
    foreach ($relationships as $relationship_type_id => $description) {
      $form->addElement('checkbox', "relationship_type_id_{$relationship_type_id}", $description);
      $elements[] = "relationship_type_id_{$relationship_type_id}";
    }
    
    $form->assign('elements', $elements);    
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
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    //die($sql);
    return $sql;
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
	    civicrm_entity_tag et ON et.entity_id = afd.id and et.entity_table = 'civicrm_contact'	 
	  INNER JOIN
	    `civicrm_relationship_type` rt ON rt.id = r.`relationship_type_id`
	";
  }

  function where($includeContactIDs = FALSE) {       
	// let's check the possible relationships againts the selected relationships  
	$relationshipsIDs = array();
	$selectedIDs = array();
	foreach ($this->_getCollegeRelations() as $id => $v) {
	  // see if this one is selected
	  if (array_key_exists("relationship_type_id_{$id}", $this->_formValues)) {
	    $selectedIDs[] = $id;
	  }

      // store the id anyway, we might need it if nothing is selected
	  $relationshipsIDs[] = $id;		
	}
	
    $where = "r.is_active = 1 and rt.id in (";	
	if (count($selectedIDs) > 0) {
	  // take the selected id's
	  $where .= implode(', ', $selectedIDs);
	}
	else {
	  // no relationship selected, take all of them
	  $where .= implode(', ', $relationshipsIDs);
	}
	$where .= ") ";

    // see if a department was chosen
    $params = array();
    $department = CRM_Utils_Array::value('afdeling', $this->_formValues);
    if ($department) {
      $params[1] = array($department, 'Integer');
      $where .= " AND afd.id = %1";
    }
    
    // add the specific tag
    $where .= " AND et.tag_id = {$this->_collegeTagID}";

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
  
  /**
   * get the relationships that are relevant to 'deelname college':
   * 
   * Gemeenteraadslid
   * Wethouder
   * Fractievoorzitter afd
   * Voorzitter
   */
  private function _getCollegeRelations() {
    $relationships = array();
    
    // get a list of some specific relationship types
    $sql = "
      SELECT
        r.id as relationship_type_id
        , r.label_a_b as description
      FROM
        civicrm_relationship_type r
      WHERE
        r.label_a_b IN ('Gemeenteraadslid', 'Wethouder', 'Fractievoorzitter afd', 'Voorzitter')    
    ";
    
    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    while ($dao->fetch()) {     
      $relationships[$dao->relationship_type_id] = $dao->description;
    }
      
    return $relationships;	      
  }
}
