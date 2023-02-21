<?php
/*
 *	mInputs - PHP class for quick and comfortable work with inputs.
 *	
 *
 *	Copyright (c) 2010 Artem Kalashnikov
 *	Contact: maximus@imageprom.com
 *
 *	Integrated with bitrix by Marina Barsukova-Palagina
 *	Contact: necris@imageprom.com
 *	
 *	Requires PHP version 5 or later
 *	Requires Bitrix version 14 later
 *	For Js ability required jQuery ver. 1.4 or higher
 *
 *	Date: 23:42 25/08/2014
 *
 *	Version: 3.19 
 *
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php"); 


if (version_compare(PHP_VERSION, '5.0.0', '<') ) exit("Sorry, mInputs will only run on PHP version 5 or greater!\n");

class nxInput {
/**
 *	Если true HTML шаблон не печатается. Функция preHTML возвращает false.
 */
	private $noPrint = false ;

/**
 *	Если true не печатает HTML шаблон, только после успешной проверки 
 *  данных функцией CheckAllValues(). Свойству $noPrint присваивается
 *  значение true.
 */
	private $AutonoPrintHTML  = true;

/**
 *	Если true поля которые заполненны некорректно будут помечаться
 *  в соответствии с массивом $markedText.
 */
	private $MarkErrorInputs  = true;

/**
 *	Массив со стилями, в соответствии с которым будут помечаться
 *  некорректно заполненные поля.
 */
	private $markedText		  = Array ("color"=>"red", "border-bottom"=>"1px solid red");


/**
 *	Массив со стилями, в соответствии с которым будет отображаться
 *  текст JsText.
 */
	private $JsTextCssArray   = Array("color" => "rgb(145,145,145)", "font-style" => "italic", "font-family"=>"Verdana");

/**
 *	Если true автоматически в конец шаблона будет добавлено 
 *  скрытое поле для проверки отправлена ли форма с именем $AddHiddenName.
 */
	private $AddHiddenValue   = true;

/**
 *	Имя скрытого поля которое автоматически добавляется к шаблону.
 */
	private $AddHiddenName    = "mInputsHidden";

/**
 *	Значение которое в полях select и mselect которое не делает поле заполненным =)
 */
	public $falseValue        = "___";

/**
 *	Массив с шаблонами ошибок
 **/
	public $erArray = Array(

		"1"	 =>	 "Поле <b>{Description}</b> не найдено",
		"2"	 =>	 "Поле <b>{Description}</b> не заполнено",
		"3"	 =>	 "Поле <b>{Description}</b> заполнено неверно",
		"4"  =>  "Неизвестное значение в поле <b>{Description}</b>",
		"5"  =>  "В поле <b>{Description}</b> необходимо выбрать значение",
		"6"	 =>  "Поле <b>{Description}</b> имеет недопустимые символы: <u><b>{ErrorDetail}</b></u>"
	);


	public $jQueryUrl = "http://code.jquery.com/jquery-1.7.2.js";
	public $FormID = "";


	private $markedInputs = Array();
	private $eFunc;
	private $Error;
	private $MainArray;
	private $Objs;
	private $mTemplateParam;
	private $jsTextArray;
	private $BXcaptcha;




	/**
	 *	Function __construct.
	 */
	function __construct($iArray, $id = false) {
	     
		$this->MainArray = $iArray;
		foreach ($iArray as $mKey => $mValue) $this->Objs[$mKey] = new NxSimpleIm($mValue, $mKey);
		
		$this->BXcaptcha = new CCaptcha();
	    $captchaPass = COption::GetOptionString("main", "captcha_password", "");
	    if(strlen($captchaPass) <= 0)
	    {
		    $captchaPass = randString(10);
		    COption::SetOptionString("main", "captcha_password", $captchaPass);
		}

		if($id) $this->AddHiddenName.= "_".$id;
	}

	public function SetErrorColorCheme($input = array()){
		if(count($input) > 0 && is_array($input)) {
			$this->markedText = $input;
		}
	}


/**
 *	Добавляет пользовательскую функцию для проверки корректности ввода данных
 */

	private function ErrorTFunction($V) { return "{".$V."}"; }

	public function BuildErrorTemplate($Errorname, $Template, $Obj, $AddArray = Array()) {
		if (!$Template) return false; 

		if (gettype($Template)=="integer") {
				$Template = $this->erArray[$Template];
		}

		$ObjArray = array_merge(get_object_vars($Obj), $AddArray);
		$ObjKeys   = array_map(Array($this, "ErrorTFunction"), array_keys($ObjArray));
		$ObjValues = array_values($ObjArray);

		return str_replace($ObjKeys, $ObjValues, $Template);
	}



/**
 *	Добавляет пользовательскую функцию для проверки корректности ввода данных
 */
public function AddEFunc($eFunc) {
	$this->eFunc[] = $eFunc;
}


/**
 *	Проверяет была ли отправлена форма с полями
 */
