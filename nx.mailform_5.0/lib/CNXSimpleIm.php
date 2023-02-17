<?php
/*
 *	CNXSimpleIm - PHP class for quick and comfortable work with inputs.
 *
 *	Copyright (c) 2010 Artem Kalashnikov
 *	Contact: maximus@imageprom.com
 *
 *	Integrated with bitrix by Marina Barsukova-Palagina
 *	Contact: necris@imageprom.com
 *	
 *	Requires PHP version 5 or later
 *	Requires Bitrix version 14 later
 *
 *	Date: 23:42 20/08/2022
 *
 *	Version: 3.2
 *
 */

namespace NXMailForm;

class CNXSimpleIm {

    private static $types = array(
        'text' => Array(
            'TYPE' => 'text',
            'REG' => 'text',
            'PLACEHOLDER' => true,
            'ARRVAL' => false
        ),

        'phone' => Array(
            'TYPE' => 'text',
            'REG' => 'phone',
            'PLACEHOLDER' => true,
            'ARRVAL' => false
        ),

        'mail' => Array(
            'TYPE' => 'text',
            'REG' => 'mail',
            'PLACEHOLDER' => true,
            'ARRVAL' => false
        ),

        'file' => Array(
            'TYPE' => 'file',
            'REG' => '',
            'PLACEHOLDER' => false,
            'ARRVAL' => false
        ),

        'textarea' => Array(
            'TYPE' => 'textarea',
            'REG' => 'textarea',
            'PLACEHOLDER' => true,
            'ARRVAL' => false
        ),

        'password' => Array(
            'TYPE' => 'password',
            'REG' => '',
            'PLACEHOLDER' => true,
            'ARRVAL' => false
        ),

        'checkbox' => Array(
            'TYPE' => 'checkbox',
            'REG' => '',
            'PLACEHOLDER' => false,
            'ARRVAL' => false
        ),

        'checkboxgroup' => Array(
            'TYPE' => 'checkboxgroup',
            'REG' => '', 'PLACEHOLDER' => false,
            'ARRVAL' => true
        ),

        'select' => Array(
            'TYPE' => 'select',
            'REG' => '',
            'PLACEHOLDER' => true,
            'ARRVAL' => true
        ),

        'multiselect'  => Array(
            'TYPE' => 'mselect',
            'REG' => '',
            'PLACEHOLDER' => true,
            'ARRVAL' => true
        ),

        'radio'   => Array(
            'TYPE' => 'radio',
            'REG' => '',
            'PLACEHOLDER' => false,
            'ARRVAL' => true
        ),

        'captcha' => Array(
            'TYPE' => 'captcha',
            'REG' => '',
            'PLACEHOLDER' => true ,
            'ARRVAL' => false
        ),

        'date' => Array(
            'TYPE' => 'date',
            'REG' => 'text',
            'PLACEHOLDER' => true,
            'ARRVAL' => false
        ),

        'hidden' => Array(
            'TYPE' => 'hidden',
            'REG' => '',
            'PLACEHOLDER' => false,
            'ARRVAL' => false
        ),
    );

    private static $mime = array(
        'jpg'  => 'image/jpeg, image/jpg, image/jp_, application/jpg, application/x-jpg, image/pjpeg, image/pipeg, image/vnd.swiftview-jpeg, image/x-xbitmap',
        'png'  => 'image/png, application/png, application/x-png',
        'gif'  => 'image/gif, image/x-xbitmap, image/gi_',
        'bmp'  => 'image/bmp, image/x-bmp, image/x-bitmap, image/x-xbitmap, image/x-win-bitmap, image/x-windows-bmp, image/ms-bmp, image/x-ms-bmp, application/bmp, application/x-bmp, application/x-win-bitmap ',
        'jpeg' => 'image/jpeg, image/jpg, image/jp_, application/jpg, application/x-jpg, image/pjpeg, image/pipeg, image/vnd.swiftview-jpeg, image/x-xbitmap',
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
        'csv'  => 'text/csv',
        'cdr' => 'application/cdr, application/coreldraw, application/x-cdr, application/x-coreldraw, image/cdr, image/x-cdr, zz-application/zz-winassoc-cdr',
        'ai' => 'application/postscript',
        'ps' => 'application/postscript',
        'psd' => 'image/photoshop, image/x-photoshop, image/psd, application/photoshop, application/psd, zz-application/zz-winassoc-psd',
    );

