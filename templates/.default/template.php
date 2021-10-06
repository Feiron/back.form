<?
/**
 * @var $arResult
 * @var $arParams
 */
?>
<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<div class="form">
    <form action="" id="backform" class="form_send" method="post" enctype="multipart/form-data">
		<?= $arResult['SESS_ID']; ?>
        <input type="hidden" value="<?= $arResult['AJAX_SIGNED'] ?>" name="ajax_signed"/>
        <div class="row">
			<? foreach ($arResult['FIELDS'] as $code => $arField):

				switch (strtolower($code)) {
					case 'email':
						$strInputType = 'email';
						break;
					case 'phone':
						$strInputType = 'tel';
						break;
					default:
						$strInputType = 'text';
						break;
				}

				?>
                <div class="large-6 columns">
					<? if ($arField['TYPE'] == 'F') { ?>
                        <label><?= GetMessage("FIELD_" . $code) ? GetMessage("FIELD_" . $code) : $arField['PROPS']['NAME'] ?><? if ($arField['IS_REQUIRED'] == 'Y'): ?>
                                <span class="required" style="color: red;">*</span><? endif; ?>:
							<?
							$GLOBALS['APPLICATION']->IncludeComponent(
								"bitrix:main.file.input", "drag_n_drop",
								array(
									"INPUT_NAME"       => $code,
									"INPUT_VALUE"      => '',
									"INPUT_CAPTION"    => "25 мб",
									"MULTIPLE"         => $arField['PROPS']['MULTIPLE'],
									"MODULE_ID"        => "ok.main",
									"MAX_FILE_SIZE"    => "25000000",
									"ALLOW_UPLOAD"     => "F",
									"ALLOW_UPLOAD_EXT" => $arField['PROPS']['FILE_TYPE'],
									"CONTROL_ID"       => 'BackFormFile'
								),
								false
							); ?>
                        </label>
					<? } else if ($arField['TYPE'] == 'L') {
						?>
                        <label><?= GetMessage("FIELD_" . $code) ?><? if ($arField['IS_REQUIRED'] == 'Y'): ?>
                                <span class="required" style="color: red;">*</span><? endif; ?>:
                            <select<? if ($arField['IS_REQUIRED'] == 'Y'): ?> required=""<? endif; ?>
                                    type="<?= $strInputType ?>"
                                    id="i_<?= $code ?>"
                                    name="<?= $code ?>"
                            >
								<? foreach ($arField['LIST_ENUM'] as $uID => $arOption): ?>
                                    <option<?= $arOption['DEF'] == 'Y' ? ' checked=""' : '' ?> value="<?= $uID ?>"><?= $arOption['VALUE'] ?></option>
								<? endforeach; ?>
                            </select>
                        </label>
						<?
					} else { ?>
                        <label><?= GetMessage("FIELD_" . $code) ?><? if ($arField['IS_REQUIRED'] == 'Y'): ?>
                                <span class="required" style="color: red;">*</span><? endif; ?>:
                            <input<? if ($arField['IS_REQUIRED'] == 'Y'): ?> required=""<? endif; ?>
                                    type="<?= $strInputType ?>"
                                    id="i_<?= $code ?>"
                                    name="<?= $code ?>"
                                    value="<?= $arField['DEFAULT_VALUE'] ?>"
                                    placeholder="<?= GetMessage("FIELD_" . $code) ?>"
                            />
                        </label>
					<? } ?>
                </div>
			<? endforeach; ?>
        </div>
        <div class="row">
            <div class="large-12 columns">
                <label><?= GetMessage("FIELD_MSG") ?>:
                    <textarea rows="5" name="MSG" id="comment" placeholder=""></textarea>
                </label>
                <p class="AGREEMENT"><?= GetMessage("AGREEMENT") ?></a></p>
            </div>
        </div>

        <div class="row">
            <div class="large-12 columns">
                <button class="" type="submit">Отправить</button>
            </div>
        </div>
    </form>
</div>