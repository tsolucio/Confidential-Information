<?php
/*************************************************************************************************
 * Copyright 2015 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
 * Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
 * file except in compliance with the License. You can redistribute it and/or modify it
 * under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
 * granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
 * the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
 * applicable law or agreed to in writing, software distributed under the License is
 * distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing
 * permissions and limitations under the License. You may obtain a copy of the License
 * at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
 *************************************************************************************************
 *  Module       : ConfidentialInfo
 *  Version      : 5.5.0
 *  Author       : JPL TSolucio, S. L.
 *************************************************************************************************/
require_once 'Smarty_setup.php';
require_once 'include/utils/utils.php';
global $currentModule, $app_strings, $mod_strings;
$smty = new vtigerCRM_Smarty();
$smty->assign('OPERATION_MESSAGE', getTranslatedString('DisabledProcess', $currentModule));
$smty->assign('PUT_BACK_ACTION', 'false');
$smty->assign('APP', $app_strings);
$smty->assign('MOD', $mod_strings);
$smty->display('modules/Vtiger/OperationNotPermitted.tpl');
if (empty($_REQUEST['mode']) && $_REQUEST['action']!='ConfidentialInfoAjax') {
	checkFileAccessForInclusion("modules/$currentModule/ListView.php");
	include_once 'modules/$currentModule/ListView.php';
}
?>