public function isSubmit() {

	if ($this->AddHiddenValue) {
		if (isset($_POST[$this->AddHiddenName])) return true;
		return false;
	}

	foreach ($this->Objs as $oKey => $oV) {
		if (eval("return isset(\$_".strtoupper($oV->Method)."['".$oV->Name."']);")) return true;
	}

return false;
}


/**
 *	Помечает некорректно заполненное поле, добавляя к его стилям значения
 *  указанные в массиве $markedText
 */
	private function markEInput($Ob) {

		if (!count($this->markedText)) return false;
		preg_match("/style=([\"']).*[\"']/i", $Ob->inText, $Mathes);
		$NewMatch = $Mathes[0];

		if (strlen(trim($Mathes[0]))) {
			foreach ($this->markedText as $mKey => $mValue) {
				$NewMatch = (preg_match("/".$mKey.".+;/i", $NewMatch, $preMchs))?str_replace($preMchs[0], $mKey.": ".$mValue.";", $NewMatch):preg_replace("/style=([\"'])/i", "style=".$Mathes[1].$mKey.": ".$mValue."; ", $NewMatch);
			}
			$Ob->inText = str_replace($Mathes[0], $NewMatch, $Ob->inText);
		} else {

			foreach ($this->markedText as $mKey => $mValue) $NewMatch .= $mKey.": ".$mValue."; ";
			$Ob->inText .= " style=\"".$NewMatch."\"";
		}
	}

/**
 *	Возвращает массив значений полей для дальнейшей работы в Cms Bitrix
 */
	public function BuildBxArray() {
		foreach ($this->Objs as $ObKey => $ObValue) {
			$BxArray[$ObValue->Bxname] = $this->GV($ObKey);
		}
		return $BxArray;
	}


/**
 *	Проверяет корректность воода данных в поля
 */
	public function CheckAllValues() {
		
		foreach ($this->Objs as $ObKey => $ObValue) {
		
			if (($ErrorReturn = $this->CheckValues($ObValue)) == true) {
				$this->markedInputs[] = $ObKey;
				$this->Error[] = $ErrorReturn;
			}
		}

		if (count($this->Error) == 0 && count($this->eFunc)) {
			foreach ($this->eFunc as $mKey => $mValue) {
				$ErrorReturn = $mValue($this);
				if ($ErrorReturn) $this->Error[] = $ErrorReturn;
			}
		}
		$eBoo = (bool)count($this->Error);
		if (!$eBoo && $this->AutonoPrintHTML) $this->noPrint = true;
		
		return $eBoo;
	}


/**
 *	Возвращает текст ошибки после проверки функцией CheckAllValues
 */
	public function EchoError() {

		if (count($this->Error)) {
			function ChArrErr(&$V, $K) { $V = "<li>".$V."</li>"; }
			array_walk($this->Error, "ChArrErr");
			$R = "<b>Ошибка!</b><ul>".implode("", $this->Error)."</ul>";
			$this->Error = Array();
			return $R;
		}

		return false;
	}

/**
 *	Устанавливает значение свойства $noPrint как true. После этого HTML шаблон
 *  печататься не будет.
 */
	public function noPrintHTML() {
		$this->noPrint = true;
	}


/**
 *	Возвращает описание заданного поля.
 */
	public function GD($Key, $Star = true) {

		$Ob = $this->Objs[$Key];
		$Desc = (isset($Ob->Description))?$Ob->Description:"Неизвестно";
		if ($Ob->Oblig==1) $Desc .= ($Star==true)?"<sup style='color: #F25100'>*</sup>":"";
		return $Desc;

	}

/**
*	Формирование и транслитерация имени загружаемого файла для полей типа file
*/

	private function getUploadFileName($Ob) {
		$name = explode('.', $_FILES[$Ob->Name]['name']);
	    $cnt = count($name)-1;
	    $ext = $name[$cnt];
	    unset ($name[$cnt]);
	    $name = implode('_', $name).'_'.date('d_m_Y__H_i_s'); 
	    $url = $Ob->Config['UploadUrl'];
	    $name = $url.Cutil::translit($name, "ru").'.'.$ext;
	    return $name;
	}


/**
 *	Возвращает значение заданного поля.
 */
public function GV($Key) {
$Ob = $this->Objs[$Key];

	switch ($Ob->Type) {
	case "select":
	case "radio":
        if(strtoupper($Ob->Method)=="POST") return $Ob->MValues[$_POST[$Ob->Name]]; else return $Ob->MValues[$_GET[$Ob->Name]];
	    //return $src[$Ob->Name];	
		//return eval("return \$Ob->MValues[\$_".strtoupper($Ob->Method)."['".$Ob->Name."']];");
	break;
	
	case "mselect":
	case "checkboxgroup": { 

			if(strtoupper($Ob->Method)=="POST") $src=&$_POST; else $src=&$_GET;

			$result = '';
			foreach ($Ob->MValues as $cnt=>$item) {
				if(in_array($cnt, $src[$Ob->Name])) $result.=$item.' | ';
			}

			$result=rtrim($result, ' | ');
			return $result;
		}
	break;

	case "file":
		if ($_FILES[$Ob->Name]['name'])
		    return  $Ob->Config['Host'].$this->getUploadFileName($Ob);
		else 
			return false;
	
	break;

	default:
		return eval("return \$_".strtoupper($Ob->Method)."['".$Ob->Name."'];");
	break;
	}

}


