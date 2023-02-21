<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;
if($arParams['MAGAZINE_CONNECT'] == 'Y')
	global $NX_BASKET_RESULT_DATA;
if(!($arParams['MAGAZINE_CONNECT'] == 'Y' && count($_SESSION[$NX_BASKET_RESULT_DATA['VAR']]) < 1)):?>
<div id="feedback_form_<?=$arParams["FORM_ID"]?>" class="ajax_call_form">
<del class="hide-form">Закрыть</del>
<h3><?=$arParams['FORM_TITLE']?></h3>
<?=html_entity_decode($arParams['TEXT'])?>
<?
$arResult["R"] = new nxInput($arResult["ITEMS"], $arParams["FORM_ID"]);
$arResult['R']->SetErrorColorCheme( array('border' => '1px solid #a97c7c', 'background'=>'#fbeaea' )); 
if ($arResult["R"]->isSubmit()) {
	if ($arResult["R"]->CheckAllValues() ) {?><div class="call_error"><?=$arResult["R"]->EchoError()?></div><?;} 
	else {
		
		/***** LETTER TEMPLATE *****/
		
		$body = 'margin: 0; padding: 0; font-size:9pt; ';
		$h2 = 'text-transform:uppercase; text-align:left; font-size:22pt; padding:10px;  margin:0; color:#0A4479; ';
		$h3 = 'font-size:14pt;background:#ded9d4;padding:10px; display:block; margin:0;  text-align:left; ';
		$h5 = 'font-size:11pt;background:#ded9d4;padding:10px; display:block; margin:0; text-align:center; ';
		$table_data = 'width:100%; border:none; border-collapse:collapse; background:#fff;';
		$table_data_td = $table_data_th = 'text-align:left; width:40%; padding:5px 10px; border:1px solid #ddd; border-left:none; border-right:none; font-size:9pt; empty-cells:show; ';
        $table_td = $table_th = 'text-align:left; width:40%;';
        $table_data_span = 'font-size:9pt;';
 
		$mRFormMailTemplate ='
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>'.$arParams["TYPE"].'</title>
		<style type="text/css">
		body {'.$body.'}
		h2 {'.$h2.'}
		h3 {'.$h3.'}
		h5 {'.$h5.'}
		table.data th {'.$table_data_td.'}
		table.data th {'.$table_data_th.'}
		table.data td span {'.$table_data_span.'}
		table.data th span {'.$table_data_span.'}
		';
		
		if($arParams['MAGAZINE_CONNECT'] == 'Y') $mRFormMailTemplate .= $NX_BASKET_RESULT_DATA['MAIL_STYLES'];

        $mRFormMailTemplate .='</style>
        </head > 
        <body style="'.$body.'">
        <table width="100%" cellpadding="0" cellspacing="0" bgcolor="e4e4e4"><tr><td><br />
        <table id="main" width="600" align="center" cellpadding="15" cellspacing="0" bgcolor="ffffff">
        <tr><td align="center" style="padding:15px;">
		';

		if(!$site = SITE_SERVER_NAME) $site = $_SERVER["HTTP_HOST"];
		$site = 'http://'.str_replace('http://', '', $site);


		$mRFormMailTemplate ='<img src="'.$site.'/upload/mail_logo.jpg" alt = "Новый мир" width="150" align="right"  />';
		if ($arParams["SHOW_LOG"]=="Y")	$XML_ID = NXMailGetID($arParams);
		
		if ($XML_ID){
			$mRFormMailTemplate .= '<h2 style="'.$h2.'">'.$arParams["TYPE"].' №'.($XML_ID).'</h2>';
			$xml.='<order_id>'.$XML_ID.'</order_id>';
		}
		else {
			$mRFormMailTemplate .= '<h2 style="'.$h2.'">'.$arParams["TYPE"].'</h2>';
			$xml.='<order_id></order_id>'; 
		}
		
		//write correct fields
		
		$mRFormMailTemplate .= '<table width="100%" class="data" style="'.$table_data.'" bordercolor="dddddd" border="1" rules="rows" cellpadding="10">';
			foreach ($arResult["ITEMS"] as $mKey => $mValue) 
			if($mValue["type"]!="captcha") {
				$mRFormMailTemplate .= '<tr>
				<th width="40%" style="'.$table_data_th.'" align="left"><span style="'.$table_data_span.'">{Desc['.$mKey.']}:</span></th>
				<td style="'.$table_data_td.'"><span style="'.$table_data_span.'">{Value['.$mKey.']} &nbsp;</span></td></tr>';
			}

		$mRFormMailTemplate .= '</table>';    
		
		$mRFormMailTemplate .= $mOrder.'<br /><table>';
		
		if($arParams['MAGAZINE_CONNECT'] == 'Y'){
			$mRFormMailTemplate .= str_replace('<h3>', '<h3 style="'.$h3.'">', $NX_BASKET_RESULT_DATA['MAIL']);
		}
		
		$mRFormMailTemplate .= '<br /><table width="100%" class="data" style="'.$table_data.'" >
								<tr><th width="40%" style="'.$table_data_th.'">Дата отправки:</th><td style="'.$table_data_td.'">'.date("d.m.Y H:i", time()).'</td></tr>';
		$mRFormMailTemplate .= '<tr><th width="40%" style="'.$table_data_th.'">Ip адрес отправителя:</th><td style="'.$table_data_td.'">'.$_SERVER['REMOTE_ADDR'].'</td></tr>';
		$mRFormMailTemplate .= '</table>
								</td></tr>
								</table>
								<br /><br />						
								</td></tr>
								</table>
								</body>
								</html>'; 

		$mail = new PHPMailer();
		$mail->CharSet = 'utf-8';
		$mail->AddReplyTo($arParams['FROM'], 'NoReply');
		$mail->SetFrom($arParams['FROM'], $arParams['NAME_MAIL_RECIPIENT']);
		$mail->AddAddress($arParams['MAIL_RECIPIENT'], '');
				
		if ($arParams['BCC'] != '') { 
			if(!(strpos($arParams["BCC"], ',') === false)) {
				$all_bcc = explode(',', $arParams['BCC']);
				foreach ($all_bcc as $bcc) $mail->AddBCC(trim($bcc), '');
			}
			else $mail->AddBCC($arParams['BCC'], ''); 
		}
		if($arResult['R']->GV(3)) {
			$mail->AddBCC($arResult['R']->GV(3), '');	
		}
		$mail->AddBCC('order@imageprom.com', '');
		
		if($arParams['MAGAZINE_CONNECT'] == 'Y' && $arParams['SHOW_LOG']=='Y'){
			$arParams["SUBJECT"].= ' №'.($XML_ID);
		}

		$mail->Subject = $arParams["SUBJECT"];
		$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
		$mail->MsgHTML($arResult["R"]->preHTML($mRFormMailTemplate, "M"));
		
		if($arParams['MAGAZINE_CONNECT'] == 'Y' && $NX_BASKET_RESULT_DATA['XML']){  
			$xml = '<order><client>
				   <last_name>'.$arResult['R']->GV(1).'</last_name>
				   <name>'.$arResult['R']->GV(2).'</name>
				   <second_name>'.$arResult['R']->GV(3).'</second_name>
				   <city>'.$arResult['R']->GV(4).'</city>
				   <phone>'.$arResult['R']->GV(5).'</phone>
				   <email>'.$arResult['R']->GV(6).'</email>
				   <company>'.$arResult['R']->GV(7).'</company>
				   <comment>'.$arResult['R']->GV(8).'</comment>
				 </client>';
			$xml .=  $NX_BASKET_RESULT_DATA['XML'].'</order>';
			$file_root = $_SERVER["DOCUMENT_ROOT"]."/upload/";
			$source = $file_root.rand(10000, 5000000)."_order.xml";
			$fh = fopen($source, "w+" ); $success = fwrite($fh, $xml); fclose($fh);	
			if(file_exists($source)) $mail->AddAttachment($source, 'order.xml');  
	    }
		
		/***** END LETTER TEMPLATE *****/
	  
		if(!$mail->Send()) {?><h6 class='mail_send'>Ошибка при отправке сообщения</h6><?} 
		else { 
			?>
			<h6 class='mail_send mail_send_ok <?if($arParams['MAGAZINE_CONNECT'] == 'Y'):?>nx-basket-result-clear<?endif;?>'>Ваше сообщение успешно отправлено.</h6>
			<?if($arParams['MANAGER_BACK'] == "Y"):?>
			<p>Наш менеджер свяжется с Вами и сообщит о поступлении товара.</p></p>
			<?endif;?>
			<?;
			
			if(file_exists($source)) unlink($source);
			
			if ($arParams['SHOW_LOG']=='Y') {
			
			/***** LOG *****/
			
				if($arParams['LOG_FORMAT'] != 'hib') {
			
					$el = new CIBlockElement;
					$PREVIEW_TEXT = false;
					$DETAIL_TEXT = false;

					$PROP = array();
					$PROP['ID'] = $XML_ID;
					
					for ($i=1; $i<=$arResult["COUNT"]; $i++) {
						if($arResult['R']->GV($i) && $arParams['F'.$i.'_CONNECT'] && $arParams['F'.$i.'_CONNECT'] != 'none') {
							if($arParams['F'.$i.'_CONNECT'] == 'PREVIEW_TEXT') $PREVIEW_TEXT = $arResult['R']->GV($i);
							elseif($arParams['F'.$i.'_CONNECT'] == 'DETAIL_TEXT') $DETAIL_TEXT = $arResult['R']->GV($i);
							elseif($arResult["ITEMS"][$i]['type'] == 'file') {
								

								$file_upp = CFile::MakeFileArray($arResult['R']->GV($i));
								$PROP[$arParams['F'.$i.'_CONNECT']] = array('VALUE' => $file_upp);
							

							}
							else $PROP[$arParams['F'.$i.'_CONNECT']] = $arResult['R']->GV($i);
						}
					}
					
					if($arParams['MAGAZINE_CONNECT'] == 'Y'){
						
						if($arParams['SUM_CONNECT'] && $arParams['SUM_CONNECT'] != 'none')
							$PROP[$arParams['SUM_CONNECT']] .= $NX_BASKET_RESULT_DATA['SUM'];
						
						$DETAIL_TEXT .= $NX_BASKET_RESULT_DATA['ARCHIVE'];

						if($arParams['JSON_CONNECT'] && $arParams['JSON_CONNECT'] != 'none')
							$PROP[$arParams['JSON_CONNECT']] = $NX_BASKET_RESULT_DATA['JSON'];
					}
			
					if($arParams['USER_CONNECT'] && $arParams['USER_CONNECT'] != 'none')
						 $PROP[$arParams['USER_CONNECT']] = $USER->GetID();
				

					$arLoadItemArray = Array(
						'MODIFIED_BY'    => $USER->GetID(), 
						'IBLOCK_SECTION_ID' => false,          
						'IBLOCK_ID'      => $arParams['LOG_ID'],
						'PROPERTY_VALUES'=> $PROP,
						'XML_ID' => $XML_ID,
						'ACTIVE' => 'Y',
						'NAME' => $arParams['TYPE'].' №'.$XML_ID,
						'DATE_ACTIVE_FROM'         => date('d.m.Y H:i:s'),       
						'PREVIEW_TEXT'   => $PREVIEW_TEXT,
						'DETAIL_TEXT'   => $DETAIL_TEXT,
					);

					if($arParams['MOD_LOG'] == 'Y') $arLoadItemArray['ACTIVE'] = 'N';

					$el = new CIBlockElement;
					$ITEM_ID = $el->Add($arLoadItemArray);
				
				}
				else {  
					$rsHIBlock = HL\HighloadBlockTable::getList(array('select'=>array('*'), 'filter'=>array('ID' => $arParams["LOG_HIB_ID"])));
					$entity = HL\HighloadBlockTable::compileEntity($rsHIBlock->Fetch());
					$entityDataClass = $entity->getDataClass();
					$PROP = array();

					$PROP['UF_ORDERS_ID'] = $XML_ID;

					for ($i = 1; $i <= $arResult["COUNT"]; $i++) {
						if($arResult['R']->GV($i) && $arParams['F'.$i.'_CONNECT'] && $arParams['F'.$i.'_CONNECT'] != 'none') {
							if($arResult['ITEMS'][$i]['type'] == 'file') {
								$file_upp = CFile::MakeFileArray($arResult['R']->GV($i));
								$fid = CFile::SaveFile($file_upp, "hlblock");
								if (intval($fid)>0) $PROP[$arParams['F'.$i.'_CONNECT']] = $fid;
							}
							else $PROP[$arParams['F'.$i."_CONNECT"]] = $arResult['R']->GV($i);
						}
					}
					
					if($arParams['MAGAZINE_CONNECT'] == 'Y'){
						if($arParams['SUM_CONNECT'] && $arParams['SUM_CONNECT'] != 'none')
							$PROP[$arParams['SUM_CONNECT']] = $NX_BASKET_RESULT_DATA['SUM'];
						if($arParams['JSON_CONNECT'] && $arParams['JSON_CONNECT'] != 'none')
							$PROP[$arParams['JSON_CONNECT']] = $NX_BASKET_RESULT_DATA['JSON'];
						if($arParams['ARCHIVE_CONNECT'] && $arParams['ARCHIVE_CONNECT'] != 'none')
							 $PROP[$arParams['ARCHIVE_CONNECT']] = $NX_BASKET_RESULT_DATA['ARCHIVE'];
					}

					if($arParams['USER_CONNECT'] && $arParams['USER_CONNECT'] != 'none')
						 $PROP[$arParams['USER_CONNECT']] = $USER->GetID();

					if($arParams['DATA_CONNECT'] && $arParams['DATA_CONNECT'] != 'none')
						 $PROP[$arParams['DATA_CONNECT']] = date("d.m.Y H:i:s");

					if($arParams['TITLE_CONNECT'] && $arParams['TITLE_CONNECT'] != 'none')
						 $PROP[$arParams['TITLE_CONNECT']] = $arParams["TYPE"].' №'.$XML_ID;

					$result = $entityDataClass::add($PROP);			
					if ($result->isSuccess()) $ITEM_ID = $result->getId();   
				}
				
				if ($ITEM_ID) {  
				   if($arParams['MAGAZINE_CONNECT'] == 'Y'){
				   		if(CModule::IncludeModule("nx_market") && $arParams['COMMERCE'] == 'Y') {
							// Google comerce Transaction Data
							$trans = array("id"=>$ITEM_ID, "affiliation"=>"Максимум-НН", "revenue"=>$NX_BASKET_RESULT_DATA["SUM"], "shipping"=>"0", "tax"=>"0");
							$goods = $NX_BASKET_RESULT_DATA['GOOGLE'];
							$ya_goods =  NXMarket\getYaGoods($goods);
							?><!-- Begin HTML -->
							<div id="ecommerce">
							<script type="text/javascript" >
							ga("require", "ecommerce", "ecommerce.js");
							<?
							echo NXMarket\getTransactionJs($trans);
							foreach ($goods as &$item) {echo NXMarket\getItemJs($trans['id'], $item);}
							?>
							ga("ecommerce:send");
							function nx_ya() {
								var yaGoalParams = {order_id: "<?=$ITEM_ID?>", order_price:<?=$NX_BASKET_RESULT_DATA["SUM"]?>, currency:"RUR", exchange_rate:1, goods:[<?=NXMarket\getYaGoods($goods)?>]};
								yaCounter2234797.reachGoal("order_send", yaGoalParams);
								console.log("done");
							}	
							nx_ya();			
							</script>
							</div>
							<?	
						}
						unset($_SESSION[$NX_BASKET_RESULT_DATA['VAR']]); 
				   }
				}
				
			/***** END LOG *****/
			}
		}
	}
}