    private static $ext = Array(
        'jpg', 'png', 'gif', 'bmp', 'jpeg',
        'pdf', 'rtf', 'doc', 'docx', 'odt',
        'txt', 'ppt', 'pptx', 'xls', 'xlsx', 'xml',
        'csv', 'cdr', 'ai', 'psd'
    );

	private static $regTps = Array(
        "text"     => '/[^a-zA-ZА-Яа-яёЁ0-9\#\.,\%\$«»№:;\/\"\«\»\-\+\(\)\[\] ]/u',
		'mail'     => '/^[_\.a-zA-ZА-Яа-яёЁ0-9\-]+@[a-zA-ZА-Яа-яёЁ0-9\-\.]+\.[a-zA-ZА-Яа-яёЁ0-9\-]+$/u',
		'textarea' => '/[^a-zA-Zа-яА-ЯёЁ0-9@\№;*!?:\.,\-\+\%$\)\(\s]/u',
		'phone'    => '/[^-+\(\)0-9 ]/u'
    );

	private static $html5Patterns = Array(
        'text'     => '[ a-zA-ZА-Яа-яёЁ0-9\#\.,\%\$«»№:;\/\-\+\)\("]+',
		'mail'     => '[_\.a-zA-ZА-Яа-яёЁ0-9\-]+@[a-zA-ZА-Яа-яёЁ0-9\-\.]+\.[a-zA-ZА-Яа-яёЁ0-9\-]+',
		'textarea' => '[ a-zA-Zа-яА-ЯёЁ0-9@\№*!?;:\.,\-\+\%$\)\(\s]+',
        'phone'    => '[0-9\-\+\(\)\[\] ]+',
		'date'     => '[ .:0-9]+',
		'captcha'  => '[a-zA-Z0-9]+',
	);

	private static $html5Titles = Array( 
		'text'     => 'Возможно вы использовали опасные символы: например, кавычки',
		'mail'     => 'Проверьте, корректно ли вы ввели e-mail',
		'textarea' => 'Возможно вы использовали опасные символы, например кавычки', 
		'phone'    => 'Номер телефона может содержать числа, ( ) и +',
		'date'     => 'Дата может содержать числа, разделитель даты - точка, разделитель времени - двоеточие',
		'captcha'  => 'Проверочный код может содержать числа и символы латинского алфавита',
	);

    /**
     * CNXRegion constructor.
     * @param array $eArray
     * @param string $eKey
     */

    public $Type, $Name, $ObjKey, $inText, $Placeholder, $MValues,
           $Regular, $RegType, $Default, $Description, $Method,
           $RegularDetail, $RegularRight, $Oblig, $ErrorText, $Config,
           $Bxname, $Url, $jPretext, $_Arr, $ThisValue;

