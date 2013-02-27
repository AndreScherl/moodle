<?php

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

/**
 * mebis class
 * class for communication with SODIS Content Pool API
 *
 * @author Friedhelm Schumacher <friedhelm.schumacher@fwu.de>
 * based on:
 * @author Dongsheng Cai <dongsheng@moodle.com>, Raul Kern <raunator@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

 
 
class mebis {
    private $_conn  = null;
    private $_param = array();

	
    public function __construct($url = '') {
		global $USER;
		
        if (empty($url)) {
			//$this->api = 'http://sodis.de/cp/api/api.php';
			//$this->api = 'http://www.mebis.bayern.de/mediathek/cpCommon/api/api.php';
			$this->api = 'http://mediathek.mebis.int-dmz.bayern.de/cpCommon/api/api.php';
			//$this->api = 'http://10.172.40.30/cpCommon/api/api.php';
        } else {
            $this->api = $url;
        }
        $this->_conn = new curl(array('cache'=>true, 'debug'=>false));
		
		$USER->cp_country="BY";
		$USER->cp_school_no="12345";
		$USER->cp_role="teacher";
	}


	public function authentify(){
		global $USER;

		$this->_param['action']   = 'login';
		$this->_param['moodle_username']   = $USER->username;
		$this->_param['moodle_password']   = $USER->password;
		$this->_param['moodle_sessionId'] = $USER->sesskey;

		$content = $this->_conn->post($this->api, $this->_param);
		$result = unserialize($content);

		if($result==$USER->sesskey){
			$USER->cp_token=$result;
			return true;
		} else {
			//return true;
			return false;
		}
	}
	


    //public function login($user, $pass, $token) {
    public function login() {
		global $USER;

		$this->_param['action']   = 'login';
		$this->_param['moodle_username']   = $USER->username;
		$this->_param['moodle_password']   = $USER->password;
		$this->_param['moodle_sessionId'] = $USER->sesskey;

		$content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
		if($result==$token){
			return true;
		} else {
			return false;
		}
    }


    public function logout() {
		global $USER;

		$this->_param['action']   = 'logout';
 		$USER->cp_token='';
		$content = $this->_conn->post($this->api, $this->_param);
		return;
    }


	
	/**
     * Search for resources, return array.
     *
     * @param array $parameters
     * @return array
     */
    public function provideList($parameters) {
		// parameters are provided by lib.php -> print_search ->search

		global $USER;
		if(!isset($parameters['general_keyword'])) $parameters['general_keyword']="";
		if(!isset($parameters['educational_context'])) $parameters['educational_context']="";
		if(!isset($parameters['classification_discipline'])) $parameters['classification_discipline']="";
		
		$USER->parameters['general_keyword']=$parameters['general_keyword'];
		$USER->parameters['educational_context']=$parameters['educational_context'];
		$USER->parameters['classification_discipline']=$parameters['classification_discipline'];

//		if(empty($USER->cp_token)){
//			echo "EMPTY token";
//			$this->authentify();
//		} else {
			//echo "EMPTY token";
			$this->_param['action'] = 'search';
			$this->_param['search_parameter'] = $parameters;
			$this->_param['school_id'] = $USER->cp_school_no;
			$this->_param['userCountry'] = $USER->cp_country;
			$this->_param['userRole'] = $USER->cp_role;
			$this->_param['moodle_token'] = $USER->cp_token;
			$content = $this->_conn->get($this->api, $this->_param);
			$result = unserialize($content);
			$result['list']['nologin'] = true;
			$list['norefresh'] = true;
			//$list['nosearch'] = true;
			return $result['list'];
//		}
    }	
	
}
