<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

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
/** @const string SITE */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use PHPMailer\PHPMailer\PHPMailer;

/***** LETTER TEMPLATE *****/
		
$body = 'margin: 0; padding: 0; font-size:9pt; ';
$h2 = 'text-transform:uppercase; text-align:left; font-size:22pt; padding:10px;  margin:0; color:#2c2c2c; ';
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

$mRFormMailTemplate .='</style>
</head > 
<body style="'.$body.'">
<table width="100%" cellpadding="0" cellspacing="0" bgcolor="e4e4e4"><tr><td><br />
<table id="main" width="600" align="center" cellpadding="15" cellspacing="0" bgcolor="ffffff">
<tr><td align="center" style="padding:15px;">
';

if(!$site = SITE_SERVER_NAME) $site = $_SERVER['HTTP_HOST'];
$site = 'https://'.str_replace('http://', '', $site);


$mRFormMailTemplate ='<img src="'.$site.'/upload/mail_logo.png" alt = "Название сайта" width="150" align="right"  />';
if ($arParams["SHOW_LOG"]=="Y")	$XML_ID = NXMailGetID($arParams);

if ($XML_ID){
	$mRFormMailTemplate .= '<h2 style="'.$h2.'">'.$arParams["TYPE"].' №'.($XML_ID).'</h2>';
}
else {
	$mRFormMailTemplate .= '<h2 style="'.$h2.'">'.$arParams["TYPE"].'</h2>';
}

//write correct fields

$mRFormMailTemplate .= '<table width="100%" class="data" style="'.$table_data.'" bordercolor="dddddd" border="1" rules="rows" cellpadding="10">';

	foreach ($arResult["ITEMS"] as $mKey => $mValue) 
	if($mValue["type"]!="captcha") {
        if($arResult['R']->GV($mKey) && $mKey != 11) {
            $mRFormMailTemplate .= '<tr>
		<th width="40%" style="' . $table_data_th . '" align="left"><span style="' . $table_data_span . '">{Desc[' . $mKey . ']}:</span></th>
		<td style="' . $table_data_td . '"><span style="' . $table_data_span . '">{Value[' . $mKey . ']} &nbsp;</span></td></tr>';
        }
        
	}

$mRFormMailTemplate .= '</table>'.
					   '<br /><table>';


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

/***** END TEMPLATE *****/



$arResult['PHP_MAILER'] = new PHPMailer();

$arResult['PHP_MAILER']->setLanguage('ru', $componentPath.'/lib/lang'); //localization
$arResult['PHP_MAILER']->CharSet = 'utf-8';
$arResult['PHP_MAILER']->AddReplyTo($arParams['FROM'], 'NoReply');
$arResult['PHP_MAILER']->SetFrom($arParams['FROM'], $arParams['NAME_MAIL_RECIPIENT']);
$arResult['PHP_MAILER']->AddAddress($arParams['MAIL_RECIPIENT'], '');

//ЕСЛИ НУЖЕН DKIM

//$arResult['PHP_MAILER']->DKIM_domain = SITE_SERVER_NAME;
//$arResult['PHP_MAILER']->DKIM_private = $templateFolder.'/path/to/my/private.key'; // Make sure to protect the key from being publicly accessible!
//$arResult['PHP_MAILER']->DKIM_selector = 'phpmailer';
//$arResult['PHP_MAILER']->DKIM_passphrase = '';
//$arResult['PHP_MAILER']->DKIM_identity = $mail->From;
		
if ($arParams['BCC'] != '') { 
	if(!(strpos($arParams['BCC'], ',') === false)) {
		$all_bcc = explode(',', $arParams['BCC']);
		foreach ($all_bcc as $bcc) $arResult['PHP_MAILER']->AddBCC(trim($bcc), '');
	}
	else $arResult['PHP_MAILER']->AddBCC($arParams['BCC'], ''); 
}
// if($arResult['R']->GV(3)) {
// 	$arResult['PHP_MAILER']->AddBCC($arResult['R']->GV(3), '');	
// }
$arResult['PHP_MAILER']->AddBCC('order@imageprom.com', '');

if($arParams['SHOW_LOG']=='Y'){
	$arParams['SUBJECT'].= ' №'.($XML_ID);
}

$arResult['PHP_MAILER']->Subject = $arParams['SUBJECT'];
$arResult['PHP_MAILER']->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional, comment out and test
$arResult['PHP_MAILER']->MsgHTML($arResult['R']->preHTML($mRFormMailTemplate, 'M'));
