<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
global $app_strings, $mod_strings, $current_language, $currentModule, $theme, $adb;
require_once 'Smarty_setup.php';

$focus = CRMEntity::getInstance($currentModule);
$smarty = new vtigerCRM_Smarty();

$smarty->assign('CUSTOM_MODULE', $focus->IsCustomModule);

$category = getParentTab($currentModule);
$record = isset($_REQUEST['record']) ? vtlib_purify($_REQUEST['record']) : null;
$isduplicate = isset($_REQUEST['isDuplicate']) ? vtlib_purify($_REQUEST['isDuplicate']) : null;

$searchurl = getBasic_Advance_SearchURL();
$smarty->assign('SEARCH', $searchurl);

if ($record) {
	$focus->id = $record;
	$focus->mode = 'edit';
	$focus->retrieve_entity_info($record, $currentModule);
}
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', 'SINGLE_'.$currentModule);
$smarty->assign('CATEGORY', $category);
$smarty->assign('THEME', $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('ID', $focus->id);
$smarty->assign('MODE', $focus->mode);
$smarty->assign('CREATEMODE', isset($_REQUEST['createmode']) ? vtlib_purify($_REQUEST['createmode']) : '');

$smarty->assign('CHECK', Button_Check($currentModule));
$smarty->assign('DUPLICATE', $isduplicate);

$cidwspinfo = (isset($_REQUEST['cidwspinfo']) ? vtlib_purify($_REQUEST['cidwspinfo']) : '');
if (empty($cidwspinfo)) {
	$smarty->assign('RETURN_MODULE', isset($_REQUEST['return_module']) ? vtlib_purify($_REQUEST['return_module']) : '');
	$smarty->assign('RETURN_ACTION', isset($_REQUEST['return_action']) ? vtlib_purify($_REQUEST['return_action']) : '');
	$smarty->assign('RETURN_ID', isset($_REQUEST['return_id']) ? vtlib_purify($_REQUEST['return_id']) : '');
	$smarty->assign('RETURN_VIEWNAME', isset($_REQUEST['return_viewname']) ? vtlib_purify($_REQUEST['return_viewname']) : '');
	if (isset($isduplicate)) {
		$smarty->assign('isDuplicate', $isduplicate);
	}
	$smarty->assign('ID', $record);
	if (isset($_REQUEST['ciasset']) && !isset($_REQUEST['cirelto'])) {
		$assetrs = $adb->pquery('select account from vtiger_assets where assetsid = ?', array($_REQUEST['ciasset']));
		if (!empty($assetrs)) {
			$asset = $adb->fetch_array($assetrs);
			if (!empty($asset['account'])) {
				$_REQUEST['cirelto'] = $asset['account'];
				$_REQUEST['cirelto_type'] = 'Accounts';
			}
		}
	}
	$smarty->assign('BadPassword', 'false');
	$smarty->assign('MODE', '');
	$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'GetPassword.tpl'));
} else {
	$rsps = $adb->query('select * from vtiger_cicryptinfo limit 1');
	if (empty($rsps) || $adb->num_rows($rsps)==0) {
		$smarty->assign('OPERATION_MESSAGE', getTranslatedString('ErrorPassword', $currentModule));
		$smarty->display('modules/Vtiger/OperationNotPermitted.tpl');
	} else {
		$row = $adb->fetch_array($rsps);
		if (sha1($cidwspinfo)!=$row['paswd']) {
			$smarty->assign('RETURN_MODULE', isset($_REQUEST['return_module']) ? vtlib_purify($_REQUEST['return_module']) : '');
			$smarty->assign('RETURN_ACTION', isset($_REQUEST['return_action']) ? vtlib_purify($_REQUEST['return_action']) : '');
			$smarty->assign('RETURN_ID', isset($_REQUEST['return_id']) ? vtlib_purify($_REQUEST['return_id']) : '');
			$smarty->assign('RETURN_VIEWNAME', isset($_REQUEST['return_viewname']) ? vtlib_purify($_REQUEST['return_viewname']) : '');
			if (isset($isduplicate)) {
				$smarty->assign('isDuplicate', $isduplicate);
			}
			$smarty->assign('ID', $record);
			if (isset($_REQUEST['ciasset']) && !isset($_REQUEST['cirelto'])) {
				$assetrs = $adb->pquery('select account from vtiger_assets where assetsid = ?', array($_REQUEST['ciasset']));
				if (!empty($assetrs)) {
					$asset = $adb->fetch_array($assetrs);
					if (!empty($asset['account'])) {
						$_REQUEST['cirelto'] = $asset['account'];
						$_REQUEST['cirelto_type'] = 'Accounts';
					}
				}
			}
			$smarty->assign('BadPassword', 'true');
			$focus->set_cinfo_history($record, 'accessnok', '');
			$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'GetPassword.tpl'));
		} else {
			$smarty->assign('CITimeout', $row['timeout']);
			$smarty->assign('cidwspinfo', $focus->k87rgsz5f4g9eer($cidwspinfo));
			$focus->set_cinfo_history($record, 'retrieve', 'EditView');
			//added to fix the issue4600
			$searchurl = getBasic_Advance_SearchURL();
			$smarty->assign('SEARCH', $searchurl);
			//4600 ends

			if ($record) {
				$focus->id = $record;
				$focus->mode = 'edit';
				$focus->retrieve_entity_info($record, $currentModule);
			}
			//decrypt here
			$focus->column_fields = $focus->decryptFields($focus->column_fields, $cidwspinfo);
			if ($isduplicate == 'true') {
				$focus->id = '';
				$focus->mode = '';
				$focus->column_fields['isduplicatedfromrecordid'] = $record; // in order to support duplicate workflows
			}
			$focus->preEditCheck($_REQUEST, $smarty);
			if (!empty($_REQUEST['save_error']) && $_REQUEST['save_error'] == 'true') {
				if (!empty($_REQUEST['encode_val'])) {
					global $current_user;
					$encode_val = vtlib_purify($_REQUEST['encode_val']);
					$decode_val = base64_decode($encode_val);
					$explode_decode_val = explode('&', trim($decode_val, '&'));
					$tabid = getTabid($currentModule);
					foreach ($explode_decode_val as $fieldvalue) {
						$value = explode('=', $fieldvalue);
						$field_name_val = $value[0];
						$field_value =urldecode($value[1]);
						$finfo = VTCacheUtils::lookupFieldInfo($tabid, $field_name_val);
						if ($finfo !== false) {
							switch ($finfo['uitype']) {
								case '56':
									$field_value = $field_value=='on' ? '1' : '0';
									break;
								case '7':
								case '9':
								case '72':
									$field_value = CurrencyField::convertToDBFormat($field_value, null, true);
									break;
								case '71':
									$field_value = CurrencyField::convertToDBFormat($field_value);
									break;
								case '33':
								case '3313':
								case '3314':
									if (is_array($field_value)) {
										$field_value = implode(' |##| ', $field_value);
									}
									break;
							}
						}
						$focus->column_fields[$field_name_val] = $field_value;
					}
				}
				$errormessageclass = isset($_REQUEST['error_msgclass']) ? vtlib_purify($_REQUEST['error_msgclass']) : '';
				$errormessage = isset($_REQUEST['error_msg']) ? vtlib_purify($_REQUEST['error_msg']) : '';
				$smarty->assign('ERROR_MESSAGE_CLASS', $errormessageclass);
				$smarty->assign('ERROR_MESSAGE', $errormessage);
			} elseif ($focus->mode != 'edit') {
				setObjectValuesFromRequest($focus);
			}
			$smarty->assign('MASS_EDIT', '0');
			$disp_view = getView($focus->mode);
			$blocks = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields);
			$smarty->assign('BLOCKS', $blocks);
			$basblocks = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields, 'BAS');
			$smarty->assign('BASBLOCKS', $basblocks);
			$advblocks = getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields, 'ADV');
			$smarty->assign('ADVBLOCKS', $advblocks);

			$custom_blocks = getCustomBlocks($currentModule, $disp_view);
			$smarty->assign('CUSTOMBLOCKS', $custom_blocks);
			$smarty->assign('FIELDS', $focus->column_fields);

			$smarty->assign('OP_MODE', $disp_view);

			if ($focus->mode == 'edit' || $isduplicate == 'true') {
				$recordName = array_values(getEntityName($currentModule, $record));
				$recordName = isset($recordName[0]) ? $recordName[0] : '';
				$smarty->assign('NAME', $recordName);
				$smarty->assign('UPDATEINFO', updateInfo($record));
			}

			if (isset($_REQUEST['return_module'])) {
				$smarty->assign('RETURN_MODULE', vtlib_purify($_REQUEST['return_module']));
			}
			if (isset($_REQUEST['return_action'])) {
				$smarty->assign('RETURN_ACTION', vtlib_purify($_REQUEST['return_action']));
			}
			if (isset($_REQUEST['return_id'])) {
				$smarty->assign('RETURN_ID', vtlib_purify($_REQUEST['return_id']));
			}
			if (isset($_REQUEST['return_viewname'])) {
				$smarty->assign('RETURN_VIEWNAME', vtlib_purify($_REQUEST['return_viewname']));
			}
			$upload_maxsize = GlobalVariable::getVariable('Application_Upload_MaxSize', 3000000, $currentModule);
			$smarty->assign('UPLOADSIZE', $upload_maxsize/1000000); //Convert to MB
			$smarty->assign('UPLOAD_MAXSIZE', $upload_maxsize);

			// Field Validation Information
			$tabid = getTabid($currentModule);
			$validationData = getDBValidationData($focus->tab_name, $tabid);
			$validationArray = split_validationdataArray($validationData);

			$smarty->assign('VALIDATION_DATA_FIELDNAME', $validationArray['fieldname']);
			$smarty->assign('VALIDATION_DATA_FIELDDATATYPE', $validationArray['datatype']);
			$smarty->assign('VALIDATION_DATA_FIELDLABEL', $validationArray['fieldlabel']);

			// In case you have a date field
			$smarty->assign('CALENDAR_LANG', $app_strings['LBL_JSCALENDAR_LANG']);
			$smarty->assign('CALENDAR_DATEFORMAT', parse_calendardate($app_strings['NTC_DATE_FORMAT']));

			// Module Sequence Numbering
			$mod_seq_field = getModuleSequenceField($currentModule);
			if ($focus->mode != 'edit' && $mod_seq_field != null) {
				$autostr = getTranslatedString('MSG_AUTO_GEN_ON_SAVE');
				list($mod_seq_string, $mod_seq_prefix, $mod_seq_no, $doNative) = cbEventHandler::do_filter('corebos.filter.ModuleSeqNumber.get', array('', '', '', true));
				if ($doNative) {
					$mod_seq_string = $adb->pquery('SELECT prefix, cur_id from vtiger_modentity_num where semodule=? and active=1', array($currentModule));
					$mod_seq_prefix = $adb->query_result($mod_seq_string, 0, 'prefix');
					$mod_seq_no = $adb->query_result($mod_seq_string, 0, 'cur_id');
				}
				if ($adb->num_rows($mod_seq_string) == 0 || $focus->checkModuleSeqNumber($focus->table_name, $mod_seq_field['column'], $mod_seq_prefix.$mod_seq_no)) {
					$smarty->assign('ERROR_MESSAGE_CLASS', 'cb-alert-warning');
					$smarty->assign('ERROR_MESSAGE', '<b>'. getTranslatedString($mod_seq_field['label']). ' '. getTranslatedString('LBL_NOT_CONFIGURED')
						.' - '. getTranslatedString('LBL_PLEASE_CLICK') .' <a href="index.php?module=Settings&action=CustomModEntityNo&parenttab=Settings&selmodule='.$currentModule.'">'.getTranslatedString('LBL_HERE').'</a> '
						. getTranslatedString('LBL_TO_CONFIGURE'). ' '. getTranslatedString($mod_seq_field['label']) .'</b>');
				} else {
					$smarty->assign('MOD_SEQ_ID', $autostr);
				}
			} else {
				if (!empty($mod_seq_field) && !empty($mod_seq_field['name']) && !empty($focus->column_fields[$mod_seq_field['name']])) {
					$smarty->assign('MOD_SEQ_ID', $focus->column_fields[$mod_seq_field['name']]);
				} else {
					$smarty->assign('MOD_SEQ_ID', '');
				}
			}

			// Gather the custom link information to display
			include_once 'vtlib/Vtiger/Link.php';
			$customlink_params = array('MODULE'=>$currentModule, 'RECORD'=>$focus->id, 'ACTION'=>vtlib_purify($_REQUEST['action']));
			$smarty->assign(
				'CUSTOM_LINKS',
				Vtiger_Link::getAllByType($tabid, array('EDITVIEWBUTTON','EDITVIEWBUTTONMENU','EDITVIEWWIDGET','EDITVIEWHTML'), $customlink_params, null, $focus->id)
			);
			// Gather the help information associated with fields
			$smarty->assign('FIELDHELPINFO', vtlib_getFieldHelpInfo($currentModule));
			$smarty->assign('Module_Popup_Edit', isset($_REQUEST['Module_Popup_Edit']) ? vtlib_purify($_REQUEST['Module_Popup_Edit']) : 0);
			$smarty->assign('SandRActive', GlobalVariable::getVariable('Application_SaveAndRepeatActive', 0, $currentModule));
			$cbMapFDEP = Vtiger_DependencyPicklist::getFieldDependencyDatasource($currentModule);
			$smarty->assign('FIELD_DEPENDENCY_DATASOURCE', json_encode($cbMapFDEP));

			$smarty->display(vtlib_getModuleTemplate('ConfidentialInfo', 'EditView.tpl'));
		}
	}
}
?>
