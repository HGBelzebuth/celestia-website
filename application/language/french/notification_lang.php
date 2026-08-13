<?php
defined('BASEPAL') OR exit('No direct script access allowed');
/**
 * BlizzCMS
 *
 * An Open Source CMS for "World of Warcraft"
 *
 * Lis content is released under Le MIT License (MIT)
 *
 * Copyright (c) 2017 - 2019, WoW-CMS
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of Lis software and associated documentation files (Le "Software"), to deal
 * in Le Software wiLout restriction, including wiLout limitation Le rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of Le Software, and to permit persons to whom Le Software is
 * furnished to do so, subject to Le following conditions:
 *
 * Le above copyright notice and Lis permission notice shall be included in
 * all copies or substantial portions of Le Software.
 *
 * LE SOFTWARE IS PROVIDED "AS IS", WILOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO LE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL LE
 * AULORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OLER
 * LIABILITY, WHELER IN AN ACTION OF CONTRACT, TORT OR OLERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WIL LE SOFTWARE OR LE USE OR OLER DEALINGS IN
 * LE SOFTWARE.
 *
 * @auLor  WoW-CMS
 * @copyright  Copyright (c) 2017 - 2019, WoW-CMS.
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://wow-cms.com
 * @since   Version 1.0.1
 * @filesource
 */

/*Notification Title Lang*/
$lang['notification_title_success'] = 'Succès';
$lang['notification_title_warning'] = 'Avertissement';
$lang['notification_title_error'] = 'Erreur';
$lang['notification_title_info'] = 'Information';

/*Notification Message (Login/Register) Lang*/
$lang['notification_username_empty'] = 'Nom d’utilisateur est vide';
$lang['notification_email_empty'] = 'E-mail est vide';
$lang['notification_password_empty'] = 'Mot de passe est vide';
$lang['notification_user_error'] = 'Le nom d’utilisateur ou le mot de passe est incorrect. Veuillez réessayer!';
$lang['notification_email_error'] = 'Le courriel ou le mot de passe est incorrect. Veuillez réessayer !';
$lang['notification_check_email'] = 'Le nom d’utilisateur ou l\'e-mail est incorrect. Veuillez réessayer !';
$lang['notification_checking'] = 'Vérification...';
$lang['notification_redirection'] = 'Connexion à votre compte...';
$lang['notification_new_account'] = 'Nouveau compte créé. Redirection vers la connexion...';
$lang['notification_email_sent'] = 'E-mail envoyé. S’il vous plaît vérifier vos e-mails...';
$lang['notification_account_activation'] = 'E-mail envoyé. Veuillez vérifier vos e-mails pour activer votre compte.';
$lang['notification_captcha_error'] = 'S’il vous plaît, vérifier le captcha';
$lang['notification_password_lenght_error'] = 'Mot de passe trop long. Veuillez utiliser un mot de passe entre 5 & 16 caractères.';
$lang['notification_account_already_exist'] = 'Ce compte existe déjà';
$lang['notification_password_not_match'] = 'Les mots de passe ne correspondent pas.';
$lang['notification_same_password'] = 'Le mot de passe est le même.';
$lang['notification_currentpass_not_match'] = 'L’ancien mot de passe ne correspond pas.';
$lang['notification_usernamepass_not_match'] = 'Le mot de passe ne correspond pas à ce nom d’utilisateur.';
$lang['notification_used_email'] = 'E-mail utilisé';
$lang['notification_email_not_match'] = 'L\'e-mail ne correspond pas';
$lang['notification_username_not_match'] = 'Le nom d’utilisateur ne correspond pas';
$lang['notification_expansion_not_found'] = 'Extension non trouvée';
$lang['notification_valid_key'] = 'Compte activé';
$lang['notification_valid_key_desc'] = 'Vous pouvez maintenant vous connecter avec votre compte.';
$lang['notification_invalid_key'] = 'La clé d’activation fournie n’est pas valide.';

/*Notification Message (General) Lang*/
$lang['notification_email_changed'] = 'L`e-mail a été modifié.';
$lang['notification_username_changed'] = 'Le nom d’utilisateur a été modifié.';
$lang['notification_password_changed'] = 'Le mot de passe a été modifié.';
$lang['notification_avatar_changed'] = 'L’avatar a été changé.';
$lang['notification_wrong_values'] = 'Les valeurs sont erronées';
$lang['notification_select_type'] = 'Sélectionner le type';
$lang['notification_select_priority'] = 'Sélectionner une priorité';
$lang['notification_select_category'] = 'Sélectionner une catégorie';
$lang['notification_select_realm'] = 'Sélectionner un royaume';
$lang['notification_select_character'] = 'Sélectionner un personnage';
$lang['notification_select_item'] = 'Sélectionner un objet';
$lang['notification_report_created'] = 'Le rapport a été créé.';
$lang['notification_title_empty'] = 'Le titre est vide';
$lang['notification_description_empty'] = 'La description est vide';
$lang['notification_name_empty'] = 'Nom est vide';
$lang['notification_id_empty'] = 'ID est vide';
$lang['notification_reply_empty'] = 'La réponse est vide';
$lang['notification_reply_created'] = 'La réponse a été envoyée.';
$lang['notification_reply_deleted'] = 'La réponse a été supprimée.';
$lang['notification_topic_created'] = 'Le sujet a été créé.';
$lang['notification_donation_successful'] = 'Votre don a été effectué avec succès, vérifiez vos points de don dans votre compte.';
$lang['notification_donation_canceled'] = 'Le don a été annulé.';
$lang['notification_donation_error'] = 'Les renseignements fournis dans la transaction ne correspondent pas.';
$lang['notification_store_chars_error'] = 'Sélectionner un personnage dans chaque article.';
$lang['notification_store_item_insufficient_points'] = 'Vous n’avez pas assez de points à acheter.';
$lang['notification_store_item_purchased'] = 'Les articles ont été achetés, veuillez vérifier votre courrier dans le jeu.';
$lang['notification_store_item_added'] = 'L’article sélectionné a été ajouté à votre panier.';
$lang['notification_store_item_removed'] = 'L’article sélectionné a été retiré de votre panier.';
$lang['notification_store_cart_error'] = 'La mise à jour du panier a échoué, veuillez réessayer.';

