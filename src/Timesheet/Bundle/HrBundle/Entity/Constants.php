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
	
	const userActions=array(
		'edituser',
		'editlocation',
		'editcontract',
		'edittiming',
		'editgroup',
		'editqualification',
		'edituserqualification',
		'editvisa',
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
		'editvisa',
		'editstatus',
		'editshift',
		'editsreq',
		'editqreq'
	);

}