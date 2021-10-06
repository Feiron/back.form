<?php
/**
 * User: Feiron
 * Date: 28.06.2021
 *
 * @var array $arParams
 * @var array $arResult
 *
 */

namespace Fei\Components;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web;

class IblockFrom extends \CBitrixComponent implements Controllerable
{
	public function configureActions(): array
	{
		return [
			'addResult' => [
				'prefilters' => [],
			],
		];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'IBLOCK_ID',
			'IBLOCK_TYPE',
			'CAPTCHA',
			'MSG_EVENT',
			'SUBMIT_EVENT',
			'CHECK_MSG',
			'AJAX',
			'CRM',
			'CRM_URL',
			'CRM_SOURCE_ID',
			'CRM_FIELDS',
		];
	}

	public function onPrepareComponentParams($arParams)
	{
		\CModule::IncludeModule('iblock') or die();
		\CJSCore::Init(array('ajax', 'window'));

		$arParams['IBLOCK_ID']   = $arParams['IBLOCK_ID'] > 0 ? $arParams['IBLOCK_ID'] : 0;
		$arParams['IBLOCK_TYPE'] = strlen($arParams['IBLOCK_TYPE']) > 0 ? $arParams['IBLOCK_TYPE'] : false;
		$arParams['CAPTCHA']     = !($arParams['CAPTCHA'] == 'N');
		$arParams['MSG_EVENT']   = strlen($arParams['MSG_EVENT']) > 0 ? $arParams['MSG_EVENT'] : false;
		$arParams['CHECK_MSG']   = $arParams['CHECK_MSG'] == 'Y';
		$arParams['AJAX']        = $arParams['AJAX'] == "Y";

		$arParams['SUBMIT_EVENT'] = $arParams['SUBMIT_EVENT'] ?: false;

		if ($arParams['AJAX']) {
			\Bitrix\Main\Page\Asset::getInstance()->addJs($this->getPath() . "/js/formsubmitter.js");
			$arParams['CAPTCHA'] = false;
		}

		$arParams['CACHE_TIME'] = $arParams['CACHE_TIME'] >= 0 ? $arParams['CACHE_TIME'] : 3600;


		$arParams['CRM']     = $arParams['CRM'] == "Y";
		$arParams['CRM_URL'] = $arParams['CRM_URL'] ?: 'https://cp.gpw.eu/crm/configs/import/lead.php';

		$arParams['CRM_SOURCE_ID'] = $arParams['CRM_SOURCE_ID'] ?: 'site-miningcentr';
		$arParams['CRM_FIELDS']    = ['NAME', 'EMAIL', 'DETAIL_TEXT'];


		if (!$arParams['CRM_LOGIN'] || !$arParams['CRM_PASSWORD']) {
			$arParams['CRM'] = false;
		}

		if ($arParams['IBLOCK_ID'] <= 0 || !$arParams['IBLOCK_TYPE']) {
			\ShowError("IBLOCK_NOT_DEFINED");

			return false;
		}

		if ($arParams['CAPTCHA']) {
			$this->arResult["CAPTCHA_CODE"] = htmlspecialchars($GLOBALS["APPLICATION"]->CaptchaGetCode());
		}

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$this->arResult['SESS_ID'] = bitrix_sessid_post();
		$this->arResult['FIELDS']  = $this->getFormFields();

		if ($this->arParams['AJAX']) {
			$this->arResult['AJAX_SIGNED'] = $this->getSignedParameters();
		}

		$this->includeComponentTemplate();

		return $this->arResult;
	}

	/**
	 * @param $arFields
	 *
	 * @throws \Exception
	 */
	public function addResultAction($arFields)
	{
		$arFormErrors = [];

		try {
			$this->addFormResult($arFields, $arFormErrors);
		} catch (\Exception $e) {
			throw new \Exception(
				\GetMessage('FORM_ERRORS') . implode('\n', $arFormErrors)
			);
		}
	}

	/**
	 * @param       $arFields
	 * @param array $arFormErrors
	 *
	 * @throws \Exception
	 */
	public function addFormResult($arFields, array &$arFormErrors = [])
	{

		$arFormErrors = [];

		$arIblockFields = $this->getFormFields();

		$arIblockFields['MSG'] = [
			"CODE"  => 'MSG',
			"VALUE" => ''
		];

		foreach ($arFields as $key => $value) {
			if (array_key_exists($key, $arIblockFields)) {

				$FIELD          = &$arIblockFields[$key];
				$FIELD['VALUE'] = $value;

				$VAL = &$FIELD['VALUE'];

				switch ($key) {
					case 'EMAIL':
						if ($FIELD['IS_REQUIRED'] == "Y" && !\check_email($VAL)) {
							$arFormErrors[] = \GetMessage("FIELD_EMAIL_NOTVALID");
						}
						break;

					case 'CAPTCHA':
						$captcha_code = htmlspecialchars($_POST['captcha_code']);
						$captcha_sid  = htmlspecialchars($_POST['captcha_sid']);
						if (!$GLOBALS['APPLICATION']->CaptchaCheckCode($captcha_code, $captcha_sid)) {
							$arFormErrors[] = \GetMessage('FIELD_CAPTCHA_WRONG');
						}
						break;

					case 'MSG':
						$VAL = htmlspecialchars(trim($VAL));
						if (strlen($VAL) <= 0 && $this->arParams['CHECK_MSG']) {
							$arFormErrors[]      = \GetMessage("FIELD_EMPTY", array("#FIELD#" => GetMessage('FIELD_MESSAGE')));
							$arFormErrors['MSG'] = true;
						}
						break;

					default:
						if ($FIELD['IS_REQUIRED'] == "Y") {
							if (!$VAL) {
								$arFormErrors[] = \GetMessage("FIELD_EMPTY", array("#FIELD#" => $FIELD['PROPS']['NAME']));
							}
						}
				}
			}
		}

		if (!empty($arFormErrors)) {
			throw new \Exception('FORM_FIELDS_ERROR');
		}

		/*******************************************************
		 * SEND and Write
		 *******************************************************/

		/**
		 * @var $PROPS          array Для инфоблока
		 * @var $arIblockFields array поля + VALUE
		 * @var $EVENT_FIELDS   array для события code => value
		 */

		$PROPS = [];

		$strElementName =
			array_key_exists('NAME', $arIblockFields) && strlen($arIblockFields['NAME']['VALUE']) ?
				$arIblockFields['NAME']['VALUE'] :
				\GetMessage('ELEMENT_NAME');

		$EVENT_FIELDS = [];

		foreach ($arIblockFields as $code => $field) {


			switch ($field['TYPE']) {

				case 'F':

					if (is_array($field['VALUE'])) {
						foreach ($field['VALUE'] as $iFileID) {
							$PROPS[$code][] = \CFile::MakeFileArray($iFileID);
						}
					} else {
						$PROPS[$code][] = \CFile::MakeFileArray($field['VALUE']);
					}

					/*
					 * TODO: Непонятно что отправлять в массив события
					 */
					$EVENT_FIELDS[$code] = $field['VALUE'];

					break;
				case 'L':
					$EVENT_FIELDS[$code]   = $field['LIST_ENUM'][$field['VALUE']]['VALUE'];
					$PROPS[$code]['VALUE'] = $field['VALUE'];

					break;
				default:
					$PROPS[$code]['VALUE'] = $field['VALUE'];
					$EVENT_FIELDS[$code]   = $field['VALUE'];
			}
		}

		$arIblockElement = array(
			"IBLOCK_ID"        => $this->arParams['IBLOCK_ID'],
			"PROPERTY_VALUES"  => $PROPS,
			"NAME"             => date("d.m.Y H:i:s") . ' ' . $strElementName,
			"ACTIVE"           => "Y",
			"DETAIL_TEXT"      => $arIblockFields['MSG']['VALUE'] ?: "",
			"DETAIL_TEXT_TYPE" => "html"
		);

		$el = new \CIBlockElement();

		if ($NEW_ID = $el->Add($arIblockElement)) {

			$EVENT_FIELDS['PAGE_URL'] = $GLOBALS['APPLICATION']->GetCurPage();
			$arEventFields            = $EVENT_FIELDS;
			$arEventFields['MSG']     = $arIblockElement['DETAIL_TEXT'];

			if ($this->arParams['MSG_EVENT']) {
				\CEvent::SendImmediate($this->arParams['MSG_EVENT'], SITE_ID, $arEventFields);
			}

			if ($this->arParams['CRM']) {

				$arCrmFields = [];
				foreach ($this->arParams['CRM_FIELDS'] as $strCode) {
					if (array_key_exists($strCode, $arIblockFields)) {
						$arCrmFields[$strCode] = $arIblockFields['VALUE'];
					}
				}

				if ($this->addCrmFormResult($arCrmFields)) {
					if (array_key_exists('CRM', $arIblockFields)) {
						foreach ($arIblockFields['CRM']['LIST_ENUM'] as $uID => $arField) {
							if ($arField['XML_ID'] == "Y") {
								\CIBlockElement::SetPropertyValueCode($NEW_ID, "CRM", $uID);
							}
						}
					}
				}

			}

			/**
			 * Checking events
			 */
			if ($this->arParams['SUBMIT_EVENT']) {
				$event = new \Bitrix\Main\Event(
					"main",
					$this->arParams['SUBMIT_EVENT'],
					$arEventFields
				);
				$event->send();
				if ($event->getResults()) {
					foreach ($event->getResults() as $evenResult) {

						if ($evenResult->getType() != \Bitrix\Main\EventResult::SUCCESS) {
							$arFormErrors[] = 'Ошибка обработчика';
							throw new \Exception('FORM_ERROR');
						}
					}
				}
			}
		}
	}

	/**
	 * @param $arFields
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function addCrmFormResult($arFields): bool
	{

		$obHttp = new Web\HttpClient();

		$strUrl = $this->arParams['CRM_URL'];

		$arData = [
			'LOGIN'     => $this->arParams['CRM_LOGIN'],
			'PASSWORD'  => $this->arParams['CRM_PASSWORD'],
			'TITLE'     => 'Запрос с: ' . SITE_SERVER_NAME,
			'SOURCE_ID' => $this->arParams['CRM_SOURCE_ID'],
		];

		$arData = array_merge($arData, $arFields);

		if (
			$obHttp->post($strUrl, $arData) &&
			$obHttp->getStatus() == 200
		) {

			return true;
		}

		return false;
	}

	public function getFormFields(): array
	{
		$arFields = [];

		$obCache  = new \CPHPCache;
		$cache_id = $this->GetTemplateName() . serialize([$this->arParams['IBLOCK_ID'], $this->arParams['IBLOCK_TYPE']]);

		if ($obCache->InitCache($this->arParams['CACHE_TIME'], $cache_id, "/forms/fields")) {
			// получаем закешированные переменные
			$arFields = $obCache->GetVars();

		} else {

			if ($obCache->StartDataCache($this->arParams['CACHE_TIME'])) {

				$dbFields = \CIBlockProperty::GetList(
					array("sort" => "asc"),
					array("ACTIVE" => "Y", "IBLOCK_ID" => $this->arParams['IBLOCK_ID'])
				);

				while ($arField = $dbFields->GetNext()) {

					$arFields[$arField['CODE']] = array(
						'ID'            => $arField['ID'],
						'CODE'          => $arField['CODE'],
						'DEFAULT_VALUE' => $arField['DEFAULT_VALUE'],
						'IS_REQUIRED'   => $arField['IS_REQUIRED'],
						'ACTIVE'        => $arField['ACTIVE'],
						'TYPE'          => $arField['PROPERTY_TYPE'],
						'PROPS'         => $arField
					);

					if ($arField['PROPERTY_TYPE'] == "L") {
						$arValues       = [];
						$property_enums = \CIBlockPropertyEnum::GetList(
							array(
								"DEF"  => "DESC",
								"SORT" => "ASC"
							),
							array("IBLOCK_ID" => $this->arParams['IBLOCK_ID'], "CODE" => $arField['CODE'])
						);
						while ($enum_fields = $property_enums->GetNext()) {
							$arValues[$enum_fields['ID']] = array(
								"ID"     => $enum_fields['ID'],
								"VALUE"  => $enum_fields['VALUE'],
								"DEF"    => $enum_fields['DEF'],
								"SORT"   => $enum_fields['SORT'],
								"XML_ID" => $enum_fields['XML_ID']
							);

						}
						$arFields[$arField['CODE']]['LIST_ENUM'] = $arValues;
					}
				}

				/* TODO: USER_CONSENT + Component support
				if ($this->arParams['USER_CONSENT_ID'] > 0) {
					$obAgreement = new \Bitrix\Main\UserConsent\Agreement($this->arParams['USER_CONSENT_ID']);

					$arFields['AGREEMENT_LABEL'] = $obAgreement->getLabelText();
					$arFields['AGREEMENT_TEXT']  = $obAgreement->getText();
				}
				*/

				$obCache->EndDataCache(
					$arFields
				);
			}
		}

		return $arFields;
	}
}
