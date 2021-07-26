<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule("iblock"))
	die('SERVER_ERROR');

$arIBlockType = CIBlockParameters::GetIBlockTypes();
$arIBlock     = array();
$rsIBlock     = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE" => "Y"));
while ($arr = $rsIBlock->Fetch()) {
	$arIBlock[$arr["ID"]] = "[" . $arr["ID"] . "] " . $arr["NAME"];
}

$arComponentParameters = array(
	'PARAMETERS' => array(
		'CACHE_TIME'    => array('DEFAULT' => 3600),
		"IBLOCK_TYPE"   => array(
			"PARENT"  => "BASE",
			"NAME"    => "Тип инфо блока",
			"TYPE"    => "LIST",
			"VALUES"  => $arIBlockType,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID"     => array(
			"PARENT"            => "BASE",
			"NAME"              => "Инфо блок",
			"TYPE"              => "LIST",
			"VALUES"            => $arIBlock,
			"REFRESH"           => "Y",
			"ADDITIONAL_VALUES" => "Y",
		),
		"MSG_EVENT"     => array(
			"PARENT"        => "BASE",
			"NAME"          => "ID почтового события",
			"TYPE"          => "STRING",
			"DEFAULT_VALUE" => "Y",
		),
		"CAPTCHA"       => array(
			"PARENT"        => "BASE",
			"NAME"          => "CAPTCHA",
			"TYPE"          => "CHECKBOX",
			"DEFAULT_VALUE" => "Y",
		),
		"CHECK_MSG"     => array(
			"PARENT"  => "BASE",
			"NAME"    => "Проверять наличие сообщения",
			"TYPE"    => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"AJAX"          => array(
			"PARENT"  => "BASE",
			"NAME"    => "AJAX",
			"TYPE"    => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"CRM"           => array(
			"PARENT"  => "BASE",
			"NAME"    => "CRM",
			"TYPE"    => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"CRM_URL"       => array(
			"PARENT"        => "BASE",
			"NAME"          => "CRM_URL",
			"TYPE"          => "STRING",
			"DEFAULT_VALUE" => "",
		),
		"CRM_LOGIN"       => array(
			"PARENT"        => "BASE",
			"NAME"          => "CRM_LOGIN",
			"TYPE"          => "STRING",
			"DEFAULT_VALUE" => "",
		),
		"CRM_PASSWORD"       => array(
			"PARENT"        => "BASE",
			"NAME"          => "CRM_PASSWORD",
			"TYPE"          => "STRING",
			"DEFAULT_VALUE" => "",
		),
		"SUBMIT_EVENT"       => array(
			"PARENT"        => "BASE",
			"NAME"          => "SUBMIT_EVENT",
			"TYPE"          => "STRING",
			"DEFAULT_VALUE" => "",
		),
		"CRM_SOURCE_ID" => array(
			"PARENT"        => "BASE",
			"NAME"          => "CRM_SOURCE_ID",
			"TYPE"          => "STRING",
			"DEFAULT_VALUE" => "site-miningcentr",
		),
		"USER_CONSENT" => array(),
	),

);
?>
