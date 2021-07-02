<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => 'Форма обратной связи',
	"DESCRIPTION" => 'Создает форму обратной связи по свойствам инфоблока - считая детальную информацию сообщением. Отправляет письмо через евент',
	"ICON" => "/images/lists_list.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "utility",
        "CHILD" => array(
			"ID" => "misc",
			"NAME" => "Дополнительно"
		)
	),
);
?>