/**
 *	Добавляет скрытое поле в конец шаблона для проверки на факт отправки формы.
 */
private function AddHiddenInput($HTML) {


$ReturnInput = "<input type='hidden' name='".$this->AddHiddenName."' value='".md5(uniqid())."' />";
return (preg_match("/<\/form>/i", $HTML))?str_ireplace("</form>", $ReturnInput."</form>", $HTML):$HTML.$ReturnInput;
}


/**
 *	Функция которая используется в preg_replace_callback функции preHTML() 
 *  для замены элементов шаблона
 */
private function preReplace($mRep) {

$mFunc = $mRep[1];
$mKey = $mRep[2];

	switch($mFunc) {

		case "Desc";
			switch ($this->mTemplateParam['mParam']) {
				case "W";
				case "M";
					return $this->GD($mKey);
				break;

				default:
					return;
				break;
			}
		break;

		case "Value";
			switch ($this->mTemplateParam['mParam']) {
				case "W";
					if ($this->MarkErrorInputs && array_search($mKey, $this->markedInputs)!==false) $this->markEInput($this->Objs[$mKey]);
					return $this->BuildHTML($this->Objs[$mKey]);
				break;

				case "M";
					return $this->GV($mKey);
				break;

				default:
					return;
				break;
			}
		break;

		default:
			return $mRep[0];
		break;

	}

	return true;
}




private function preSearchTemplates($Mathes) { 

$Mathes[1] = strtoupper($Mathes[1]);

switch ($this->mTemplateParam['mParam']) {

	case "W":

		if ($Mathes[1]=="W") return $Mathes[2];

		return "";
	break;

	case "M":

		if ($Mathes[1]=="M") return $Mathes[2];

		return "";
	break;

	default:
		return $Mathes[0];
	break;

}

return $Mathes[0];
}

/** 
 *	Возвращяет HTML шаблон с замененными HTML элементами (описания и значения полей).
 */
