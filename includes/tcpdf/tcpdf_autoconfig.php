<?php
//============================================================+
// File name   : tcpdf_autoconfig.php
// Version     : 1.1.1
// Begin       : 2013-05-16
// Last Update : 2014-12-18
// Authors     : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2011-2014 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with TCPDF. If not, see
// <http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// Description : Try to automatically configure some TCPDF
//               constants if not defined.
//
//============================================================+

/**
 * @file
 * Try to automatically configure some TCPDF constants if not defined.
 * @package com.tecnick.tcpdf
 * @version 1.1.1
 */

$_SERVER['DOCUMENT_ROOT'] = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']);
if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/') {
	$_SERVER['DOCUMENT_ROOT'] .= '/';
}
// Italian

global $l;
$l = Array();

// PAGE META DESCRIPTORS --------------------------------------

$l['a_meta_charset'] = 'UTF-8';
$l['a_meta_dir'] = 'ltr';
$l['a_meta_language'] = 'it';

// TRANSLATIONS --------------------------------------
$l['w_page'] = 'pagina';

if (!defined('K_PATH_MAIN')) {
	define ('K_PATH_MAIN', dirname(__FILE__).'/');
}

if (!defined('K_PATH_FONTS')) {
	define ('K_PATH_FONTS', K_PATH_MAIN.'fonts/');
}

if (!defined('K_PATH_URL')) {
	$k_path_url = K_PATH_MAIN; // default value for console mode
	if (isset($_SERVER['HTTP_HOST']) AND (!empty($_SERVER['HTTP_HOST']))) {
		if(isset($_SERVER['HTTPS']) AND (!empty($_SERVER['HTTPS'])) AND (strtolower($_SERVER['HTTPS']) != 'off')) {
			$k_path_url = 'https://';
		} else {
			$k_path_url = 'http://';
		}
		$k_path_url .= $_SERVER['HTTP_HOST'];
		$k_path_url .= str_replace( '\\', '/', substr(K_PATH_MAIN, (strlen($_SERVER['DOCUMENT_ROOT']) - 1)));
	}
	define ('K_PATH_URL', $k_path_url);
}

if (!defined('K_PATH_IMAGES')) {
	define ('K_PATH_IMAGES', "");
}

if (!defined('PDF_HEADER_LOGO')) {
	define ('PDF_HEADER_LOGO', "");
}

if (!defined('PDF_HEADER_LOGO_WIDTH')) {
		define ('PDF_HEADER_LOGO_WIDTH', 900);

}

if (!defined('K_PATH_CACHE')) {
	$K_PATH_CACHE = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
	if (substr($K_PATH_CACHE, -1) != '/') {
		$K_PATH_CACHE .= '/';
	}
	define ('K_PATH_CACHE', $K_PATH_CACHE);
}

if (!defined('K_BLANK_IMAGE')) {
	define ('K_BLANK_IMAGE', '_blank.png');
}

if (!defined('PDF_PAGE_FORMAT')) {
	define ('PDF_PAGE_FORMAT', 'A4');
}

if (!defined('PDF_PAGE_ORIENTATION')) {
	define ('PDF_PAGE_ORIENTATION', 'P');
}

if (!defined('PDF_CREATOR')) {
	define ('PDF_CREATOR', 'Gestione Corsi');
}

if (!defined('PDF_AUTHOR')) {
	define ('PDF_AUTHOR', 'Scimone Ignazio');
}

if (!defined('PDF_HEADER_TITLE')) {
	define ('PDF_HEADER_TITLE', '');
}

if (!defined('PDF_HEADER_STRING')) {
	define ('PDF_HEADER_STRING', "");
}

if (!defined('PDF_UNIT')) {
	define ('PDF_UNIT', 'mm');
}

if (!defined('PDF_MARGIN_HEADER')) {
	define ('PDF_MARGIN_HEADER', 5);
}

if (!defined('PDF_MARGIN_FOOTER')) {
	define ('PDF_MARGIN_FOOTER', 10);
}

if (!defined('PDF_MARGIN_TOP')) {
	define ('PDF_MARGIN_TOP', 27);
}

if (!defined('PDF_MARGIN_BOTTOM')) {
	define ('PDF_MARGIN_BOTTOM', 25);
}

if (!defined('PDF_MARGIN_LEFT')) {
	define ('PDF_MARGIN_LEFT', 15);
}

if (!defined('PDF_MARGIN_RIGHT')) {
	define ('PDF_MARGIN_RIGHT', 15);
}

if (!defined('PDF_FONT_NAME_MAIN')) {
	define ('PDF_FONT_NAME_MAIN', 'helvetica');
}

if (!defined('PDF_FONT_SIZE_MAIN')) {
	define ('PDF_FONT_SIZE_MAIN', 10);
}

if (!defined('PDF_FONT_NAME_DATA')) {
	define ('PDF_FONT_NAME_DATA', 'helvetica');
}

if (!defined('PDF_FONT_SIZE_DATA')) {
	define ('PDF_FONT_SIZE_DATA', 8);
}

if (!defined('PDF_FONT_MONOSPACED')) {
	define ('PDF_FONT_MONOSPACED', 'courier');
}

if (!defined('PDF_IMAGE_SCALE_RATIO')) {
	define ('PDF_IMAGE_SCALE_RATIO', 1.25);
}

if (!defined('HEAD_MAGNIFICATION')) {
	define('HEAD_MAGNIFICATION', 1.1);
}

if (!defined('K_CELL_HEIGHT_RATIO')) {
	define('K_CELL_HEIGHT_RATIO', 1.25);
}

if (!defined('K_TITLE_MAGNIFICATION')) {
	define('K_TITLE_MAGNIFICATION', 1.3);
}

if (!defined('K_SMALL_RATIO')) {
	define('K_SMALL_RATIO', 2/3);
}

if (!defined('K_THAI_TOPCHARS')) {
	define('K_THAI_TOPCHARS', true);
}

if (!defined('K_TCPDF_CALLS_IN_HTML')) {
	define('K_TCPDF_CALLS_IN_HTML', false);
}

if (!defined('K_TCPDF_THROW_EXCEPTION_ERROR')) {
	define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
}

if (!defined('K_TIMEZONE')) {
	define('K_TIMEZONE', @date_default_timezone_get());
}

//============================================================+
// END OF FILE
//============================================================+
