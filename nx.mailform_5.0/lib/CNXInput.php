<?php
/*
 *	CNXInputs - PHP class for quick and comfortable work with inputs.
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
 *	Date: 23:42 20/08/2022
 *
 *	Version: 3.2
 *
 */

namespace NXMailForm;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/captcha.php');

if (version_compare(PHP_VERSION, '5.0.0', '<') )
    exit('Sorry, mInputs will only run on PHP version 5 or greater!'.PHP_EOL);

class CNXInput {

    /**
     *	Если true HTML шаблон не печатается. Функция preHTML возвращает false.
     */

    private $noPrint = false;

    /**
     *	Если true не печатает HTML шаблон, только после успешной проверки
     *  данных функцией CheckAllValues(). Свойству $noPrint присваивается
     *  значение true.
     */

	private $autoNoPrintHTML = true;

    /**
     *	Если true поля которые заполненны некорректно будут помечаться
     *  в соответствии с массивом $markedText.
     */

	private $markErrorInputs  = true;

    /**
     *	Массив со стилями, в соответствии с которым будут помечаться
     *  некорректно заполненные поля.
     */

	private $markedText = Array('color' => 'red', 'border-bottom' => '1px solid red');

    /**
     *	Массив со стилями, в соответствии с которым будет отображаться
     *  текст JsText.
     */

	private $jsTextCssArray = Array('color' => 'rgb(145,145,145)', 'font-style' => 'italic', 'font-family' => 'Verdana');

    /**
     *	Если true автоматически в конец шаблона будет добавлено
     *  скрытое поле для проверки отправлена ли форма с именем $addHiddenName.
     */

	private $addHiddenValue = true;

    /**
     *	Имя скрытого поля которое автоматически добавляется к шаблону.
     */

	private $addHiddenName = 'mInputsHidden';

    /**
     *	Значение которое в полях select и mselect которое не делает поле заполненным =)
     */

	public $falseValue = '___';

    /**
     *	Массив с шаблонами ошибок
     **/

	public $erArray = Array(
		'1'	 =>	 "Поле <b>{Description}</b> не найдено",
		'2'	 =>	 "Поле <b>{Description}</b> не заполнено",
		'3'	 =>	 "Поле <b>{Description}</b> заполнено неверно",
		'4'  =>  "Неизвестное значение в поле <b>{Description}</b>",
		'5'  =>  "В поле <b>{Description}</b> необходимо выбрать значение",
		'6'	 =>  "Поле <b>{Description}</b> имеет недопустимые символы: <u><b>{ErrorDetail}</b></u>"
	);

	public $jQueryUrl = 'https://code.jquery.com/jquery-1.8.2.min.js';
	public $FormID = '';

	private $markedInputs = Array();
	private $eFunc;
	private $Error;
	private $MainArray;
	private $Objs;
	private $mTemplateParam;
	private $jsTextArray;
	private $bxCaptcha;

    /**
     *  CNXInput constructor.
     *  @param array $iArray
     *  @param false|string $id
     */

    function __construct($iArray, $id = false) {
	     
		$this->MainArray = $iArray;
		foreach ($iArray as $mKey => $mValue) $this->Objs[$mKey] = new CNXSimpleIm($mValue, $mKey);
		
		$this->bxCaptcha = new \CCaptcha();
	    $captchaPass = \COption::GetOptionString('main', 'captcha_password', '');
	    if(strlen($captchaPass) <= 0) {
		    $captchaPass = \Bitrix\Main\Security\Random::getString(10);
		    \COption::SetOptionString('main', 'captcha_password', $captchaPass);
		}

		if($id) $this->addHiddenName.= '_'.$id;
	}

    /**
     *  @param array $input
     *  @return void
     */

	public function SetErrorColorScheme($input = Array()){
		if(count($input) > 0 && is_array($input)) {
			$this->markedText = $input;
		}
	}

    /**
     *	Добавляет пользовательскую функцию для проверки корректности ввода данных
     *
     *  @param string $V
     *  @return string
     */