public function preHTML($Template, $mParam = 'W', $pArray = Array()) {
	$JsTextHTML = "";
	$mParam = strtoupper($mParam);

	if ($this->noPrint&&$mParam=='W')   return ""; 
	if (empty($Template)) return "Ошибка: Template пуст."; 

	$this->mTemplateParam = Array("mParam"=>$mParam);

	$Template = preg_replace_callback("/<minp\-(w|m)>(.+)<\/minp\-\\1>/isU", Array($this, 'preSearchTemplates'), $Template);

	$RHTML = preg_replace_callback("/{([a-zA-Z0-9]+)\[([a-zA-Z0-9]+)\]}/U", Array($this, 'preReplace') , $Template);


# JsText init
	if (count($this->jsTextArray)) {

		foreach ($this->jsTextArray as $jsKey => $jsValue) $jsPromArray[] = "'".$this->Objs[$jsValue]->Name."':'".$this->Objs[$jsValue]->jPretext."'";
		foreach ($this->JsTextCssArray as $jsCssKey => $jsCssValue) $JsTextCssArray[] = "'".$jsCssKey."':'".$jsCssValue."'";


		$JsTextHTML .= "<script>
		if (window.jQuery) {

		mInputsJsObj = {".implode(",", $jsPromArray)."};
		mInputsObj = {};

		for (var ObjKey in mInputsJsObj) {
	
			eval(\"$('#\"+ObjKey+\"').focusout(function(){if ($('#\"+ObjKey+\"').val()=='') $('#\"+ObjKey+\"').val(mInputsJsObj['\"+ObjKey+\"']).css({".implode(",", $JsTextCssArray)."});}).focus(function(){if ($('#\"+ObjKey+\"').val()==mInputsJsObj['\"+ObjKey+\"']) $('#\"+ObjKey+\"').val('').css(mInputsObj['\"+ObjKey+\"'].css);})\");

			mInputsObj[ObjKey] = {}; mInputsObj[ObjKey].css = {};";
			
			foreach ($this->JsTextCssArray as $jsCssKey => $jsCssValue) $JsTextHTML .= "\n\t\t\tmInputsObj[ObjKey].css['".$jsCssKey."'] = $('#'+ObjKey).css('".$jsCssKey."');";
		
			$JsTextHTML .= "\n\n\t\t\t if ($('#'+ObjKey).val()==''&&mInputsJsObj[ObjKey]!=undefined) $('#'+ObjKey).val(mInputsJsObj[ObjKey]).css({".implode(",", $JsTextCssArray)."});

		}
		}
		</script>";

	}



	if ($JsTextHTML) $RHTML .= "\n<script>if (!window.jQuery) {document.write(unescape('<script src=\"".$this->jQueryUrl."\">%3C/script%3E'))}</script>\n".$JsTextHTML;

	if ($this->AddHiddenValue) $RHTML = $this->AddHiddenInput($RHTML);

	return $RHTML;

}






/**
 *  Функция которая используется для создания HTML кода заданного поля
 *  с учетом его параметров.
 */
private function BuildHTML($Obj) {


# jsText insert
if ($Obj->jPretext and ($Obj->Type=="text" or $Obj->Type=="textarea")) {
	$this->jsTextArray[] = $Obj->ObjKey;
}

switch ($Obj->Type) {

	case "captcha":
	    
		$captchaPass = randString(10);
		$this->BXcaptcha->SetCodeCrypt($captchaPass);
		$ThisValue = $this->BXcaptcha->GetCodeCrypt($captchaPass);

		if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
		if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
		$Obj->inText .= $Obj->GetHtml5Pattren('captcha');
			
		Return "<input type='hidden' name='".$Obj->Name."[sid]' id='".$Obj->Name."[sid]' value='".$ThisValue."'  />
				<span class='cptch'><img src='/bitrix/tools/captcha.php?captcha_code=".$ThisValue."'  alt='CAPTCHA' /><span>
				<input type='text' ".$Obj->inText." name='".$Obj->Name."[word]' id='".$Obj->Name."[word]' value='' maxlength='50' />";
		

	break;

	case "file":

		if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
			if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();

		$Obj->inText .= " accept='".$Obj->GetMimeTypes()."' ";
		
		Return "<input type='file' name='".$Obj->Name."' ".$Obj->inText." id='".$Obj->Name."' />";

	break;

	case "hidden":

		$ThisValue = ($Obj->ThisValue && trim($Obj->ThisValue) != '') ? trim($Obj->ThisValue):$Obj->Default;
		Return "<input type='hidden' name='".$Obj->Name."' ".$Obj->inText." id='".$Obj->Name."' value='".trim(htmlspecialchars($ThisValue, ENT_QUOTES))."' />";

	break;


	case "text":

		$ThisValue = ($Obj->ThisValue)?trim($Obj->ThisValue):$Obj->Default;
		$ThisValue = stripslashes($ThisValue);

		if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
		if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();


		if($Obj->RegType == 'phone')     {$type = "tel";    $Obj->inText .= $Obj->GetHtml5Pattren('phone'); }
		elseif ($Obj->RegType == 'mail') {$type = "email";  $Obj->inText .= $Obj->GetHtml5Pattren('mail');}
		else {$type = "text"; $Obj->inText .= $Obj->GetHtml5Pattren('text');}

		Return "<input type='".$type."' name='".$Obj->Name."' id='".$Obj->Name."' ".$Obj->inText." value='".trim(htmlspecialchars($ThisValue, ENT_QUOTES))."' />";

	break;

	case "data":
	    global $APPLICATION;
		$ThisValue = ($Obj->ThisValue) ? trim($Obj->ThisValue):$Obj->Default;
		$ThisValue = stripslashes($ThisValue);
		CJSCore::Init(array('popup', 'date'));

		if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
		if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
		$Obj->inText .= $Obj->GetHtml5Pattren('data');
		 
		Return "<span class='dttm'><input type='text' name='".$Obj->Name."' id='".$Obj->Name."' ".$Obj->inText." value='".trim(htmlspecialchars($ThisValue, ENT_QUOTES))."' />
				<img src='/bitrix/js/main/core/images/calendar-icon.gif' alt='Календарь' class='calendar-icon' 
				onclick='BX.calendar({node:this, field:\"".htmlspecialcharsbx(CUtil::JSEscape($Obj->Name))."\", form:\"\", bTime:\"false\", currentTime:\"false\", bHideTime:\"false\"});' 
				onmouseover='BX.addClass(this, \"calendar-icon-hover\");' 
				onmouseout='BX.removeClass(this, \"calendar-icon-hover\");' 
				border='0' /></span>";

	break;

	case "checkbox":
	    if(isset($Obj->ThisValue)) $Obj->ThisValue = false;
		if((!$this->isSubmit() && $Obj->Default == 'Y') || ($Obj->ThisValue == $Obj->MValues) ) {
			$CheckValueSelected = 'checked="checked"';
		}
		else $CheckValueSelected = '';

		if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
		
		Return "<input type='checkbox' ".$CheckValueSelected." ".$Obj->inText." name='".$Obj->Name."' id='".$Obj->Name."' value='".$Obj->MValues."' />";
	break;


	case "password":
		if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
		if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
		$Obj->inText .= $Obj->GetHtml5Pattren('text');
		Return "<input type='password' name='".$Obj->Name."' id='".$Obj->Name."' ".$Obj->inText." value='' />";
	break;


case "select":

	$RSelectedValue = ($Obj->ThisValue&&trim($Obj->ThisValue)!='')?trim($Obj->ThisValue):$Obj->Default;

	if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
	
	$ReturnHTML = "<select ".$Obj->inText." name='".$Obj->Name."' id='".$Obj->Name."'>";
	if($Obj->Placeholder) $ReturnHTML .= "<option ".$RSelected." value='placeholder' default='default'>".$Obj->Placeholder."</option>";

	foreach ($Obj->MValues as $VKey => $VValue) {
		if($VKey != 'placeholder') {
			if($RSelectedValue === $VKey) $RSelected = "selected"; else $RSelected = "";
			$ReturnHTML .= "<option ".$RSelected." value='".$VKey."'>".$VValue."</option>";
		}
	}
	$ReturnHTML .= "</select>";
	return $ReturnHTML;

break;


case "mselect":

	if ($Obj->ThisValue&&is_array($Obj->ThisValue)) {
		if (array_search($this->falseValue, $Obj->ThisValue)!==false&&count($Obj->ThisValue)>1) unset($Obj->ThisValue[array_search($this->falseValue, $Obj->ThisValue)]);
		$RSelectedArray = $Obj->ThisValue;
	} else {
		$RSelectedArray = $Obj->Default;
	}

	if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();

	$ReturnHTML = "<select ".$Obj->inText." multiple name='".$Obj->Name."[]' id='".$Obj->Name."'>";
	if($Obj->Placeholder) $ReturnHTML .= "<option ".$RSelected." value='placeholder' daefault='default'>".$Obj->Placeholder."</option>";

	foreach ($Obj->MValues as $_MKey => $_MVal) {
		$RSelected = (is_array($RSelectedArray)&&array_search($_MKey, $RSelectedArray)!==false)?"selected":"";
		$ReturnHTML .= "<option ".$RSelected." value='".$_MKey."'>".$_MVal;
	}
	$ReturnHTML .= "</select>";

	return $ReturnHTML;

break;



case "textarea":
	$TextareaCurrent = ($Obj->ThisValue&&trim($Obj->ThisValue)!='')?trim($Obj->ThisValue):$Obj->Default;
	$TextareaCurrent = stripslashes($TextareaCurrent);

	if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
	if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
	$Obj->inText .= $Obj->GetHtml5Pattren('text');

	return "<textarea name='".$Obj->Name."' id='".$Obj->Name."' ".$Obj->inText.">".htmlspecialchars($TextareaCurrent, ENT_QUOTES)."</textarea>";

break;


case "radio":

	if(!$Obj->Default) $Obj->Default = 0;
    $RSelectedValue = ($Obj->ThisValue && trim($Obj->ThisValue)!='')?trim($Obj->ThisValue): $Obj->Default;
    $ReturnHTML .= "<div class ='fldst'>";
    foreach ($Obj->MValues as $VKey => $VValue) {
		$RSelected = ($RSelectedValue==$VKey)?'checked="checked"':'';	
		$ReturnHTML .= "<label><input type='radio' name='".$Obj->Name."' ".$Obj->inText." value='".$VKey."' ".$RSelected." />&nbsp;".$VValue."</label>";
	}
	$ReturnHTML .= "</div>";
    return $ReturnHTML;
break;

case "checkboxgroup":

	if ($Obj->ThisValue && is_array($Obj->ThisValue)) {
		if (array_search($this->falseValue, $Obj->ThisValue)!==false&&count($Obj->ThisValue)>1) unset($Obj->ThisValue[array_search($this->falseValue, $Obj->ThisValue)]);
		$RSelectedArray = $Obj->ThisValue;
	} 
	else {
		$RSelectedArray = $Obj->Default;
	}

	$ReturnHTML .= "<div class ='fldst'>";
	foreach ($Obj->MValues as $_MKey => $_MVal) {
		$RSelected = (is_array($RSelectedArray) && array_search($_MKey, $RSelectedArray) !== false)?'checked="checked"':'';	
		$ReturnHTML .= "<label><input type='checkbox' name='".$Obj->Name."[]' ".$Obj->inText." value='".$_MKey."' ".$RSelected." />&nbsp;".$_MVal."</label>";
	}
	$ReturnHTML .= "</div>";
    return $ReturnHTML;



default:

	return false;

break;

}


} # End BuildHTML




/**
 *  Функция которая используется для проверки значения заданного поля.
 *  Используется в цикле CheckAllValues()
 */
	private function CheckValues($Obj) {

		# Check JsText 
		if ($Obj->jPretext and ($Obj->Type=="text" or $Obj->Type=="textarea") and $Obj->jPretext==$Obj->ThisValue) $Obj->ThisValue = false;

		switch ($Obj->Type) {

		case "captcha":

			if (!$Obj->ThisValue) return ($Obj->Oblig)?"Необходимо ввести <b>".$Obj->Description."</b>":false;
			$cptcha = new CCaptcha(); 
			if (!$GLOBALS["APPLICATION"]->CaptchaCheckCode($Obj->ThisValue["word"],$Obj->ThisValue["sid"])) return "<b>".$Obj->Description."</b> введен неверно";
			return false;

		break;

		case "file":
		  
		    if (!$_FILES[$Obj->Name]['name']) return false;
		    $FileUrl = $_SERVER['DOCUMENT_ROOT'].$this->getUploadFileName($Obj);
		 	
			if (is_uploaded_file($_FILES[$Obj->Name]['tmp_name']) == false) { 
				return "Ошибка при загрузке файла <b>".$_FILES[$Obj->Name]['name']."</b>.";
			}
			elseif (isset($Obj->Config['UploadMaxSize']) && $Obj->Config['UploadMaxSize']>0 && ($_FILES[$Obj->Name]['size']>$Obj->Config['UploadMaxSize'])) {
				return "Файл <b>".$_FILES[$Obj->Name]['name']."</b> превышает допустимый размер.";
			}
		    elseif (!in_array(pathinfo($_FILES[$Obj->Name]['name'], PATHINFO_EXTENSION), $Obj->Config['FileType'])) {
		    	return "Недопустимый тип файла <b>".$_FILES[$Obj->Name]['name']."</b>";
		    }
			elseif (!copy($_FILES[$Obj->Name]['tmp_name'], $FileUrl)){
		        return "Ошибка при сохранении файла <b>".$_FILES[$Obj->Name]['name']."</b>";
		    }
				
		   	return false;

		break;

		case "text": 
		case "textarea":
		case "password":

			if (trim($Obj->ThisValue) == "" || $Obj->ThisValue === false) 
				return ($Obj->Oblig)?$this->BuildErrorTemplate( "2" , 2 , $Obj):false;
				
			if ($Obj->Regular && $Obj->Regular != '') {
			preg_match_all($Obj->Regular, stripslashes($Obj->ThisValue), $Mathes);
				if (count($Mathes[0])==$Obj->RegularRight) {
					return false;
				}
				else {
					if (!$Obj->RegularRight&&$Obj->RegularDetail) {	
						return $this->BuildErrorTemplate( "4" , 6, $Obj, Array("ErrorDetail"=>stripslashes(htmlspecialchars(implode("", array_unique($Mathes[0])), ENT_QUOTES))));
					}
				return $this->BuildErrorTemplate( "3" , 3 , $Obj);
				}
			}

		return false;
		break;

		case "select":
			
			if ($Obj->ThisValue != $this->falseValue && !isset($Obj->MValues[$Obj->ThisValue])) return $this->BuildErrorTemplate( "1" , 4 , $Obj);
			if ($Obj->Oblig && ($Obj->ThisValue == $this->falseValue || $Obj->ThisValue == 'placeholder')) return $this->BuildErrorTemplate( "2" , 5 , $Obj);
			return false;
		break;


		case "mselect":

			if (($Obj->ThisValue === false || !is_array($Obj->ThisValue)) && $Obj->Oblig) return $this->BuildErrorTemplate( "1" , 5 , $Obj);

			if ($Obj->Oblig) {

				foreach ($Obj->ThisValue as $_AKey => $_AValue) 
					if (!isset($Obj->MValues[$_AValue]) && $_AValue != 'placeholder') return $this->BuildErrorTemplate( "2" , 4 , $Obj);
				
				$SearchDefValue = array_search($this->falseValue, $Obj->ThisValue);
				if ($SearchDefValue !== false) unset($Obj->ThisValue[$SearchDefValue]);

				$SearchDefValue = array_search('placeholder', $Obj->ThisValue);
				if ($SearchDefValue !== false) unset($Obj->ThisValue[$SearchDefValue]);

				if (!count($Obj->ThisValue) > 0 && $Obj->Oblig) { unset($Obj->ThisValue[array_search($this->falseValue, $Obj->ThisValue)]); return $this->BuildErrorTemplate( "2" , 5 , $Obj); }

			}

		return false;
		break;

		case "checkboxgroup":

			if (($Obj->ThisValue===false || !is_array($Obj->ThisValue))&&$Obj->Oblig) return $this->BuildErrorTemplate( "1" , 5 , $Obj);

			if ($Obj->Oblig) {

				foreach ($Obj->ThisValue as $_AKey => $_AValue) if (!isset($Obj->MValues[$_AValue])) return $this->BuildErrorTemplate( "2" , 4 , $Obj);
				
				$SearchDefValue = array_search($this->falseValue, $Obj->ThisValue);
				if ($SearchDefValue!==false) unset($Obj->ThisValue[$SearchDefValue]);

				if (!count($Obj->ThisValue)>0&&$Obj->Oblig) { unset($Obj->ThisValue[array_search($this->falseValue, $Obj->ThisValue)]); return $this->BuildErrorTemplate( "3" , 2 , $Obj); }

			}

		return false;
		break;



		case "checkbox":

			if ($Obj->Oblig&&$this->isSubmit()&&$Obj->ThisValue===false) return $this->BuildErrorTemplate( "1" , 2 , $Obj);

			return false;

		break;


		default:

			return false;

		break;

		}
	}
}




