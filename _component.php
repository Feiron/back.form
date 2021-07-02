<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var $arResult
 * @var $arParams
 *
 *
 */

\CModule::IncludeModule('iblock') or die();

use Bitrix\Main\Web;

/**
 * PARAMETERS
 **/

$arResult['IBLOCK_ID']   = $arParams['IBLOCK_ID'] > 0 ? $arParams['IBLOCK_ID'] : 0;
$arResult['IBLOCK_TYPE'] = strlen($arParams['IBLOCK_TYPE']) > 0 ? $arParams['IBLOCK_TYPE'] : false;
$arResult['CAPTCHA']     = !($arParams['CAPTCHA'] == 'N');
$arResult['MSG_EVENT']   = strlen($arParams['MSG_EVENT']) > 0 ? $arParams['MSG_EVENT'] : 'iblock_backform_submit';
$arResult['CHECK_MSG']   = $arParams['CHECK_MSG'] == 'Y';
$arResult['AJAX']        = $arParams['AJAX'] == "Y";

$arResult['CRM']     = $arParams['CRM'] == "Y" ? true : false;
$arParams['CRM_URL'] = $arParams['CRM_URL'] ? $arParams['CRM_URL'] : 'https://cp.gpw.eu/crm/configs/import/lead.php';

$arParams['CRM_SOURCE_ID'] = $arParams['CRM_SOURCE_ID'] ? $arParams['CRM_SOURCE_ID'] : 'site-miningcentr';


if (!$arParams['CRM_LOGIN'] || !$arParams['CRM_PASSWORD']) {
	$arResult['CRM'] = false;
}

if ($arResult['IBLOCK_ID'] <= 0 || !$arResult['IBLOCK_TYPE']) {
	return false;
}

if ($arResult['CAPTCHA']) {
	$arResult["CAPTCHA_CODE"] = htmlspecialchars($GLOBALS["APPLICATION"]->CaptchaGetCode());
}


$obCache = new \CPHPCache;

$arResult['CACHE_TIME'] = $arParams['CACHE_TIME'] >= 0 ? $arParams['CACHE_TIME'] : 3600;

$cache_id = $this->GetTemplateName() . serialize([$arResult['IBLOCK_ID'], $arResult['IBLOCK_TYPE']]);

// если кеш есть и он ещё не истек то
if ($obCache->InitCache($arResult['CACHE_TIME'], $cache_id, "/fields")) {
	// получаем закешированные переменные
	$arResult = array_merge($obCache->GetVars(), $arResult);
} else {
	if ($obCache->StartDataCache($arResult['CACHE_TIME'])) {
		// записываем предварительно буферизированный вывод в файл кеша
		// вместе с дополнительной переменной
		$fields_db = \CIBlockProperty::GetList(
			array("sort" => "asc"),
			array("ACTIVE" => "Y", "IBLOCK_ID" => $arResult['IBLOCK_ID'])
		);

		while ($field = $fields_db->GetNext()) {

			$arResult['FIELDS'][$field['CODE']] = array(
				'ID'            => $field['ID'],
				'CODE'          => $field['CODE'],
				'DEFAULT_VALUE' => $field['DEFAULT_VALUE'],
				'IS_REQUIRED'   => $field['IS_REQUIRED'],
				'ACTIVE'        => $field['ACTIVE'],
				'TYPE'          => $field['PROPERTY_TYPE'],
				'PROPS'         => $field
			);

			if ($field['PROPERTY_TYPE'] == "L") {
				$property_enums = \CIBlockPropertyEnum::GetList(
					array(
						"DEF"  => "DESC",
						"SORT" => "ASC"
					),
					array("IBLOCK_ID" => $arResult['IBLOCK_ID'], "CODE" => $field['CODE'])
				);
				while ($enum_fields = $property_enums->GetNext()) {
					$list_values[$enum_fields['ID']] = array(
						"ID"     => $enum_fields['ID'],
						"VALUE"  => $enum_fields['VALUE'],
						"DEF"    => $enum_fields['DEF'],
						"SORT"   => $enum_fields['SORT'],
						"XML_ID" => $enum_fields['XML_ID']
					);

				}
				$arResult['FIELDS'][$field['CODE']]['LIST_ENUM'] = $list_values;
			}
		}
		$arResultCached = ["FIELDS" => $arResult['FIELDS']];

		if ($arParams['USER_CONSENT_ID'] > 0) {
			$obAgreement = new \Bitrix\Main\UserConsent\Agreement($arParams['USER_CONSENT_ID']);

			$arResultCached['AGREEMENT_LABEL'] = $obAgreement->getLabelText();
			$arResultCached['AGREEMENT_TEXT']  = $obAgreement->getText();
		}

		$obCache->EndDataCache(
			$arResultCached
		);
	}
}
//////////////////////////////////////////////////////////////////

if ($arResult['AJAX'] && $_POST['AJAX'] == 1) {
	$APPLICATION->RestartBuffer();
}

/*******************************************************
 * FORM
 *******************************************************/