	private function ErrorTFunction($V) {
	    $V = strval($V);
	    return '{'.$V.'}';
	}

    /**
     *  @param string $ErrorName
     *  @param int|string $Template
     *  @param object $Obj
     *  @param array $AddArray
     *  @return string
     */

	public function BuildErrorTemplate($ErrorName, $Template, $Obj, $AddArray = Array()) {

	    if (!$Template) return false;

        $ErrorName = strval($ErrorName);

		if (gettype($Template) == 'integer') {
				$Template = $this->erArray[$Template];
		}

		$ObjArray  = array_merge(get_object_vars($Obj), $AddArray);
		$ObjKeys   = array_map(Array($this, 'ErrorTFunction'), array_keys($ObjArray));
		$ObjValues = array_values($ObjArray);

		return str_replace($ObjKeys, $ObjValues, $Template);
	}

    /**
     *	Добавляет пользовательскую функцию для проверки корректности ввода данных
     *
     *  @param mixed $eFunc
     *  @return void
     */

    public function AddEFunc($eFunc) {
        $this->eFunc[] = $eFunc;
    }

    /**
     *	Проверяет была ли отправлена форма с полями
     *
     *  @return bool
     */

    public function isSubmit() {

        if ($this->addHiddenValue) {
            if (isset($_POST[$this->addHiddenName])) return true;
            return false;
        }

        foreach ($this->Objs as $oKey => $oV) {
            if (eval("return isset(\$_".strtoupper($oV->Method)."['".$oV->Name."']);")) return true;
        }

        return false;
    }

    /**
     *  Помечает некорректно заполненное поле, добавляя к его стилям значения
     *  указанные в массиве $markedText
     *
     *  @param object $Ob
     *  @return bool
     */

	private function markEInput($Ob) {

		if (!count($this->markedText)) return false;

		preg_match("/style=([\"']).*[\"']/i", $Ob->inText, $Matches);
		$NewMatch = $Matches[0];

		if (strlen(trim($Matches[0]))) {
			foreach ($this->markedText as $mKey => $mValue) {
				$NewMatch = (preg_match("/".$mKey.".+;/i", $NewMatch, $preMchs))?str_replace($preMchs[0], $mKey.": ".$mValue.";", $NewMatch):preg_replace("/style=([\"'])/i", "style=".$Matches[1].$mKey.": ".$mValue."; ", $NewMatch);
			}
			$Ob->inText = str_replace($Matches[0], $NewMatch, $Ob->inText);
		}

		else {

			foreach ($this->markedText as $mKey => $mValue) $NewMatch .= $mKey.": ".$mValue."; ";
			$Ob->inText .= ' style="'.$NewMatch.'"';
		}

		return true;
	}

    /**
     *  Возвращает массив значений полей для дальнейшей работы в CMS Bitrix
     *
     *  @return array
     */

	public function BuildBxArray() {

	    $BxArray = array();

		foreach ($this->Objs as $ObKey => $ObValue) {
			$BxArray[$ObValue->Bxname] = $this->GV($ObKey);
		}

		return $BxArray;
	}

    /**
     *	Проверяет корректность воода данных в поля
     *
     *  @return bool
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
		if (!$eBoo && $this->autoNoPrintHTML) $this->noPrint = true;
		
		return $eBoo;
	}

    /**
     *	Возвращает текст ошибки после проверки функцией CheckAllValues
     *
     *  @return string|false
     */

	public function EchoError() {

		if (count($this->Error)) {

			function ChArrErr(&$V) {
			    $V = '<li>'.$V.'</li>';
			}

			array_walk($this->Error, 'ChArrErr');

			$R = '<b>Ошибка!</b><ul>'.implode('', $this->Error).'</ul>';
			$this->Error = Array();
			return $R;
		}

		return false;
	}

    /**
     *	Устанавливает значение свойства $noPrint как true. После этого HTML шаблон
     *  печататься не будет.
     *
     * @return void
     */

	public function noPrintHTML() {
		$this->noPrint = true;
	}

