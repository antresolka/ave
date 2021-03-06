<?php

/**
 * AVE.cms - Модуль Рассылки
 *
 * @package AVE.cms
 * @subpackage module_mailer
 * @filesource
 */

if (!defined('BASE_DIR')) exit;

if (defined('ACP'))
{
    $modul['ModuleName'] = 'Рассылка / Подписка';
    $modul['ModuleSysName'] = 'mailer';
    $modul['ModuleVersion'] = '2.2.1';
    $modul['ModuleDescription'] = 'Данный модуль позволяет создавать внутренние (по группам пользователей сайта), внешние (по спискам) и комбинированные рассылки. Для вывода в публичной части сайта формы подписки на рассылку, используйте системный тег <strong>[mod_subscribe:XXX]</strong>, где XXX - идентификатор списка рассылки, в который будет добавлен подписчик.';
    $modul['ModuleAutor'] = 'val005';
    $modul['ModuleCopyright'] = '&copy; 2007-2013 AVE.CMS';
    $modul['ModuleIsFunction'] = 1;
    $modul['ModuleTemplate'] = 1;
    $modul['ModuleAdminEdit'] = 1;
    $modul['ModuleFunction'] = 'mod_subscribe';
    $modul['ModuleTag'] = '[mod_subscribe:XXX]';
    $modul['ModuleTagLink'] = null;
    $modul['ModuleAveTag'] = '#\\\[mod_subscribe:(\\\d+)]#';
    $modul['ModulePHPTag'] = "<?php mod_subscribe(''$1''); ?>";
}

/**
 * Обработка тега модуля
 *
 * @param int $list_id идентификатор списка рассылки
 */
function mod_subscribe($list_id)
{
	include_once(BASE_DIR . '/modules/mailer/class.mailer.php');
	$mailer = new mailer;
	$mailer->tpl_dir = BASE_DIR . '/modules/mailer/templates/';

	global $AVE_Template;
	$lang_file = BASE_DIR . '/modules/mailer/lang/' . $_SESSION['user_language'] . '.txt';
	$AVE_Template->config_load($lang_file, 'public');

    $list_id = (int)preg_replace('/\D/', '', $list_id);
	$_POST['email'] = trim($_POST['email']);

	switch ($_POST['action'])
	{
		case 'subscribe':
			$result = $mailer->mailerSubscribe();
			switch ($result)
			{
				case 0:
				$mes = $AVE_Template->get_config_vars('MAILER_WRONG_EMAIL');
				break;

				case 1:
				$mes = str_replace('%email%',$_POST['email'],$AVE_Template->get_config_vars('MAILER_SUBSCRIBED'));
				$AVE_Template->assign('hide_sub',true);
				break;

				case 2:
				$mes = str_replace('%email%',$_POST['email'],$AVE_Template->get_config_vars('MAILER_SUBSCRIBE_EXIST'));
				$AVE_Template->assign('hide_sub',true);
				break;
			}
			break;

		case 'unsubscribe':
			$result = $mailer->mailerUnsubscribe();
			switch ($result)
			{
				case 0:
				$mes = str_replace('%email%',$_POST['email'],$AVE_Template->get_config_vars('MAILER_DEL_NO')); break;
				case 1:
				$mes = str_replace('%email%',$_POST['email'],$AVE_Template->get_config_vars('MAILER_DELETED')); break;
			}
			$AVE_Template->assign('hide_unsub',true);
			break;
	}
	$AVE_Template->assign('message', $mes);
	$AVE_Template->assign('action', $_POST['action']);
	$AVE_Template->assign('list_id', $list_id);
	$AVE_Template->display($mailer->tpl_dir . 'public_form.tpl');
}

/**
 * Внешнее обращение
 */
if (!defined('ACP') && $_REQUEST['module'] == 'mailer')
{
	global $AVE_DB;
	switch ($_REQUEST['action'])
	{
		case 'show':
			$mail = $AVE_DB->Query("
				SELECT body, appeal, type
				FROM " . PREFIX . "_module_mailer_mails
				WHERE id = " . $_REQUEST['id']
			)->FetchRow();
			$body = str_replace(
				array('%NAME%'			,'%SHOW%'),
				array($mail->appeal		,'#'),
				$mail->body);
			echo ($mail -> type == 'text') ? '<pre>'.$body.'</pre>' : $body;
	}
}

/**
 * Админка
 */
if (defined('ACP') && $_REQUEST['mod'] == 'mailer')
{
	include_once(BASE_DIR . '/modules/mailer/class.mailer.php');
	$mailer = new mailer;
	$mailer->tpl_dir = BASE_DIR . '/modules/mailer/templates/';

	$lang_file = BASE_DIR . '/modules/mailer/lang/' . $_SESSION['admin_language'] . '.txt';
	$AVE_Template->config_load($lang_file, 'admin');

	switch ($_REQUEST['moduleaction'])
	{
		// Вывод списка рассылок
		case '':
		case '1':
			$mailer->mailerShowMails();
			break;

		// Загрузка вложения из отправленной рассылки
		case 'getfile':
			if (file_exists($_REQUEST['file']))
			{
				if ($_REQUEST['check']) exit('1');
				else $mailer->_mailerGetFile($_REQUEST['file']);
			}
			exit;

		// Вывод списка рассылок
		case 'editmail':
			include_once(BASE_DIR . '/class/class.user.php');
			$AVE_User = new AVE_User;
			$mailer->mailerEditMail();
			break;
			
		case 'savemail':
			include_once(BASE_DIR . '/class/class.user.php');
			$AVE_User = new AVE_User;
			$mailer->mailerSaveMail($_REQUEST['id'],$_REQUEST['act']);
			break;

		case 'countmail':
			$mailer->mailerCountMail($_REQUEST['id']);
			break;

		case 'testsend':
			$emails = $mailer->mailerTestSend($_REQUEST['id'],$_REQUEST['emails']);
			echo implode(', ',$emails);
			exit;

		case 'sendmail':
			$mailer->mailerSendMail();
			break;

		case 'showlists':
			$mailer->mailerShowLists();
			break;

		case 'editlist':
			$mailer->mailerEditList();
			break;

		case 'savelist':
			$mailer->mailerSaveList();
			break;

		case 'multiadd':
			$mailer->mailerMultiAdd();
			break;

		case 'multisave':
			$mailer->mailerMultiSave();
			break;

		case 'delreceiver':
			$mailer->_mailerDelReceiver($_REQUEST['rec_id']);
			break;
		
		case 'checkemail':
			echo $mailer->_mailerCheckEmail(trim($_REQUEST['email']),(int)trim($_REQUEST['list_id']));
			exit;
	}
}

?>