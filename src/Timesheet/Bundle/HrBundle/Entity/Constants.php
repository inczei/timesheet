<?php

namespace Timesheet\Bundle\HrBundle\Entity;

class Constants {
	const MENU_HOMEPAGE = 1;
	const MENU_LOGIN = 2;
	const MENU_REGISTER = 3;
	const MENU_DASHBOARD = 4;
	const MENU_TIMESHEET = 5;
	const MENU_SCHEDULE = 6;
	const MENU_HOLIDAY = 7;
	const MENU_ADMIN = 8;
	const MENU_CONFIG = 9;
	const MENU_MESSAGES = 10;
	const MENU_SYSADMIN = 11;
	const MENU_RESIDENTS = 12;
	
	const MENU_ITEMS = 12;
	
	const LEVEL_USER = 0;
	const LEVEL_ADMIN = 14;
	
	const userActions=array(
		'edituser',
		'editlocation',
		'editcontract',
		'edittiming',
		'editgroup',
		'editqualification',
		'edituserqualification',
		'editvisa',
		'editdbs',
		'editstatus',
		'editshift',
		'editsreq',
		'editqreq'
	);
	
	const adminActions=array(
		'edituser',
		'editlocation',
		'editroom',
		'editcontract',
		'edittiming',
		'editgroup',
		'editqualification',
		'edituserqualification',
		'editjobtitle',
		'editvisa',
		'editstatus',
		'editshift',
		'editsreq',
		'editqreq',
		'editmodule',
		'newfpreader',
		'editfpreader'
	);

	const fpReaderVerfyMethods=array(
		'0'=>'Fingerprint',
		'1'=>'Password',
		'2'=>'Card',
		'3'=>'PIN'
	);
	
	const maritalStatuses=array(
		'N'=>'Not disclosed',
		'S'=>'Single',
		'M'=>'Married/Civil Partner',
		'D'=>'Divorced/Person whose Civil Partnership has been dissolved',
		'W'=>'Widowed/Surviving Civil Partner',
		'P'=>'Separated'		
	);
	
}