class NxSimpleIm {

	private $RegTps = Array( 
		"text"=>"/[^a-zA-ZА-Яа-яёЁ0-9\.,\-\(\)\[\] ]/u", 
		"mail"=>"/^[_\.0-9a-zA-Z\-]+@[0-9a-zA-Z\-\.]+\.[a-zA-Z0-9\-]+$/u", 
		"textarea"=>"/[^a-zA-Zа-яА-ЯёЁ0-9@\*!?:\.,\-\)\(\s]/u", 
		"phone"=>"/[^-+\(\)0-9 ]/u"
		);

	private $html5Patterns = Array( 
		"text" => "[a-zA-ZА-Яа-яёЁ0-9\.,\-\(\)\[\] ]+", 
		"mail" => "[_\.0-9a-zA-Z\-]+@[0-9a-zA-Z\-\.]+\.[a-zA-Z0-9\-]+", 
		"textarea" => "[a-zA-Zа-яА-ЯёЁ0-9@\*!?:\.,\-\)\(\s]+", 
		"phone" => '[0-9\-\+\(\)\[\] ]+',
		"data" => '[ .:0-9]+',
		"captcha" => '[a-zA-Z0-9]+',
	);

	private $html5Titles = Array( 
		"text" => "Возможно вы использовали опасные символы: например кавычки", 
		"mail" => "Проверьте, корректно ли ввыели e-mail", 
		"textarea" => "Возможно вы использовали опасные символы, например кавычки", 
		"phone" => 'Номер телефона может содержать числа, ( ) и +',
		"data" => 'Дата может содержать числа, разделитель даты - точка, разделитель времени - двоеточие',
		"captcha" => 'Проверочный код может содержать числа и символы латинского алфавита',
	);

