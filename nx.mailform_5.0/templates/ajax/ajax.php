<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->RestartBuffer();
$path = $_SERVER['DOCUMENT_ROOT'].$templateFolder;
$arResult['R'] = new NXMailForm\CNXInput($arResult['ITEMS'], $arParams['FORM_ID']);
$arResult['R']->SetErrorColorScheme( array('border' => '1px solid #a97c7c', 'background'=>'#fbeaea' ));
$res['id'] = $arParams['FORM_ID'];
$res['send'] = false;

if ($arResult['R']->isSubmit()) {
	if ($arResult['R']->CheckAllValues() ) {
		$res['error'] = $arResult['R']->EchoError();
	} 
	else {
		require_once($path.'/mailer.php');
		if(!$arResult['PHP_MAILER']->Send()) {
			$res['error'] = '<div class="g-color-red">Ошибка при отправке сообщения</div>';
		} 
		else { 
			$class = '';
			$res['send'] = true;
			$res['message'] = 'Ваше сообщение успешно отправлено.';
			if($arParams['MAGAZINE_CONNECT'] == 'Y') {
				$res['clear_basket'] = true;
				$class = 'nx-basket-result-clear';	
			}

			$res['message_html'] = '<h3 class="landing-block-node-title landing-semantic-title-medium g-my-0 g-max-width-container g-pl-0 g-pr-0 text-center g-font-open-sans g-font-size-28">'.$arParams['FORM_TITLE'].'</h3>'.
								   '<p class="g-color-green1 mt-3 g-font-size-18 '.$class.' t_center">Ваше сообщение успешно отправлено.</p>';
			
			if($arParams['MANAGER_BACK'] == 'Y') {
				$res['add_message'] = '<p>Наш менеджер свяжется с Вами и сообщит о поступлении товара.</p>';
			}
			
			if ($arParams['SHOW_LOG']=='Y') {
				require_once($path.'/save.php');
			}
		}
	}
}

require_once($path.'/form.php');
$res['form'] = $arResult['R']->preHTML($mTemplate);
$res['form_id'] ='feedback_form_'.$res['id'];
$res['form_class'] = 'ajax_call_form_static';

$arResult['AJAX'] = $res;
echo json_encode($arResult);
?>