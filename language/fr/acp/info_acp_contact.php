<?php
/**
 *
 * Contact Admin. An extension for the phpBB Forum Software package.
 * French translation by Galixte (https://www.galixte.com)
 *
 * @copyright (c) 2018 Rich McGirr (RMcGirr83)
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ « » “ ” …
//

$lang = array_merge($lang, [
	// General config options
	'ACP_CAT_CONTACTADMIN'	=> 'Formulaire de contact du forum',
	'ACP_CONTACTADMIN_CONFIG'	=> 'Paramètres',
	'FORUM_EMAIL_INACTIVE'	=> 'Il n’est pas possible aux membres de contacter les administrateurs du forum.<br><span style="color:red;">Envoyer des e-mails n’est pas autorisé sur le forum, merci d’activer cette fonctionnalité.</span>.',
	'NO_FORUM_ATTACHMENTS'		=> 'Il n’est pas possible aux membres de joindre des fichiers pour contacter les administrateurs.<br><span style="color:red;">Joindre des fichiers n’est pas autorisé sur le forum, merci d’activer cette fonctionnalité.</span>',
	// Log entries
	'LOG_CONFIG_CONTACT_ADMIN'		=> '<strong>Paramètres de l’extension « Formulaire de contact du forum » mis à jour</strong>',
	'LOG_CONTACT_BOT_INVALID'		=> '<strong>L’ID utilisateur sélectionné dans l’extension « Formulaire de contact du forum » incorrect :</strong><br />ID utilisateur : %1$s',
	'LOG_CONTACT_FORUM_INVALID'		=> '<strong>L’ID du forum sélectionné dans l’extension « Formulaire de contact du forum » est incorrect :</strong><br />ID du forum : %1$s',
	'LOG_CONTACT_EMAIL_INVALID'		=> '<strong>L’extension « Formulaire de contact du forum » est configurée pour envoyer des e-mails alors que le forum n’autorise pas l’envoi de ces derniers. En conséquence de quoi l’extension a été désactivée',
	'LOG_CONTACT_NONE'				=> '<strong>Aucun administrateur n’a permis aux utilisateurs de les contacter par l’intermédiaire de %1$s depuis le formulaire de contact de l’extension « Formulaire de contact du forum »</strong>',
	'LOG_CONTACT_CONFIG_UPDATE'		=> '<strong>Paramètres de l’extension « Formulaire de contact du forum » mis à jour</strong>',
	//Donation
	'PAYPAL_IMAGE_URL'          => 'https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-pill-paypal-26px.png',
	'PAYPAL_ALT'                => 'Faire un don via PayPal',
	'BUY_ME_A_BEER_URL'         => 'https://paypal.me/RMcGirr83',
	'BUY_ME_A_BEER'				=> 'Offrir une boisson à l’auteur de cette extension',
	'BUY_ME_A_BEER_SHORT'		=> 'Faire une don pour cette extension',
	'BUY_ME_A_BEER_EXPLAIN'		=> 'Cette extension est entièrement gratuite. Afin d’en faire bénéficier à la communauté phpBB ce projet a nécessité du temps à son auteur. Si cette extension est utile au forum sur lequel elle est installé, merci de considérer de <a href="https://paypal.me/RMcGirr83" target="_blank" rel="noreferrer noopener">faire un don</a>. Ce geste sera grandement apprécié. <i class="fa fa-smile-o" style="color:green;font-size:1.5em;" aria-hidden="true"></i>',
]);
