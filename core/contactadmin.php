<?php
/**
*
* Contact Admin extension for the phpBB Forum Software package.
*
* @copyright 2016 Rich McGirr (RMcGirr83)
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/
namespace rmcgirr83\contactadmin\core;

use phpbb\auth\auth;
use phpbb\cache\service as cache_service;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\language\language;
use phpbb\log\log;
use phpbb\request\request;
use phpbb\user;
use phpbb\exception\http_exception;
use rmcgirr83\contactadmin\core\contact_constants as contact_constants;
use Symfony\Component\HttpFoundation\JsonResponse;

class contactadmin
{
	/** @var auth */
	protected $auth;

	/** @var cache_service */
	protected $cache;

	/** @var config */
	protected $config;

	/** @var driver_interface */
	protected $db;

	/** @var language */
	protected $language;

	/** @var log */
	protected $log;

	/** @var request */
	protected $request;

	/** @var user */
	protected $user;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string phpEx */
	protected $php_ext;

	public function __construct(
			auth $auth,
			cache_service $cache,
			config $config,
			driver_interface $db,
			language $language,
			log $log,
			request $request,
			user $user,
			$root_path,
			$php_ext)
	{
		$this->auth = $auth;
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->request = $request;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		if (!class_exists('messenger'))
		{
			include($this->root_path . 'includes/functions_messenger.' . $this->php_ext);
		}
	}

	/**
	* contact_change_auth
	* thanks to poppertom69 for the idea..and some of the code
	* @param $bot_id	the user id of the contact bot chosen in the ACP
	* @param $mode		the mode either replace or restore
	* @param $bkup_data	an array of the current users data
	* changes the user in posting to that of the bot chosen in the ACP
	*/
	public function contact_change_auth($bot_id, $mode = 'replace', $bkup_data = false)
	{
		switch ($mode)
		{
			// change our user to one we chose in the ACP settings
			// for posting or PM'ing only
			case 'replace':

				$bkup_data = [
					'user_backup'	=> $this->user->data,
				];

				// sql to get the bots info
				$sql = 'SELECT *
					FROM ' . USERS_TABLE . '
					WHERE user_id = ' . (int) $bot_id;
				$result	= $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				// reset the current users info to that of the bot
				// we do this instead of just using the sql query
				// for items such as $this->user->data['is_registered'] which isn't a table column from the users table
				$this->user->data = array_merge($this->user->data, $row);

				unset($row);

				return $bkup_data;

			break;

			// now we restore the users stuff
			case 'restore':

				$this->user->data = $bkup_data['user_backup'];

				unset($bkup_data);
			break;
		}
	}

	/**
	* contact_check
	* @param string		$mode		what we are checking
	* @param int		$forum_id	the forum id selected in ACP
	* @param int		$bot_id		the id of the bot selected in the ACP
	* @param string		$method		the type of contact we are using email, forum post or PM
	* ensures postable forum and correct "bot"
	*/
	public function contact_check($mode, $forum_id = false, $bot_id = false, $method = false)
	{
		// the servers url
		$server_url = generate_board_url();

		switch ($mode)
		{
			// check for a valid forum
			case 'contact_check_forum':

				$sql = 'SELECT forum_name
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id . '
				AND forum_type = ' . FORUM_POST;
				$result = $this->db->sql_query($sql);

				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				// we didn't get a result
				// send an email if board enabled
				if (!$row && $this->config['email_enable'])
				{

					// send an email to the board default
					$email_template = '@rmcgirr83_contactadmin/contact_error';
					$email_message = $this->language->lang('CONTACT_BOT_MESSAGE', $this->user->data['username'], $this->config['sitename'], $this->language->lang('FORUM'), $server_url);
					$this->contact_send_email($email_template, $email_message);

					// add an entry into the error log
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_FORUM_INVALID',  time(), [$forum_id, $row]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_ERROR') . '<br /><br />' . $this->language->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">', '</a>');

					throw new http_exception(503, $message);
				}
				else if (!$row)
				{
					// add an entry into the error log
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_FORUM_INVALID',  time(), [$forum_id, $row]);

					$message = $this->language->lang('CONTACT_DISABLED', '<a href="mailto:' . $this->config['board_contact'] . '">', '</a>');

					throw new http_exception(503, $message);
				}

			break;

			// check for a valid bot
			case 'contact_check_bot':

				$sql = 'SELECT username
					FROM ' . USERS_TABLE . '
						WHERE user_id = ' . (int) $bot_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				// we didn't get a result
				// send an email if board enabled
				if (!$row && $this->config['email_enable'])
				{
					// send an email to the board default
					$email_template = '@rmcgirr83_contactadmin/contact_error';
					$email_message = $this->language->lang('CONTACT_BOT_MESSAGE', $this->user->data['username'], $this->config['sitename'], $this->language->lang('USER'), $server_url);
					$this->contact_send_email($email_template, $email_message);

					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_BOT_INVALID',  time(), [$bot_id, $row]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_ERROR') . '<br /><br />' . $this->language->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">', '</a>');

					throw new http_exception(503, $message);
				}
				else if (!$row)
				{
					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_BOT_INVALID',  time(), [$bot_id, $row]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_DISABLED', '<a href="mailto:' . $this->config['board_contact'] . '">', '</a>');

					throw new http_exception(503, $message);
				}
			break;

			case 'contact_nobody':

				//this is only called if there are no contact admins available
				// for pm'ing or for emailing to per the preferences set by the admin user in their profiles
				if ($method == contact_constants::CONTACT_METHOD_EMAIL)
				{
					$error = $this->language->lang('EMAIL');
				}
				else
				{
					$error = $this->language->lang('PRIVATE_MESSAGE');
				}

				// only send an email if the board allows it
				if ($this->config['email_enable'])
				{
					// send an email to the board default
					$email_template = '@rmcgirr83_contactadmin/contact_error';
					$email_message = $this->language->lang('CONTACT_NONE', $this->user->data['username'], $this->config['sitename'], $error, $server_url);

					$this->contact_send_email($email_template, $email_message);

					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_NONE',  time(), [$error]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_ERROR', '<br /><br />' . $this->language->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">', '</a>'));

					throw new http_exception(503, $message);
				}
				else
				{
					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_NONE',  time(), [$error]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_DISABLED', '<a href="mailto:' . $this->config['board_contact'] . '">', '</a>');

					throw new http_exception(503, $message);
				}
			break;
		}
		return;
	}

	/**
	 * contact_send_email
	 * @param $email_template	the email template to use
	 * @param $email_message	the message we are sending
	 * sends an email to the board default if an error occurs
	 */
	private function contact_send_email($email_template, $email_message)
	{
		$dir_array = $this->dir_to_array($this->root_path .'ext/rmcgirr83/contactadmin/language');

		$lang = (in_array($this->config['default_lang'], $dir_array)) ? $this->config['default_lang'] : 'en';
		// don't use the queue send the email immediately if not sooner
		$messenger = new \messenger(false);

		// Email headers
		$messenger->headers('X-AntiAbuse: Board servername - ' . $this->config['server_name']);
		$messenger->headers('X-AntiAbuse: User_id - ' . $this->user->data['user_id']);
		$messenger->headers('X-AntiAbuse: Username - ' . $this->user->data['username']);
		$messenger->headers('X-AntiAbuse: User IP - ' . $this->user->ip);

		$messenger->template($email_template, $lang);
		$messenger->to($this->config['board_email']);
		$messenger->from($this->config['server_name']);

		$messenger->assign_vars([
			'SUBJECT'		=> $this->language->lang('CONTACT_BOT_SUBJECT'),
			'EMAIL_SIG'  	=> $this->config['board_email_sig'],
			'MESSAGE'		=> $email_message,
		]);

		$messenger->send(NOTIFY_EMAIL);

		return;
	}

	/**
	 * contact_make_select
	 *
	 * @param array 	$input_ary	an array of contact reasons
	 * @param string	$selected	the chosen reason
	 * @return string Select html
	 * for drop down reasons in the contact page
	 */
	public function contact_make_select($input_ary, $selected)
	{
		// only accept arrays, no empty ones
		if (!is_array($input_ary) || !count($input_ary))
		{
			return false;
		}

		// If selected isn't in the array, use first entry
		if (!in_array($selected, $input_ary))
		{
			$selected = $input_ary[0];
		}

		$select = '';
		foreach ($input_ary as $item)
		{
			//$item = htmlspecialchars($item);
			$item_selected = ($item == $selected) ? ' selected="selected"' : '';
			$select .= '<option value="' . $item . '"' . $item_selected . '>' . $item . '</option>';
		}
		return $select;
	}

	/**
	 * make_user_select
	 * @return string User List html
	 * for drop down when selecting the contact bot
	 */
	public function make_user_select($select_id = false)
	{
		// variables
		$user_list = '';

		// groups we ignore for the dropdown
		$groups = [USER_IGNORE, USER_INACTIVE];

		// do the main sql query
		$sql = 'SELECT user_id, username
			FROM ' . USERS_TABLE . '
			WHERE ' . $this->db->sql_in_set('user_type', $groups, true) . '
			ORDER BY username_clean';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$selected = ($row['user_id'] == $select_id) ? ' selected="selected"' : '';
			$user_list .= '<option value="' . $row['user_id'] . '"' . $selected . '>' . $row['username'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		return $user_list;
	}
	/**
	 * Create the selection for the contact method
	 */
	public function method_select($value, $key = '')
	{
		if ($this->config['email_enable'])
		{
			$radio_array = [
				contact_constants::CONTACT_METHOD_EMAIL	=> 'CONTACT_METHOD_EMAIL',
				contact_constants::CONTACT_METHOD_POST	=> 'CONTACT_METHOD_POST',
				contact_constants::CONTACT_METHOD_PM	=> 'CONTACT_METHOD_PM',
				contact_constants::CONTACT_METHOD_BOARD_DEFAULT	=> 'CONTACT_METHOD_BOARD_DEFAULT',
			];
		}
		else
		{
			$radio_array = [
				contact_constants::CONTACT_METHOD_POST	=> 'CONTACT_METHOD_POST',
				contact_constants::CONTACT_METHOD_PM	=> 'CONTACT_METHOD_PM',
			];
		}

		$radio_ary = $radio_array;
		return h_radio('contact_method', $radio_ary, $value, $key);
	}
	/**
	 * Create the selection for the post method
	 */
	public function poster_select($value, $key = '')
	{
		$radio_ary = [
			contact_constants::CONTACT_POST_NEITHER	=> 'CONTACT_POST_NEITHER',
			contact_constants::CONTACT_POST_GUEST	=> 'CONTACT_POST_GUEST',
			contact_constants::CONTACT_POST_ALL		=> 'CONTACT_POST_ALL',
		];

		return h_radio('contact_bot_poster', $radio_ary, $value, $key);
	}
	/**
	 * Create the selection for the bot forum
	 */
	public function forum_select($value)
	{
		return '<select id="contact_forum" name="forum">' . make_forum_select($value, false, true, true) . '</select>';
	}
	/**
	 * Create the selection for the bot
	 */
	public function bot_user_select($value)
	{
		return '<select id="contact_bot_user" name="bot_user">' . $this->make_user_select($value) . '</select>';
	}

	/**
	* get an array of admins
	*/
	public function admin_array()
	{
		$sql_where = '';
		$contact_users = [];

		// board default email
		if ($this->config['contactadmin_method'] == contact_constants::CONTACT_METHOD_BOARD_DEFAULT)
		{
			$contact_users[] = [
				'username'	=> !empty($this->config['board_contact_name']) ? $this->config['board_contact_name'] : $this->config['sitename'],
				'user_email'	=> $this->config['board_contact'],
				'user_jabber'	=> '',
				'user_lang'		=> $this->config['default_lang'],
				'user_notify_type'	=> 0,
			];

			return $contact_users;
		}

		// Only founders...maybe
		if ($this->config['contactadmin_founder_only'])
		{
			$sql_where .= ' WHERE user_type = ' . USER_FOUNDER;
		}
		else
		{
			// Grab an array of user_id's with admin permissions
			$admin_ary = $this->auth->acl_get_list(false, 'a_', false);
			$admin_ary = (!empty($admin_ary[0]['a_'])) ? $admin_ary[0]['a_'] : [];

			if ($this->config['contactadmin_method'] == contact_constants::CONTACT_METHOD_EMAIL && count($admin_ary))
			{
				$sql_where .= ' WHERE ' . $this->db->sql_in_set('user_id', $admin_ary) . ' AND user_allow_viewemail = 1';
			}
			else if ($this->config['contactadmin_method'] == contact_constants::CONTACT_METHOD_PM && count($admin_ary))
			{
				$sql_where .= ' WHERE ' . $this->db->sql_in_set('user_id', $admin_ary) . ' AND user_allow_pm = 1';
			}
		}

		if (!empty($sql_where))
		{
			$sql = 'SELECT user_id, username, user_email, user_lang, user_jabber, user_notify_type
				FROM ' . USERS_TABLE . ' ' .
				$sql_where;
			$result = $this->db->sql_query($sql);
			$contact_users = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);
		}

		// we didn't get a soul
		if (!count($contact_users))
		{
			// we have no one to send anything to
			// notify the board default
			$this->contact_check('contact_nobody', false, false, (int) $this->config['contactadmin_method']);
		}

		return $contact_users;
	}

	/**
	* bot_user_info					used in the ACP when choosing the "bot"
	*
	* @param user_id				user id
	* @return array					array of user info or error if not found
	* @access public
	*/
	public function bot_user_info($user_id)
	{
		$bot_user_info = [];

		$sql = 'SELECT user_id, username
			FROM ' . USERS_TABLE . "
			WHERE user_id = " . (int) $user_id . ' AND user_type <> ' . USER_IGNORE;
		$result = $this->db->sql_query($sql);
		$bot_user_info = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!isset($bot_user_info['username']))
		{
			$bot_user_info['error'] = $this->language->lang('CONTACT_NO_BOT_USER');
		}

		if ($this->request->is_ajax())
		{
			if (!isset($bot_user_info['username']))
			{
				$json = new JsonResponse(array(
					'error'     => true,
				));
			}
			else
			{
				$json = new JsonResponse(array(
					'user_link'     => '<a href="' . append_sid("{$this->root_path}memberlist.$this->php_ext", 'mode=viewprofile&amp;u=' . $bot_user_info['user_id']) . '" target="_blank">' . $bot_user_info['username'] . '</a>',
				));
			}
			return $json;
		}

		return $bot_user_info;
	}
	/*
	* Get an array that represents directory tree
	*/
	public function dir_to_array($directory)
	{
		$directories = glob($directory . '/*' , GLOB_ONLYDIR);
		$dir_array = [];
		foreach ($directories as $key => $value)
		{
			$dir_array[] = substr(strrchr($value, '/'), 1);
		}
		return $dir_array;
	}
}