	function GetHtml5Pattren($type) {
		$pattern = ''; 
		if($this->html5Patterns[$type]) {
			$pattern .= ' pattern="'.$this->html5Patterns[$type].'"';
			if($this->html5Patterns[$type]) 
				$pattern .= ' title="'.$this->html5Titles[$type].'" ';
		}
		return $pattern;
	}


	static function GetHtml5Required() {
		return 'required="required"';
	}

	function GetPlaceholder() {
	 return ' placeholder="'.$this->Placeholder.'" ';
	} 

	function __construct($eArray, $eKey) {

		$this->Type				= $eArray["type"];
		$this->Name				= $eArray["name"];
		$this->ObjKey			= $eKey;
		$this->inText			= isset($eArray["intext"])?$eArray["intext"]:"";
		$this->Placeholder		= isset($eArray["placeholder"])?$eArray["placeholder"]:"";
		$this->MValues			= isset($eArray["values"])?$eArray["values"]:"";
		
		if($this->Type	== 'select' || $this->Type	== 'multiselect') 
		$this->MValues['placeholder'] = '';
			
		$this->Regular			= isset($eArray["regular"])?((array_key_exists($eArray["regular"], $this->RegTps))?$this->RegTps[$eArray["regular"]]:$eArray["regular"]):"";
		$this->RegType			= isset($eArray["regular"])?((array_key_exists($eArray["regular"], $this->RegTps))?$eArray["regular"]:$eArray["regular"]):"";
		$this->Default			= isset($eArray["default"])?$eArray["default"]:"";
		$this->Description		= isset($eArray["description"])?$eArray["description"]:"Неизвестное поле";
		$this->Method			= isset($eArray["method"])?trim($eArray["method"]):"post";
		$this->RegularDetail	= isset($eArray["rDetail"])?$eArray["rDetail"]:true;
		$this->RegularRight		= isset($eArray["rRight"])?$eArray["rRight"]:false;
		$this->Oblig			= isset($eArray["oblig"])?$eArray["oblig"]:0;
		$this->eRrorText	    = isset($eArray["eText"])?trim($eArray["eText"]):"";
		$this->Config			= isset($eArray["config"])?$eArray["config"]:Array();
		$this->Bxname			= isset($eArray["bxname"])?$eArray["bxname"]:"";
		$this->Url				= isset($eArray["url"])?$eArray["url"]:"";
		$this->jPretext			= isset($eArray["jpretext"])?$eArray["jpretext"]:false;

		switch ($this->Method) {
			case "get":
			$this->_Arr = &$_GET;
			break;

			default:
			$this->_Arr = &$_POST;
			break;
		}
		$this->ThisValue = (isset($this->_Arr[$this->Name]))?$this->_Arr[$this->Name]:false;
	}