if (\check_bitrix_sessid() && isset($_POST['form_submit'])) {

	try {

		if ($arResult['AJAX'] && $_POST['AJAX'] != 1) {
			echo Web\Json::encode(array('status' => -1, 'errors' => 'SERVER_FAULT'));
			die();
		}

		/***********************VALIDATE/******************************/
		foreach ($_POST as $key => $value) {
			if (array_key_exists($key, $arResult['FIELDS'])) {
				$FIELD = &$arResult['FIELDS'][$key];
				$VAL   = &$arResult['FIELDS'][$key]['VALUE'];
				$VAL   = htmlspecialchars(trim($value));
				if ($FIELD['IS_REQUIRED'] == "Y") {
					if (strlen($VAL) <= 0) {
						$arResult["ERRORS"][] = \GetMessage("FIELD_EMPTY", array("#FIELD#" => $FIELD['PROPS']['NAME']));
						$FIELD['ERROR']       = true;
					}
				}
			}
		}

		/*******************************************************
		 * EMAIL
		 *******************************************************/

		if (strlen($arResult['FIELDS']['EMAIL']['VALUE']) > 0) {
			if (!check_email($arResult['FIELDS']['EMAIL']['VALUE'])) {
				$arResult["ERRORS"][] = \GetMessage("FIELD_EMAIL_NOTVALID");
			}
		}

		/*******************************************************
		 * CAPTCHA
		 *******************************************************/

		if ($arResult['CAPTCHA']) {
			$captcha_code = htmlspecialchars($_POST['captcha_code']);
			$captcha_sid  = htmlspecialchars($_POST['captcha_sid']);
			if (!$APPLICATION->CaptchaCheckCode($captcha_code, $captcha_sid)) {
				$arResult['ERRORS'][] = \GetMessage('FIELD_CAPTCHA_WRONG');
			}
		}

		/*******************************************************
		 * MSG
		 *******************************************************/

		$arResult['POST']['MSG'] = htmlspecialchars(trim($_POST['MSG']));
		if (strlen($arResult['POST']['MSG']) <= 0 && $arResult['CHECK_MSG']) {
			$arResult['ERRORS'][]     = \GetMessage("FIELD_EMPTY", array("#FIELD#" => GetMessage('FIELD_MESSAGE')));
			$arResult['ERROR']['MSG'] = true;
		}

		/*******************************************************
		 * SEND and Write
		 *******************************************************/

		if (count($arResult['ERRORS']) <= 0) {
			$arResult['POST']['NAME'] = $arResult['FIELDS']['NAME']['VALUE'];
			$el                       = new \CIBlockElement();
			foreach ($arResult['FIELDS'] as $code => $field) {
				$PROPS[$code]['VALUE'] = $field['VALUE'];
				if ($field['TYPE'] == "L") {
					$EVENT_FIELDS[$code] = $field['LIST_ENUM'][$field['VALUE']]['VALUE'];
				} else {
					$EVENT_FIELDS[$code] = $field['VALUE'];
				}
			}

			$arLoadProductArray = array(
				"IBLOCK_ID"        => $arResult['IBLOCK_ID'],
				"PROPERTY_VALUES"  => $PROPS,
				"NAME"             => date("d m, Y H:i:s") . ' ' . $arResult['POST']['NAME'],
				"ACTIVE"           => "Y",
				"DETAIL_TEXT"      => $arResult['POST']['MSG'],
				"DETAIL_TEXT_TYPE" => "html"
			);


			if ($NEW_ID = $el->Add($arLoadProductArray)) //if(1)
			{
				if ($arResult['MSG_EVENT']) {
					$EVENT_FIELDS['PAGE_URL'] = $APPLICATION->GetCurPage();
					$arEventFields            = $EVENT_FIELDS;
					$arEventFields['MSG']     = $arResult['POST']['MSG'];
					\CEvent::SendImmediate($arResult['MSG_EVENT'], SITE_ID, $arEventFields);
				}

				if ($arResult['CRM']) {

					$obHttp = new Web\HttpClient();

					$strUrl = $arParams['CRM_URL'];
					$arData = [
						'LOGIN'      => $arParams['CRM_LOGIN'],
						'PASSWORD'   => $arParams['CRM_PASSWORD'],
						'TITLE'      => 'Запрос с: ' . SITE_SERVER_NAME,
						'SOURCE_ID'  => $arParams['CRM_SOURCE_ID'],
						'EMAIL_WORK' => $arResult['FIELDS']['EMAIL']['VALUE'],
						'PHONE_WORK' => $arResult['FIELDS']['PHONE']['VALUE'],
						'NAME'       => $arResult['FIELDS']['NAME']['VALUE'],
						'COMMENTS'   => $arResult['POST']['MSG'],

					];

					if (
						$obHttp->post($strUrl, $arData) &&
						$obHttp->getStatus() == 200
					) {
						foreach ($arResult['FIELDS']['CRM']['LIST_ENUM'] as $uID => $arField) {
							if ($arField['XML_ID'] == "Y") {
								\CIBlockElement::SetPropertyValueCode($NEW_ID, "CRM", $uID);
							}
						}
					}
				}

				if ($arResult['AJAX']) {
					echo Web\Json::encode(array('status' => 1, [$arLoadProductArray, $arEventFields]));
				}

				//$_POST = array();
			} elseif ($arResult['AJAX']) {
				echo Web\Json::encode(array('status' => -1));
			}
		} else if ($arResult['AJAX']) {
			echo Web\Json::encode(array('status' => -1, 'errors' => $arResult['ERRORS']));
		}
	} catch (\Exception $e) {
		echo Web\Json::encode(array('status' => -2, 'errors' => $e->getMessage()));
	}
}
if ($_POST['AJAX'] == 1) {
	die();
}
$this->IncludeComponentTemplate();
?>