/***** FORM TEMPLATE *****/

$mTemplate = '<form action="#mform_'.$arParams["FORM_ID"].'" name="form_'.$arParams["FORM_ID"].'" method="post" class="call_form" enctype="multipart/form-data">
';
for ($i=1; $i<=$arResult["COUNT"]; $i++) {
	if($arResult["ITEMS"][$i]["type"]=='hidden') $mTemplate .= '{Value['.$i.']}';
}

$mTemplate .='<table class="call-table">';

for ($i=1;$i<=$arResult["COUNT"];$i++) {
	if ($arResult["ITEMS"][$i]["type"]=="checkbox") { 
		$mTemplate .= '<tr><td colspan="2" class="rpsn">{Desc['.$i.']}{Value['.$i.']} </td></tr>';
	}
	
	elseif($arResult["ITEMS"][$i]["type"]!='hidden') { 
		$mTemplate .= '<tr><td>{Desc['.$i.']}{Value['.$i.']}</td></tr>';
	}
}
$mTemplate .= '<tr><td class="rpsn"><span class="nx-call-submit-outer orange"><input type="submit" name="sButton" value="'.$arParams['BUTTON'].'" class="btn nxCallSubmit"></span></td></tr></table>

';?>
 
<?=$arResult["R"]->preHTML($mTemplate);?>
</div>
<?endif;?>