	public static function GetFileFormats() {
		$arFormats = array('jpg', 'png', 'gif', 'bmp', 'jpeg', 'pdf', 'rtf', 'doc', 'docx', 'odt', 'txt', 'ppt', 'pptx', 'xls', 'xlsx', 'cdr', 'ai', 'psd');
		return $arFormats;
	}

	public function GetMimeTypes() {
		$mime = array(
			'jpg'  => 'image/jpeg, image/jpg, image/jp_, application/jpg, application/x-jpg, image/pjpeg, image/pipeg, image/vnd.swiftview-jpeg, image/x-xbitmap', 
			'png'  => 'image/png, application/png, application/x-png',
			'gif'  => 'image/gif, image/x-xbitmap, image/gi_', 
			'bmp'  => 'image/bmp, image/x-bmp, image/x-bitmap, image/x-xbitmap, image/x-win-bitmap, image/x-windows-bmp, image/ms-bmp, image/x-ms-bmp, application/bmp, application/x-bmp, application/x-win-bitmap ', 
			'pdf'  => 'application/pdf, application/x-pdf, application/acrobat, applications/vnd.pdf, text/pdf, text/x-pdf', 
			'rtf'  => 'application/rtf, application/x-rtf, text/rtf, text/richtext, application/msword, application/doc, application/x-soffice', 
			'doc'  => 'application/msword, application/doc, appl/text, application/vnd.msword, application/vnd.ms-word, application/winword, application/word, application/x-msw6, application/x-msword', 
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
			'odt'  => 'application/vnd.oasis.opendocument.text	', 
			'txt'  => 'text/plain, application/txt, browser/internal, text/anytext, widetext/plain, widetext/paragraph', 
			'ppt'  => 'pplication/vnd.ms-powerpoint, application/mspowerpoint, application/ms-powerpoint, application/mspowerpnt, application/vnd-mspowerpoint', 
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 
			'xls'  => 'application/vnd.ms-excel, application/msexcel, application/x-msexcel, application/x-ms-excel, application/vnd.ms-excel, application/x-excel, application/x-dos_ms_excel, application/xls',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xml'  => 'text/xml, application/xml, application/x-xml', 
			'cdr' => 'application/cdr, application/coreldraw, application/x-cdr, application/x-coreldraw, image/cdr, image/x-cdr, zz-application/zz-winassoc-cdr', 
			'ai' => 'application/postscript', 
			'ps' => 'application/postscript', 
			'psd' => 'image/photoshop, image/x-photoshop, image/psd, application/photoshop, application/psd, zz-application/zz-winassoc-psd',
		);
		
		//print_r($this);
		$result = array();
		foreach ($this->Config['FileType'] as $key => $value) {
			$result[] = $mime [$value];
		}

		$result = array_unique($result);
		$result = implode(', ', $result);
		return $result;

	}