    /**
     *	Возвращает описание заданного поля.
     *
     *  @param int $Key
     *  @param bool $Star
     *  @return string
     *
     */

	public function GD($Key, $Star = true) {
		$Ob = $this->Objs[$Key];
		$Desc = (isset($Ob->Description)) ? $Ob->Description : 'Неизвестно';
		if ($Ob->Oblig==1) $Desc .= ($Star==true) ? '<sup class="sup">*</sup>' : '';
		return $Desc;
	}

    /**
     *	Формирование и транслитерация имени загружаемого файла для полей типа file
     *  @param object $Ob
     *  @return string
     */

	private function getUploadFileName($Ob) {
		$name = explode('.', $_FILES[$Ob->Name]['name']);
	    $cnt = count($name)-1;
	    $ext = $name[$cnt];
	    unset ($name[$cnt]);
	    $name = implode('_', $name).'_'.date('d_m_Y__H_i_s'); 
	    $url = $Ob->Config['UploadUrl'];
	    $name = $url.\Cutil::translit($name, 'ru').'.'.$ext;
	    return $name;
	}

    /**
     *	Возвращает значение заданного поля.
     *  @param int $Key
     *  @return string
     */

    public function GV($Key) {

        $Ob = $this->Objs[$Key];

        switch ($Ob->Type) {

            case 'select':
            case 'radio':
                if(strtoupper($Ob->Method) == 'POST') {
                    return $Ob->MValues[$_POST[$Ob->Name]];
                }

                else {
                    return $Ob->MValues[$_GET[$Ob->Name]];
                }

                //return $src[$Ob->Name];
                //return eval("return \$Ob->MValues[\$_".strtoupper($Ob->Method)."['".$Ob->Name."']];");
                break;

            case 'mselect':
            case 'checkboxgroup': {

                    if(strtoupper($Ob->Method) == 'POST') {
                        $src = &$_POST;
                    }
                    else {
                        $src = &$_GET;
                    }

                    $result = '';
                    foreach ($Ob->MValues as $cnt => $item) {
                        if(in_array($cnt, $src[$Ob->Name])) $result.= $item.' | ';
                    }

                    $result = rtrim($result, ' | ');
                    return $result;
                }
                break;

            case 'file':
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
     *
     *  @param string $HTML
     *  @return string
     */

    private function AddHiddenInput($HTML) {
        $ReturnInput = '<input type="hidden" name="'.$this->addHiddenName.'" value="'.md5(uniqid()).'" />';
        return (preg_match('/<\/form>/i', $HTML)) ? str_ireplace('</form>', $ReturnInput.'</form>', $HTML) : $HTML.$ReturnInput;
    }

    /**
     *	Функция которая используется в preg_replace_callback функции preHTML()
     *  для замены элементов шаблона
     *  @param array $mRep
     *  @return string|bool
     */

    private function preReplace($mRep) {

        $mFunc = $mRep[1];
        $mKey  = $mRep[2];

        switch($mFunc) {

            case 'Desc':
                switch ($this->mTemplateParam['mParam']) {
                    case 'W';
                    case 'M';
                        return $this->GD($mKey);
                        break;

                    default:
                        return false;
                    break;
                }
                break;

            case 'Value':
                switch ($this->mTemplateParam['mParam']) {
                    case 'W';
                        if ($this->markErrorInputs && array_search($mKey, $this->markedInputs)!==false)
                            $this->markEInput($this->Objs[$mKey]);

                        return $this->BuildHTML($this->Objs[$mKey]);
                    break;

                    case 'M';
                        return $this->GV($mKey);
                    break;

                    default:
                        return false;
                    break;
                }
                break;

            default:
                return $mRep[0];
                break;
        }
    }

    /**
     *	Функция которая используется в preg_replace_callback функции preHTML()
     *  для замены элементов шаблона
     *  @param array $Mathes
     *  @return string
     */

    private function preSearchTemplates($Mathes) {

        $Mathes[1] = strtoupper($Mathes[1]);

        switch ($this->mTemplateParam['mParam']) {

            case 'W':
                if ($Mathes[1] == 'W')
                    return $Mathes[2];

                return '';
                break;

            case 'M':
                if ($Mathes[1] == 'M')
                    return $Mathes[2];

                return '';
                break;

            default:
                return $Mathes[0];
                break;
        }
    }

    /**
     *	Возвращяет HTML шаблон с замененными HTML элементами (описания и значения полей).
     *  @param string $Template
     *  @param string $mParam
     *  @return string
     */

    public function preHTML($Template, $mParam = 'W') {

        $JsTextHTML = '';

        $mParam = strtoupper($mParam);

        if ($this->noPrint && $mParam == 'W')   return '';
        if (empty($Template)) return 'Ошибка: Template пуст.';

        $this->mTemplateParam = Array('mParam' => $mParam);

        $Template = preg_replace_callback("/<minp\-(w|m)>(.+)<\/minp\-\\1>/isU", Array($this, 'preSearchTemplates'), $Template);
        $RHTML    = preg_replace_callback("/{([a-zA-Z0-9]+)\[([a-zA-Z0-9]+)\]}/U", Array($this, 'preReplace') , $Template);

        # JsText init

        if (count($this->jsTextArray)) {

            $jsPromArray = Array();
            $jsTextCssArray = Array();

            foreach ($this->jsTextArray as $jsKey => $jsValue)
                $jsPromArray[] = "'".$this->Objs[$jsValue]->Name."':'".$this->Objs[$jsValue]->jPretext."'";
            foreach ($this->jsTextCssArray as $jsCssKey => $jsCssValue)
                $jsTextCssArray[] = "'".$jsCssKey."':'".$jsCssValue."'";

            $jsPromString = implode(',', $jsPromArray);
            $jsTextCssString = implode(',', $jsTextCssArray);

            $JsTextHTML .= "<script>
                if (window.jQuery) {
                    let mInputsJsObj = {".$jsPromString."},
                        mInputsObj = {};
                for (let ObjKey in mInputsJsObj) {
                    eval(\"$('#\"+ObjKey+\"').focusout(function(){if ($('#\"+ObjKey+\"').val()=='') $('#\"+ObjKey+\"').val(mInputsJsObj['\"+ObjKey+\"']).css({".$jsTextCssString."});}).focus(function(){if ($('#\"+ObjKey+\"').val()==mInputsJsObj['\"+ObjKey+\"']) $('#\"+ObjKey+\"').val('').css(mInputsObj['\"+ObjKey+\"'].css);})\");
                    mInputsObj[ObjKey] = {}; mInputsObj[ObjKey].css = {};";

                    foreach ($this->jsTextCssArray as $jsCssKey => $jsCssValue) {
                        $JsTextHTML .= "\n\t\t\tmInputsObj[ObjKey].css['" . $jsCssKey . "'] = $('#'+ObjKey).css('" . $jsCssKey . "');";
                    }

            $JsTextHTML .= "\n\n\t\t\t if ($('#'+ObjKey).val() == '' && mInputsJsObj[ObjKey] != undefined)".
                           "$('#'+ObjKey).val(mInputsJsObj[ObjKey]).css({".$jsTextCssString."});".
                           "}}</script>";
        }

        if($JsTextHTML)
            $RHTML .= PHP_EOL.
                      "<script>if (!window.jQuery) {document.write(unescape('<script src=\"".$this->jQueryUrl."\">%3C/script%3E'))}</script>".
                      PHP_EOL.
                      $JsTextHTML;

        if($this->addHiddenValue)
            $RHTML = $this->AddHiddenInput($RHTML);

        return $RHTML;
    }

    /**
     *  Функция которая используется для создания HTML кода заданного поля
     *  с учетом его параметров.
     *
     * @param CNXSimpleIm $Obj
     * @return string|false
     */

    private function BuildHTML($Obj) {

        # jsText insert
        if ($Obj->jPretext and ($Obj->Type == 'text' or $Obj->Type == 'textarea')) {
            $this->jsTextArray[] = $Obj->ObjKey;
        }

        switch ($Obj->Type) {

            case 'captcha':

                $captchaPass = \Bitrix\Main\Security\Random::getString(10);
                $this->bxCaptcha->SetCodeCrypt($captchaPass);
                $ThisValue = $this->bxCaptcha->GetCodeCrypt();

                if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
                if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
                $Obj->inText .= $Obj->GetHtml5Pattern('captcha');

                return "<input type='hidden' name='".$Obj->Name."[sid]' id='".$Obj->Name."[sid]' value='".$ThisValue."'  />".
                       "<span class='cptch'>".
                       "<img src='/bitrix/tools/captcha.php?captcha_code=".$ThisValue."' alt='CAPTCHA' />".
                       "<span>".
                       "<input type='text' ".$Obj->inText." name='".$Obj->Name."[word]' id='".$Obj->Name."[word]' value='' maxlength='50' />";

                break;

            case 'file':

                if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
                    if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();

                $Obj->inText .= " accept='".$Obj->GetMimeTypes()."' ";

                return "<input type='file' name='".$Obj->Name."' ".$Obj->inText." id='".$Obj->Name."' />";

            break;

            case 'hidden':

                $ThisValue = ($Obj->ThisValue && trim($Obj->ThisValue) != '') ? trim($Obj->ThisValue) : $Obj->Default;
                return "<input type='hidden' name='".$Obj->Name.
                       "' ".$Obj->inText.
                       " id='".$Obj->Name.
                       "' value='".trim(htmlspecialchars($ThisValue, ENT_QUOTES))."' />";

                break;


            case 'text':

                $ThisValue = ($Obj->ThisValue)?trim($Obj->ThisValue):$Obj->Default;
                $ThisValue = stripslashes($ThisValue);

                if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
                if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();


                if($Obj->RegType == 'phone') {
                    $type = "tel";
                    $Obj->inText .= $Obj->GetHtml5Pattern('phone');
                }
                elseif ($Obj->RegType == 'mail') {
                    $type = "email";
                    $Obj->inText .= $Obj->GetHtml5Pattern('mail');
                }
                else {
                    $type = "text";
                    $Obj->inText .= $Obj->GetHtml5Pattern('text');
                }

                return "<input type='".$type.
                        "' name='".$Obj->Name.
                        "' id='".$Obj->Name."' ".$Obj->inText.
                        " value='".trim(htmlspecialchars($ThisValue, ENT_QUOTES))."' />";

                break;

            case 'date':

                $ThisValue = ($Obj->ThisValue) ? trim($Obj->ThisValue) : $Obj->Default;
                $ThisValue = stripslashes($ThisValue);

                \CJSCore::Init(Array('popup', 'date'));

                if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
                if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();

                $Obj->inText .= $Obj->GetHtml5Pattern('date');

                return "<span class='dttm'><input type='text' name='".$Obj->Name."' id='".$Obj->Name."' ".$Obj->inText." value='".trim(htmlspecialchars($ThisValue, ENT_QUOTES))."' />
                            <img src='/bitrix/js/main/core/images/calendar-icon.gif' alt='Календарь' class='calendar-icon' 
                            onclick='BX.calendar({node:this, field:\"".htmlspecialcharsbx(\CUtil::JSEscape($Obj->Name))."\", form:\"\", bTime:\"false\", currentTime:\"false\", bHideTime:\"false\"});' 
                            onmouseover='BX.addClass(this, \"calendar-icon-hover\");' 
                            onmouseout='BX.removeClass(this, \"calendar-icon-hover\");' 
                            border='0' />
                        </span>";

                break;

            case 'checkbox':

                if(isset($Obj->ThisValue)) $Obj->ThisValue = false;
                if((!$this->isSubmit() && $Obj->Default == 'Y') || ($Obj->ThisValue == $Obj->MValues) ) {
                    $CheckValueSelected = 'checked="checked"';
                }
                else $CheckValueSelected = '';

                if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();

                return "<input type='checkbox' ".$CheckValueSelected." ".
                       $Obj->inText.
                       " name='".$Obj->Name.
                       "' id='".$Obj->Name.
                       "' value='".$Obj->MValues."' />";
                break;

            case 'password':
                if($Obj->Placeholder)  $Obj->inText .= $Obj->GetPlaceholder();
                if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();
                $Obj->inText .= $Obj->GetHtml5Pattern('text');
                Return "<input type='password' name='".$Obj->Name.
                       "' id='".$Obj->Name."' ".
                       $Obj->inText." value='' />";
                break;

            case 'select':

                $RSelectedValue = ($Obj->ThisValue && trim($Obj->ThisValue) != '') ? trim($Obj->ThisValue) : $Obj->Default;
                $RSelected = ($RSelectedValue === 0) ? 'selected' : '';

                if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();

                $ReturnHTML = "<select ".$Obj->inText." name='".$Obj->Name."' id='".$Obj->Name."'>";

                if($Obj->Placeholder) {
                    $ReturnHTML .= "<option ".$RSelected." value='placeholder' data-default='default'>".
                                        $Obj->Placeholder.
                                    "</option>";
                }

                if(!is_array($Obj->MValues)) $Obj->MValues = array($Obj->MValues);

                foreach ($Obj->MValues as $VKey => $VValue) {
                    if($VKey != 'placeholder') {
                        $RSelected = ($RSelectedValue === $VKey) ? 'selected' : '';
                        $ReturnHTML .= "<option ".$RSelected." value='".$VKey."'>".$VValue."</option>";
                    }
                }

                $ReturnHTML .= "</select>";
                return $ReturnHTML;

                break;

            case 'mselect':

                if (is_array($Obj->ThisValue)) {
                    $sKey = array_search($this->falseValue, $Obj->ThisValue);

                    if ($sKey !== false && count($Obj->ThisValue) > 1)
                        unset($Obj->ThisValue[$sKey]);

                    $RSelectedArray = $Obj->ThisValue;
                }
                else {
                    $RSelectedArray = $Obj->Default;
                }

                $RSelected = (is_array($RSelectedArray) && array_search(0, $RSelectedArray) !== false) ? 'selected' : '';

                if($Obj->Oblig) $Obj->inText .= $Obj->GetHtml5Required();

                $ReturnHTML = "<select ".$Obj->inText." multiple name='".$Obj->Name."[]' id='".$Obj->Name."'>";

                if($Obj->Placeholder)
                    $ReturnHTML .= "<option ".$RSelected." value='placeholder' data-default='default'>".
                                   $Obj->Placeholder.
                                   "</option>";

                if(!is_array($Obj->MValues))
                    $Obj->MValues = array($Obj->MValues);

                foreach ($Obj->MValues as $_MKey => $_MVal) {
                    $RSelected = (is_array($RSelectedArray) && array_search($_MKey, $RSelectedArray) !== false) ? 'selected' : '';
                    $ReturnHTML .= "<option ".$RSelected." value='".$_MKey."'>".$_MVal;
                }

                $ReturnHTML .= "</select>";

                return $ReturnHTML;

                break;

        case 'textarea':

            $TextareaCurrent = ($Obj->ThisValue && trim($Obj->ThisValue) != '') ? trim($Obj->ThisValue) : $Obj->Default;
            $TextareaCurrent = stripslashes($TextareaCurrent);

            if($Obj->Placeholder)
                $Obj->inText .= $Obj->GetPlaceholder();

            if($Obj->Oblig)
                $Obj->inText .= $Obj->GetHtml5Required();

            $Obj->inText .= $Obj->GetHtml5Pattern('text');

            return "<textarea name='".$Obj->Name."' id='".$Obj->Name."' ".$Obj->inText.">".
                        htmlspecialchars($TextareaCurrent, ENT_QUOTES).
                   "</textarea>";

            break;

        case 'radio':

            if(!$Obj->Default)
                $Obj->Default = 0;

            $RSelectedValue = ($Obj->ThisValue && trim($Obj->ThisValue) != '') ? trim($Obj->ThisValue) : $Obj->Default;
            $ReturnHTML = "<div class='fldst form-check'>";

            if(!is_array($Obj->MValues))
                $Obj->MValues = array($Obj->MValues);

            foreach ($Obj->MValues as $VKey => $VValue) {
                $RSelected = ($RSelectedValue==$VKey) ? 'checked="checked"' : '';
                $ReturnHTML .= "<label class='form-check-label'>".
                               "<input type='radio' name='".$Obj->Name."' ".$Obj->inText." value='".$VKey."' ".$RSelected." />".
                               "&nbsp;".$VValue.
                               "</label>";
            }

            $ReturnHTML .= "</div>";
            return $ReturnHTML;

            break;

        case 'checkboxgroup':

            if (is_array($Obj->ThisValue)) {

                $sKey = array_search($this->falseValue, $Obj->ThisValue);

                if ($sKey !== false && count($Obj->ThisValue) > 1)
                    unset($Obj->ThisValue[$sKey]);

                $RSelectedArray = $Obj->ThisValue;
            }
            else {
                $RSelectedArray = $Obj->Default;
            }

            $ReturnHTML = "<div class='fldst'>";

            if(!is_array($Obj->MValues))
                $Obj->MValues = array($Obj->MValues);

            foreach ($Obj->MValues as $_MKey => $_MVal) {
                $RSelected = (is_array($RSelectedArray) && array_search($_MKey, $RSelectedArray) !== false) ? 'checked="checked"' : '';
                $ReturnHTML .= "<div class='checkboxgroupitem'>".
                               "<input type='checkbox' name='".$Obj->Name."[]' ".$Obj->inText." value='".$_MKey."' ".$RSelected." id='".$Obj->Name.$_MKey."'/> ".
                               "<label for='".$Obj->Name.$_MKey."'>".$_MVal."</label>".
                               "</div>";
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
     *
     *  @param CNXSimpleIm $Obj
     *  @return string|false
     */

	private function CheckValues($Obj) {

		# Check JsText 
		if ($Obj->jPretext and ($Obj->Type == 'text' or $Obj->Type == 'textarea') and $Obj->jPretext == $Obj->ThisValue)
		    $Obj->ThisValue = false;

        global $APPLICATION;

		switch ($Obj->Type) {

		case 'captcha':

			if (!$Obj->ThisValue)
			    return ($Obj->Oblig) ? 'Необходимо ввести <b>'.$Obj->Description.'</b>' : false;

			if (!$APPLICATION->CaptchaCheckCode($Obj->ThisValue['word'], $Obj->ThisValue['sid']))
			    return '<b>'.$Obj->Description.'</b> введен неверно';

			return false;

		    break;

		case 'file':
		  
		    if (!$_FILES[$Obj->Name]['name']) return false;
		    $FileUrl = $_SERVER['DOCUMENT_ROOT'].$this->getUploadFileName($Obj);
		 	
			if (is_uploaded_file($_FILES[$Obj->Name]['tmp_name']) == false) { 
				return 'Ошибка при загрузке файла <b>'.$_FILES[$Obj->Name]['name'].'</b>.';
			}
			elseif (isset($Obj->Config['UploadMaxSize']) && $Obj->Config['UploadMaxSize']>0 && ($_FILES[$Obj->Name]['size']>$Obj->Config['UploadMaxSize'])) {
				return 'Файл <b>'.$_FILES[$Obj->Name]['name'].'</b> превышает допустимый размер.';
			}
		    elseif (!in_array(pathinfo($_FILES[$Obj->Name]['name'], PATHINFO_EXTENSION), $Obj->Config['FileType'])) {
		    	return 'Недопустимый тип файла <b>'.$_FILES[$Obj->Name]['name'].'</b>';
		    }
			elseif (!copy($_FILES[$Obj->Name]['tmp_name'], $FileUrl)){
		        return 'Ошибка при сохранении файла <b>'.$_FILES[$Obj->Name]['name'].'</b>';
		    }
				
		   	return false;

		    break;

		case 'text':
		case 'textarea':
		case 'password':

			if (trim($Obj->ThisValue) == '' || $Obj->ThisValue === false)
				return ($Obj->Oblig) ? $this->BuildErrorTemplate( '2' , 2 , $Obj) : false;
				
			if ($Obj->Regular && $Obj->Regular != '') {
			    preg_match_all($Obj->Regular, stripslashes($Obj->ThisValue), $Mathes);

			    if (count($Mathes[0])==$Obj->RegularRight) {
					return false;
			    }

			    else {
					if (!$Obj->RegularRight&&$Obj->RegularDetail) {	
						return $this->BuildErrorTemplate( '4' , 6, $Obj, Array("ErrorDetail" => stripslashes(htmlspecialchars(implode('', array_unique($Mathes[0])), ENT_QUOTES))));
					}

					return $this->BuildErrorTemplate( '3' , 3 , $Obj);
				}
			}

		    return false;
		    break;

		case 'select':
			
			if ($Obj->ThisValue != $this->falseValue && !isset($Obj->MValues[$Obj->ThisValue]))
			    return $this->BuildErrorTemplate( '1' , 4 , $Obj);

			if ($Obj->Oblig && ($Obj->ThisValue == $this->falseValue || $Obj->ThisValue == 'placeholder'))
			    return $this->BuildErrorTemplate( '2' , 5 , $Obj);

			return false;
		    break;

		case 'mselect':

			if(!is_array($Obj->ThisValue) && $Obj->Oblig)
			    return $this->BuildErrorTemplate( '1' , 5 , $Obj);

			if($Obj->Oblig) {

				foreach ($Obj->ThisValue as $_AKey => $_AValue) {
					if(!isset($Obj->MValues[$_AValue]) && $_AValue != 'placeholder')
					    return $this->BuildErrorTemplate( '2' , 4 , $Obj);
                }
				
				$SearchDefValue = array_search($this->falseValue, $Obj->ThisValue);

				if($SearchDefValue !== false)
				    unset($Obj->ThisValue[$SearchDefValue]);

				$SearchDefValue = array_search('placeholder', $Obj->ThisValue);

				if($SearchDefValue !== false)
				    unset($Obj->ThisValue[$SearchDefValue]);

				if(!count($Obj->ThisValue) > 0 && $Obj->Oblig) {
				    unset($Obj->ThisValue[array_search($this->falseValue, $Obj->ThisValue)]);
				    return $this->BuildErrorTemplate( '2' , 5 , $Obj);
				}
			}

		    return false;
		    break;

		case 'checkboxgroup':

			if (!is_array($Obj->ThisValue) && $Obj->Oblig)
			    return $this->BuildErrorTemplate( '1' , 5 , $Obj);

			if ($Obj->Oblig) {

				foreach ($Obj->ThisValue as $_AKey => $_AValue) {
				    if (!isset($Obj->MValues[$_AValue]))
				        return $this->BuildErrorTemplate( '2' , 4 , $Obj);
                }
				
				$SearchDefValue = array_search($this->falseValue, $Obj->ThisValue);

				if ($SearchDefValue !== false)
				    unset($Obj->ThisValue[$SearchDefValue]);

				if (!count($Obj->ThisValue) > 0 && $Obj->Oblig) {
				    unset($Obj->ThisValue[array_search($this->falseValue, $Obj->ThisValue)]);
				    return $this->BuildErrorTemplate( '3' , 2 , $Obj);
				}
			}

		    return false;
		    break;

		case 'checkbox':

			if ($Obj->Oblig&&$this->isSubmit() && $Obj->ThisValue === false)
			    return $this->BuildErrorTemplate( '1' , 2 , $Obj);

			return false;
		    break;

		default:
			return false;
		    break;
		}
	}
}

//setlocale(LC_ALL, 'ru_RU.UTF-8', 'rus_RUS.UTF-8', 'Russian_Russia.UTF-8');
#setlocale(LC_ALL, 'ru_RU.CP1251', 'rus_RUS.CP1251', 'Russian_Russia.1251');