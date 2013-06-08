<?php

//	ini_set("display_errors", 1); 


// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once('mebis.php');

/**
 *
 *
 * repository_sodis_cp class
 * This is a class used to browse resources from SODIS Content Pool
 * derived from moodle/repository/wikimedia
 *
 * @since 2.0
 * @package    repository
 * @subpackage SODIS Content Pool provided by FWU
 * @copyright  2012 FWU Institut fuer Film und Bild 
 * @author     Friedhelm Schumacher <friedhelm.schumacher@fwu.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class repository_mebis extends repository {


	public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
		global $USER;
		
		parent::__construct($repositoryid, $context, $options);
		
//		if(empty($USER->cp_token)){
			// provide valid cp_token, if successful
			$USER->cp_token='';
			$client = new mebis;
			$logged=$client->authentify();	
//		}
		
	}



	// login
    public function check_login() {
		// replaces 'check_login' in ../moodle/repository/lib.php
		global $USER;

		return true;
		
		
		if(empty($USER->cp_token)){
			return false;
		} else {
			return true;
		}
	}

	
    // if check_login returns false,
    // this function will be called to print a login form.
    public function print_login() {
		// replaces 'print_login' in ../moodle/repository/lib.php

		global $USER;

		$user_field = new stdClass();
		$user_field->label = get_string('username', 'repository_mebis').': ';
		$user_field->id    = 'sodis_cp_username';
		$user_field->type  = 'text';
		$user_field->name  = 'sodis_cp_username';
		$user_field->value = $USER->cp_token;
		
		$passwd_field = new stdClass();
		$passwd_field->label = get_string('password', 'repository_mebis').': ';
		$passwd_field->id    = 'sodis_cp_password';
		$passwd_field->type  = 'password';
		$passwd_field->name  = 'sodis_cp_password';
		$passwd_field->value = '';

		$ret = array();
		$ret['login'] = array($user_field, $passwd_field);
		return $ret;
	}


	
    // when logout button on file picker is clicked, this function will be
    // called.

    public function logout() {
		global $USER;

		$USER->cp_token="";

		$client = new mebis;
		$logged=$client->logout();	
		return $this->print_login();
    }

	
	

     public function print_search() {
		// replaces 'print_search' in ../moodle/repository/lib.php
		global $USER;

		if(!empty($USER->parameters['general_keyword'])){
			$keyword=$USER->parameters['general_keyword'];
		} else {
			$keyword="";
		}
		if(!empty($USER->parameters['classification_discipline'])){
			$discipline=$USER->parameters['classification_discipline'];
		} else {
			$discipline="";
		}
		if(!empty($USER->parameters['educational_context'])){
			$context=$USER->parameters['educational_context'];
		} else {
			$context="";
		}

        $str = '';
		$str .= '<input type="hidden" name="repo_id" value="'.$this->id.'" />';
		$str .= '<input type="hidden" name="ctx_id" value="'.$this->context->id.'" />';
		$str .= '<input type="hidden" name="seekey" value="'.sesskey().'" />';
		$str .= '<input type="hidden" name="user_origin" value="moodle" />';

		$str .= '<label>'.'Suchwort'.': </label><br/>';
		$str .= '<input name="general_keyword" value="' . $keyword . '" /><br/>';

		$str .= '<label>'.'Fach'.': </label><br/>';
		$str.='<select id="search_classification_discipline" size="" name="classification_discipline">';
		$str.=$this->addOption('','[keine Angabe]',$discipline);
		$str.=$this->addOption('Biologie','Biologie',$discipline);
		$str.=$this->addOption('Chemie','Chemie',$discipline);
		$str.=$this->addOption('Deutsch','Deutsch',$discipline);
		$str.=$this->addOption('Englisch','Englisch',$discipline);
		$str.=$this->addOption('Erdkunde','Erdkunde',$discipline);
		$str.=$this->addOption('Mathematik','Mathematik',$discipline);
		$str.=$this->addOption('Physik','Physik',$discipline);
		$str.=$this->addOption('Politik','Politik',$discipline);
		$str.=$this->addOption('Religion','Religion',$discipline);
		$str.='</select><br />';

        $str .= '<label>'.'Schulstufe'.': </label><br/>';
		$str.='<select id="search_educational_context" size="" name="educational_context">';
		$str.=$this->addOption('','[keine Angabe]',$context);
		$str.=$this->addOption('primary school','Grundschule',$context);
		$str.=$this->addOption('lower secondary school','Sekundarstufe I',$context);
		$str.=$this->addOption('upper secondary school','Sekundarstufe II',$context);
		$str.=$this->addOption('vocational education','Berufliche Bildung',$context);
		$str.='</select><br />';
        return $str;
    }

	
	public function addOption($value,$text,$compare){
		$ret ='<option';
		if($value==$compare){
			$ret.=' selected=""';
		}
		$ret.=' value="' . $value . '">' . $text . '</option>';
		return $ret;
	}

	
    public function search($search_text, $page = 0) {
		//global $USER;
		
			$client = new mebis;
			$result = array();
			$result['list'] = $client->provideList($_POST);
			$result['nologin'] = true;
			$result['norefresh'] = true;
			return $result;
    }



    // if this plugin support global search, if this function return
    // true, search function will be called when global searching working
    public function global_search() {
        return false;
    }
	

	
    public function get_listing($path = '', $page = '') {
		//global $USER;

		if(!isset($this->token)) $this->token="";
		
		$client = new mebis;
			$list = array();
			$this->keyword=$this->token;
			$list['list'] = $client->provideList('');
			$list['nologin'] = true;
			$list['norefresh'] = true;
			//$list['nosearch'] = true;
			return $list;
    }
	
	
	
    public function supported_returntypes() {
        //return (FILE_INTERNAL | FILE_EXTERNAL);
        return (FILE_EXTERNAL);
    }
}