	public static function GetFileFormat($format) {
		$formats = self::GetFileFormats();
		if(is_array($format)) {
			foreach($format as $cnt => $val) {
				if($val >= 0 && $val < 15) $result[] = $formats[$val];
			}
		}
		else $result = array($formats[inval($format)]);
		return $result; 
	} 

	public static function GetBaseTypeConf() { 
		$types = array(
          'text' => Array('TYPE' => 'text', 'REG' => 'text' ),
          'phone' => Array('TYPE' => 'text', 'REG' => 'phone' ),
		  'mail' => Array('TYPE' => 'text', 'REG' => 'mail' ),
		  'file' => Array('TYPE' => 'file', 'REG' => '' ),
          'textarea' => Array('TYPE' => 'textarea', 'REG' => 'textarea' ),
		  'password' => Array('TYPE' => 'password', 'REG' => '' ),
		  'checkbox' => Array('TYPE' => 'checkbox', 'REG' => '' ),
		  'checkboxgroup' => Array('TYPE' => 'checkboxgroup', 'REG' => '' ),
		  'select'   => Array('TYPE' => 'select', 'REG' => '' ),
		  'multiselect'  => Array('TYPE' => 'mselect', 'REG' => '' ),
		  'radio'   => Array('TYPE' => 'radio', 'REG' => '' ),
		  'captcha' => Array('TYPE' => 'captcha', 'REG' => '' ),
		  'data' => Array('TYPE' => 'data', 'REG' => 'text' ),
		  'hidden' => Array('TYPE' => 'hidden', 'REG' => '' ),
		);
		return $types;
	}


	public static function GetBaseTypes() {
		$result = array();
		$types = self::GetBaseTypeConf();
		foreach ($types as $key => $value) {
			$result[$key] = $key;
		}
		return $result;
	}

	public static function GetBaseConf($type) {
		$types = self::GetBaseTypeConf();
		return $types[$type];
	}


}


//setlocale(LC_ALL, 'ru_RU.UTF-8', 'rus_RUS.UTF-8', 'Russian_Russia.UTF-8');
#setlocale(LC_ALL, 'ru_RU.CP1251', 'rus_RUS.CP1251', 'Russian_Russia.1251');

?>