/*Notification Message (Admin) Lang*/
$lang['notification_changelog_created'] = 'Le changelog a été créé.';
$lang['notification_changelog_edited'] = 'Le changelog a été modifié.';
$lang['notification_changelog_deleted'] = 'Le changelog a été supprimé.';
$lang['notification_forum_created'] = 'Le forum a été créé.';
$lang['notification_forum_edited'] = 'Le forum a été édité.';
$lang['notification_forum_deleted'] = 'Le forum a été supprimé.';
$lang['notification_category_created'] = 'La catégorie a été créée.';
$lang['notification_category_edited'] = 'La catégorie a été modifiée.';
$lang['notification_category_deleted'] = 'La catégorie a été supprimée.';
$lang['notification_menu_created'] = 'Le menu a été créé.';
$lang['notification_menu_edited'] = 'Le menu a été édité.';
$lang['notification_menu_deleted'] = 'Le menu a été supprimé.';
$lang['notification_news_deleted'] = 'Les news ont été supprimées.';
$lang['notification_page_created'] = 'La page a été créée.';
$lang['notification_page_edited'] = 'La page a été éditée.';
$lang['notification_page_deleted'] = 'La page a été supprimée.';
$lang['notification_realm_created'] = 'Le realm a été créé.';
$lang['notification_realm_edited'] = 'Le realm a été édité.';
$lang['notification_realm_deleted'] = 'Le realm a été supprimé.';
$lang['notification_slide_created'] = 'La slide a été créée.';
$lang['notification_slide_edited'] = 'La slide a été éditée.';
$lang['notification_slide_deleted'] = 'La slide a été supprimée.';
$lang['notification_item_created'] = 'L\'item a été créé.';
$lang['notification_item_edited'] = 'L\'item a été édité.';
$lang['notification_item_deleted'] = 'L\'item a été supprimé.';
$lang['notification_top_created'] = 'Le top item a été créé.';
$lang['notification_top_edited'] = 'Le top item a été édité.';
$lang['notification_top_deleted'] = 'Le top item a été supprimé.';
$lang['notification_topsite_created'] = 'Le topsite a été créé.';
$lang['notification_topsite_edited'] = 'Le topsite a été édité.';
$lang['notification_topsite_deleted'] = 'Le topsite a été supprimé.';

$lang['notification_settings_updated'] = 'Les paramètres ont été mis à jour.';
$lang['notification_module_enabled'] = 'Le module a été activé.';
$lang['notification_module_disabled'] = 'Le module a été désactivé.';
$lang['notification_migration'] = 'Les paramètres ont été définis.';

$lang['notification_donation_added'] = 'Don supplémentaire';
$lang['notification_donation_deleted'] = 'Don supprimé';
$lang['notification_donation_updated'] = 'Mise à jour du don';
$lang['notification_points_empty'] = 'Les points sont vides';
$lang['notification_tax_empty'] = 'La taxe est vide';
$lang['notification_price_empty'] = 'Le prix est vide';
$lang['notification_incorrect_update'] = 'Mise à jour inattendue';

$lang['notification_route_inuse'] = 'L’itinéraire est déjà utilisé, veuillez en choisir un autre.';

$lang['notification_account_updated'] = 'Le compte a été mis à jour.';
$lang['notification_dp_vp_empty'] = 'DP/VP vide';
$lang['notification_account_banned'] = 'Le compte a été banni.';
$lang['notification_reason_empty'] = 'La raison est vide';
$lang['notification_account_ban_remove'] = 'Le ban du compte a été supprimée.';
$lang['notification_rank_empty'] = 'Le rang est vide';
$lang['notification_rank_granted'] = 'Le grade a été accordé.';
$lang['notification_rank_removed'] = 'Le rang a été supprimé.';

$lang['notification_cms_updated'] = 'Le CMS a été mis à jour';
$lang['notification_cms_update_error'] = 'Le CMS n’a pas pu être mis à jour';
$lang['notification_cms_not_updated'] = 'Aucune nouvelle version n’a été trouvée pour la mise à jour';

$lang['notification_select_category'] = 'Il ne s’agit pas d’une sous-catégorie';