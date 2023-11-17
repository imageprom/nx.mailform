<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @var string $templateFolder */
/** @var string $mTemplate */
/** @var array $arCurrentValues */
/** @var array $arCurrentValues */
/** @const string LANG_CHARSET */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

CJSCore::Init(array('jquery'));
$path = $_SERVER['DOCUMENT_ROOT'] . $templateFolder;
?>

<div id="feedback_form_<?=$arParams['FORM_ID']?>"
     class="ajax_call_form_static form-evan">
    <?
    $arResult['R'] = new NXMailForm\CNXInput($arResult['ITEMS'], $arParams['FORM_ID']);
    $arResult['R']->SetErrorColorScheme(array(
        'border' => '1px solid #a97c7c',
        'background' => '#fbeaea',
    ));
    if ($arResult['R']->isSubmit()) {
        if ($arResult['R']->CheckAllValues()) { ?>
            <div class="call_error"><?= $arResult["R"]->EchoError() ?></div><?;
        }

        else {
            require_once($path . '/mailer.php');
            if (!$arResult['PHP_MAILER']->Send()) {?>
                <h6 class='mail_send'>Ошибка при отправке сообщения</h6><?
            }
            else {
                ?>
                <h6 class='mail_send mail_send_ok <?if($arParams['MAGAZINE_CONNECT'] == 'Y'): ?>nx-basket-result-clear<?endif;?>'>
                    Ваше сообщение успешно отправлено.</h6>
                <?if ($arParams['MANAGER_BACK'] == "Y"):?>
                    <p>Наш менеджер свяжется с Вами и сообщит о поступлении товара.</p>
                <?endif;?>
                <?if ($arParams['SHOW_LOG'] == 'Y') {
                    require_once($path . '/save.php');
                }
            }
        }
    }
    ?>
    <?require_once($path . '/form.php');?>
    <?=$arResult['R']->preHTML($mTemplate);?>
</div>