    function __construct($eArray, $eKey) {

        $this->Type				= $eArray['type'];
        $this->Name				= $eArray['name'];
        $this->ObjKey			= $eKey;
        $this->inText			= isset($eArray['intext']) ? $eArray['intext'] : '';
        $this->Placeholder		= isset($eArray['placeholder']) ? $eArray['placeholder'] : '';
        $this->MValues			= isset($eArray['values']) ? $eArray['values'] : '';

        if($this->Type	== 'select' || $this->Type	== 'multiselect')
            if(is_array($this->MValues)) $this->MValues['placeholder'] = '';

        $this->Regular			= isset($eArray['regular']) ? ((array_key_exists($eArray['regular'], self::$regTps)) ? self::$regTps[$eArray['regular']] : $eArray['regular']) : '';
        $this->RegType			= isset($eArray['regular']) ? ((array_key_exists($eArray['regular'], self::$regTps)) ? $eArray['regular'] : $eArray['regular']) : '';
        $this->Default			= isset($eArray['default']) ? $eArray['default'] : '';
        $this->Description		= isset($eArray['description']) ? $eArray['description'] : 'Неизвестное поле';
        $this->Method			= isset($eArray['method']) ? trim($eArray['method']) : 'post';
        $this->RegularDetail	= isset($eArray['rDetail']) ? $eArray['rDetail'] : true;
        $this->RegularRight		= isset($eArray['rRight']) ? $eArray['rRight'] : false;
        $this->Oblig			= isset($eArray['oblig']) ? $eArray['oblig'] : 0;
        $this->ErrorText	    = isset($eArray['eText']) ? trim($eArray['eText']) : '';
        $this->Config			= isset($eArray['config']) ? $eArray['config'] : Array();
        $this->Bxname			= isset($eArray['bxname']) ? $eArray['bxname'] : '';
        $this->Url				= isset($eArray['url']) ? $eArray['url'] : '';
        $this->jPretext			= isset($eArray['jpretext'])?$eArray['jpretext'] : false;

        switch ($this->Method) {
            case 'get':
                $this->_Arr = &$_GET;
                break;

            default:
                $this->_Arr = &$_POST;
                break;
        }

        $this->ThisValue = (isset($this->_Arr[$this->Name])) ? $this->_Arr[$this->Name] : false;
    }

    /**
     * @param string $type
     * @return bool|string
     */

    public function GetHtml5Pattern($type) {

        $type = trim(strval($type));

        if($type) {
            $pattern = '';
            if(self::$html5Patterns[$type]) {
                $pattern .= ' pattern="'.self::$html5Patterns[$type].'"';
                if(self::$html5Titles[$type])
                    $pattern .= ' title="'.self::$html5Titles[$type].'" ';
                return $pattern;
            }
        }

        return false;
    }

    /**
     * @param string $type
     * @return bool
     */

    public static function TypeHasPlaceholder($type) {
        $type = trim(strval($type));
        if(self::$types[$type]) return self::$types[$type]['PLACEHOLDER'];
        return false;
    }

    /**
     * @param string $type
     * @return bool
     */

    public static function TypeHasArrValues($type) {
        $type = trim(strval($type));
        if(self::$types[$type]) return self::$types[$type]['ARRVAL'];
        return false;
    }

    /**
     * @return string
     */

	public static function GetHtml5Required() {
		return 'required="required"';
	}

    /**
     * @return bool|string
     */

	public function GetPlaceholder() {
	    return ' placeholder="'.$this->Placeholder.'" ';
	} 
	
    /**
     * @return array
     */

	public static function GetFileFormats() {
		return self::$ext;
	}

    /**
     * @return string
     */

	public function GetMimeTypes() {

		$result = array();

		foreach ($this->Config['FileType'] as $key => $value) {
			$result[] = self::$mime[$value];
		}

		$result = array_unique($result);
		$result = implode(', ', $result);
		return $result;
	}

    /**
     * @param string $format
     * @return array|false
     */

	public static function GetFileFormat($format) {

        $format = trim(strval($format));
        if(!$format) return false;

		if(is_array($format)) {

		    $result = array();

		    foreach($format as $cnt => $val) {
				if($val >= 0 && $val < 15) $result[] = self::$ext[$val];
			}
		}
		else $result = array(self::$ext[intval($format)]);
		return $result; 
	}


    /**
     * @return array
     */

	public static function GetBaseTypeConf() {
		return self::$types;
	}

    /**
     * @return array
     */

	public static function GetBaseTypes() {
		$result = Array();

		foreach (self::$types as $key => $value) {
			$result[$key] = $key;
		}

		return $result;
	}

    /**
     * @param string $type
     * @return array|false
     */

	public static function GetBaseConf($type) {

        $type = trim(strval($type));
        if(!$type) return false;

		return self::$types[$type];
	}
}