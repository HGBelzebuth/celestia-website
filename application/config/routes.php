<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$lang = '^(en|es|bl|fr|de|ru)';

$route['default_controller'] = 'home';
$route['404_override'] = 'general/error404';
$route['translate_uri_dashes'] = FALSE;

/**
 * Website Routes
 *
*/
$route[$lang.'$'] = $route['default_controller'];
$route[$lang.'/confmigrate'] = 'home/setconfig';
$route[$lang.'/dbmigrate'] = 'home/migrateNow';
$route[$lang.'/maintenance'] = 'general/maintenance';



/*User*/
$route[$lang.'/login'] = 'user/login';
$route[$lang.'/register'] = 'user/register';
$route[$lang.'/recovery'] = 'user/recovery';
$route[$lang.'/newacc'] = 'user/newaccount';
$route[$lang.'/classicverify'] = 'user/verify1';
$route[$lang.'/bnetverify'] = 'user/verify2';
$route[$lang.'/forgotpassword'] = 'user/forgotpassword';
$route[$lang.'/activate/(:any)'] = 'user/activate/$2';
$route[$lang.'/resend_confirm']  = 'user/resend_confirm';
$route[$lang.'/logout'] = 'user/logout';
$route[$lang.'/panel'] = 'user/panel';
$route[$lang.'/settings'] = 'user/settings';
$route[$lang.'/changemail'] = 'user/newemail';
$route[$lang.'/changepass'] = 'user/newpass';
$route[$lang.'/changeavatar'] = 'user/newavatar';
$route[$lang.'/changeusername'] = 'user/newusername';

/*Vote*/
$route[$lang.'/vote'] = 'vote/index';
$route[$lang.'/vote/votenow/(:num)'] = 'vote/votenow/$2';
$route[$lang.'/vote/verifyvote/(:num)'] = 'vote/verifyvote/$2';
$route[$lang.'/vote/callbackTop100Arena'] = 'vote/callbackTop100Arena';
$route[$lang.'/vote/leaderboard_data/(:any)'] = 'vote/leaderboard_data/$2';
$route[$lang.'/vote/leaderboard_data'] = 'vote/leaderboard_data';
$route[$lang.'/vote/processrewards/(:any)/(:any)'] = 'vote/processrewards/$2/$3';
$route[$lang.'/vote/characters/(:num)'] = 'vote/characters/$2';
$route[$lang.'/vote/characters'] = 'vote/characters';
$route[$lang.'/vote/saverewardchar'] = 'vote/saverewardchar';

/*Donate*/
$route[$lang.'/donate'] = 'donate/index';
$route[$lang.'/donate/check/(:any)'] = 'donate/check/$2';
$route[$lang.'/donate/canceled'] = 'donate/canceled';

/*Download*/
$route[$lang.'/download'] = 'download/index';
$route[$lang.'/admin/download'] = 'admin/managedownload';
$route[$lang.'/admin/download/create'] = 'admin/createdownload';
$route[$lang.'/admin/download/edit/(:num)'] = 'admin/editdownload/$2';
$route[$lang.'/admin/download/add'] = 'admin/adddownload';
$route[$lang.'/admin/download/update'] = 'admin/updatedownload';
$route[$lang.'/admin/download/delete'] = 'admin/deletedownload';

/*Changelog*/
$route[$lang.'/changelogs'] = 'changelogs/index';
$route[$lang.'/changelogs/create'] = 'changelogs/create';
$route[$lang.'/changelogs/delete'] = 'changelogs/delete';

/*Bugtracker*/
$route[$lang.'/bugtracker'] = 'bugtracker/index';
$route[$lang.'/bugtracker/(:num)'] = 'bugtracker/index/$2';
$route[$lang.'/bugtracker/new'] = 'bugtracker/newreport';
$route[$lang.'/bugtracker/create'] = 'bugtracker/create';
$route[$lang.'/bugtracker/report/(:num)'] = 'bugtracker/report/$2';

/*Ticket Notify — webhook interne worldserver*/
$route[$lang.'/ticket/notify'] = 'ticket/notify';

/*Forum*/
$route[$lang.'/forum'] = 'forum/index';
$route[$lang.'/forum/category/(:num)'] = 'forum/category/$2';
$route[$lang.'/forum/topic/(:num)'] = 'forum/topic/$2';
$route[$lang.'/forum/topic/new/(:num)'] = 'forum/newtopic/$2';
$route[$lang.'/forum/topic/create'] = 'forum/addtopic';
$route[$lang.'/forum/topic/reply'] = 'forum/reply';
$route[$lang.'/forum/topic/reply/delete'] = 'forum/deletereply';

/*API Launcher*/
$route['api/news']              = 'api/news';
$route['api/online']            = 'api/online';
$route['api/login']             = 'api/login';
$route['api/logout']            = 'api/logout';
$route['api/votes']             = 'api/votes';
$route['api/vote/start']        = 'api/vote_start';
$route['api/vote/verify/(:num)']= 'api/vote_verify/$1';
$route['api/patch/secret']      = 'api/patch_secret';
$route['api/news/create']         = 'api/news_create';
$route['api/news/update']         = 'api/news_update';
$route['api/news/delete']         = 'api/news_delete';
$route['api/changelogs']          = 'api/changelogs';
$route['api/changelogs/create']   = 'api/changelogs_create';
$route['api/changelogs/update']   = 'api/changelogs_update';
$route['api/changelogs/delete']   = 'api/changelogs_delete';
$route['api/store/promo']             = 'api/store_promo';
$route['api/store/promo/reset']       = 'api/store_promo_reset';
$route['api/notifications']           = 'api/notifications';
$route['api/notifications/read']      = 'api/notifications_read';
$route['api/donate/packages']     = 'api/donate_packages';
$route['api/donate/offer']        = 'api/donate_offer';
$route['api/donate/offer/set']    = 'api/donate_offer_set';
$route['login/launcher/store/(:any)']        = 'launcher_auth/store/$1';
$route['login/launcher/donate/(:any)']       = 'launcher_auth/donate/$1';
$route[$lang.'/login/launcher/store/(:any)'] = 'launcher_auth/store/$2';
$route[$lang.'/login/launcher/donate/(:any)']= 'launcher_auth/donate/$2';
$route['login/launcher/parrainage/(:any)']        = 'launcher_auth/parrainage/$1';
$route[$lang.'/login/launcher/parrainage/(:any)'] = 'launcher_auth/parrainage/$2';
$route['login/launcher/panel/(:any)']             = 'launcher_auth/panel/$1';
$route[$lang.'/login/launcher/panel/(:any)']      = 'launcher_auth/panel/$2';
$route['login/launcher/(:any)']              = 'launcher_auth/index/$1';
$route[$lang.'/login/launcher/(:any)']       = 'launcher_auth/index/$2';

/*News*/
$route[$lang.'/news/(:num)'] = 'news/article/$2';
$route[$lang.'/news/reply'] = 'news/reply';
$route[$lang.'/news/reply/delete'] = 'news/deletereply';

/*Store*/
$route[$lang.'/store'] = 'store/index';
// ── Routes Gift (AVANT store/(:any) sinon elles seraient interceptées) ──
$route[$lang.'/store/check_gift_char'] = 'store/check_gift_char';
$route[$lang.'/store/send_gift']       = 'store/send_gift';
$route[$lang.'/store/send_multiple_gifts']  = 'store/send_multiple_gifts';
$route[$lang.'/store/apply_coupon']         = 'store/apply_coupon';
$route[$lang.'/store/remove_coupon']        = 'store/remove_coupon';
$route[$lang.'/store/history']              = 'store/history';
// ────────────────────────────────────────────────────────────────────────
$route[$lang.'/store/(:any)'] = 'store/category/$2/';
$route[$lang.'/cart'] = 'store/cart';
$route[$lang.'/cart/checkout'] = 'store/checkout';
$route[$lang.'/cart/add'] = 'store/addtocart';
$route[$lang.'/cart/delete'] = 'store/removeitem';
$route[$lang.'/cart/updatequantity'] = 'store/updatequantity';
$route[$lang.'/cart/updatecharacter'] = 'store/updatecharacter';

/*Pages*/
$route[$lang.'/page/(:any)'] = 'page/index/$2/';

/*PvP*/
$route[$lang.'/pvp'] = 'pvp/index';

// Page d'accueil des modifications
$route['(:any)/modifications']                      = 'modifications/index';
$route['modifications']                             = 'modifications/index';

// Index des raids
$route['(:any)/modifications/raids']               = 'modifications/raids';
$route['modifications/raids']                      = 'modifications/raids';

// Détail d'un raid
$route['(:any)/modifications/raid/(:any)']         = 'modifications/raid/$1/$2';
$route['modifications/raid/(:any)']                = 'modifications/raid/$1';

// Serveur
$route['(:any)/modifications/serveur']             = 'modifications/serveur';
$route['modifications/serveur']                    = 'modifications/serveur';

// Commandes
$route['(:any)/modifications/serveur/commandes']   = 'modifications/commandes';
$route['modifications/serveur/commandes']          = 'modifications/commandes';

// Taux
$route['(:any)/modifications/serveur/taux']        = 'modifications/taux';
$route['modifications/serveur/taux']               = 'modifications/taux';

// Informations
$route['(:any)/modifications/serveur/informations'] = 'modifications/informations';
$route['modifications/serveur/informations']        = 'modifications/informations';

/*Armory*/
$route[$lang.'/armory'] = 'armory/search';
$route[$lang.'/armory/search'] = 'armory/search';
$route[$lang.'/armory/result'] = 'armory/result';
$route[$lang.'/armory/player/(:num)'] = 'armory/index/$2';
$route[$lang.'/armory/guild/(:num)'] = 'armory/guild/$2';
$route[$lang.'/armory/icon/(:num)/(:num)'] = 'armory/icon/$2/$3';
$route[$lang.'/armory/tooltip/(:num)'] = 'armory/tooltip/$2';
$route[$lang.'/armory/item_data/(:num)/(:num)'] = 'armory/item_data/$2/$3';

/*Online*/
$route[$lang.'/online'] = 'online/index';

/**
 * Mod Routes
 *
*/
$route[$lang.'/mod'] = 'mod/index';
$route[$lang.'/mod/queue'] = 'mod/queue';
$route[$lang.'/mod/reports'] = 'mod/reports';
$route[$lang.'/mod/logs'] = 'mod/logs';
$route[$lang.'/mod/bannings'] = 'mod/bannings';
$route[$lang.'/mod/warnings'] = 'mod/warnings';

/**
 * Admin Routes
 *
*/
$route[$lang.'/admin'] = 'admin/index';
$route[$lang.'/admin/cms'] = 'admin/cmsmanage';
$route[$lang.'/admin/cms/update'] = 'admin/updatecms';
$route[$lang.'/admin/settings'] = 'admin/settings';
$route[$lang.'/admin/settings/update'] = 'admin/updatesettings';
$route[$lang.'/admin/settings/module'] = 'admin/modulesettings';
$route[$lang.'/admin/settings/module/updonate'] = 'admin/updatedonatesettings';
$route[$lang.'/admin/settings/module/upbugtracker'] = 'admin/updatebugtrackersettings';
$route[$lang.'/admin/settings/optional'] = 'admin/optionalsettings';
$route[$lang.'/admin/settings/optional/update'] = 'admin/updateoptionalsettings';
$route[$lang.'/admin/settings/seo'] = 'admin/seosettings';
$route[$lang.'/admin/settings/seo/update'] = 'admin/updateseosettings';
$route[$lang.'/admin/modules'] = 'admin/managemodules';
$route[$lang.'/admin/modules/enable'] = 'admin/enablemodule';
$route[$lang.'/admin/modules/disable'] = 'admin/disablemodule';

/*Manage Accounts*/
$route[$lang.'/admin/accounts'] = 'admin/accounts';
$route[$lang.'/admin/accounts/(:num)'] = 'admin/accounts/$2';
$route[$lang.'/admin/account/manage/(:num)'] = 'admin/accountmanage/$2';
$route[$lang.'/admin/account/dlogs/(:num)'] = 'admin/accountdonatelogs/$2';
$route[$lang.'/admin/account/update'] = 'admin/updateaccount';
$route[$lang.'/admin/account/ban'] = 'admin/banaccount';
$route[$lang.'/admin/account/unban'] = 'admin/unbanaccount';
$route[$lang.'/admin/account/grantrank'] = 'admin/grantrankaccount';
$route[$lang.'/admin/account/delrank'] = 'admin/delrankaccount';

/*Menu*/
$route[$lang.'/admin/menu'] = 'admin/managemenu';
$route[$lang.'/admin/menu/create'] = 'admin/createmenu';
$route[$lang.'/admin/menu/edit/(:num)'] = 'admin/editmenu/$2';
$route[$lang.'/admin/menu/add'] = 'admin/addmenu';
$route[$lang.'/admin/menu/update'] = 'admin/updatemenu';
$route[$lang.'/admin/menu/delete'] = 'admin/deletemenu';

/*Realms*/
$route[$lang.'/admin/realms'] = 'admin/managerealms';
$route[$lang.'/admin/realms/(:num)'] = 'admin/managerealms/$2';
$route[$lang.'/admin/realms/create'] = 'admin/createrealm';
$route[$lang.'/admin/realms/edit/(:num)'] = 'admin/editrealm/$2';
$route[$lang.'/admin/realms/add'] = 'admin/addrealm';
$route[$lang.'/admin/realms/update'] = 'admin/updaterealm';
$route[$lang.'/admin/realms/delete'] = 'admin/deleterealm';

/*Slides*/
$route[$lang.'/admin/slides'] = 'admin/manageslides';
$route[$lang.'/admin/slides/(:num)'] = 'admin/manageslides/$2';
$route[$lang.'/admin/slides/create'] = 'admin/createslide';
$route[$lang.'/admin/slides/edit/(:num)'] = 'admin/editslide/$2';
$route[$lang.'/admin/slides/add'] = 'admin/addslide';
$route[$lang.'/admin/slides/update'] = 'admin/updateslide';
$route[$lang.'/admin/slides/delete'] = 'admin/deleteslide';

/*News*/
$route[$lang.'/admin/news'] = 'admin/managenews';
$route[$lang.'/admin/news/(:num)'] = 'admin/managenews/$2';
$route[$lang.'/admin/news/create'] = 'admin/createnews';
$route[$lang.'/admin/news/edit/(:num)'] = 'admin/editnews/$2';
$route[$lang.'/admin/news/delete'] = 'admin/deletenews';

/*Changelog*/
$route[$lang.'/admin/changelogs'] = 'admin/managechangelogs';
$route[$lang.'/admin/changelogs/(:num)'] = 'admin/managechangelogs/$2';
$route[$lang.'/admin/changelogs/create'] = 'admin/createchangelog';
$route[$lang.'/admin/changelogs/edit/(:num)'] = 'admin/editchangelog/$2';
$route[$lang.'/admin/changelogs/add'] = 'admin/addchangelog';
$route[$lang.'/admin/changelogs/update'] = 'admin/updatechangelog';
$route[$lang.'/admin/changelogs/delete'] = 'admin/deletechangelog';

/*Pages*/
$route[$lang.'/admin/pages'] = 'admin/managepages';
$route[$lang.'/admin/pages/(:num)'] = 'admin/managepages/$2';
$route[$lang.'/admin/pages/create'] = 'admin/createpage';
$route[$lang.'/admin/pages/edit/(:num)'] = 'admin/editpage/$2';
$route[$lang.'/admin/pages/add'] = 'admin/addpage';
$route[$lang.'/admin/pages/update'] = 'admin/updatepage';
$route[$lang.'/admin/pages/delete'] = 'admin/deletepage';

/*Store*/
$route[$lang.'/admin/store'] = 'admin/managestore';
$route[$lang.'/admin/store/(:num)'] = 'admin/managestore/$2';
$route[$lang.'/admin/store/items'] = 'admin/managestoreitems';
$route[$lang.'/admin/store/items/(:num)'] = 'admin/managestoreitems/$2';
$route[$lang.'/admin/store/top'] = 'admin/managestoretop';
$route[$lang.'/admin/store/top/(:num)'] = 'admin/managestoretop/$2';
$route[$lang.'/admin/store/category/create'] = 'admin/createstorecategory';
$route[$lang.'/admin/store/category/edit/(:num)'] = 'admin/editstorecategory/$2';
$route[$lang.'/admin/store/category/add'] = 'admin/addstorecategory';
$route[$lang.'/admin/store/category/update'] = 'admin/updatestorecategory';
$route[$lang.'/admin/store/category/delete'] = 'admin/deletestorecategory';
$route[$lang.'/admin/store/item/create'] = 'admin/createstoreitem';
$route[$lang.'/admin/store/item/edit/(:num)'] = 'admin/editstoreitem/$2';
$route[$lang.'/admin/store/item/add'] = 'admin/addstoreitem';
$route[$lang.'/admin/store/item/update'] = 'admin/updatestoreitem';
$route[$lang.'/admin/store/item/delete'] = 'admin/deletestoreitem';
$route[$lang.'/admin/store/top/create'] = 'admin/createstoretop';
$route[$lang.'/admin/store/top/edit/(:num)'] = 'admin/editstoretop/$2';
$route[$lang.'/admin/store/top/add'] = 'admin/addstoretop';
$route[$lang.'/admin/store/top/update'] = 'admin/updatestoretop';
$route[$lang.'/admin/store/top/delete'] = 'admin/deletestoretop';
$route[$lang.'/admin/store/promo']               = 'admin/managepromo';
$route[$lang.'/admin/store/promo/settings/save'] = 'admin/updatepromosettings';
$route[$lang.'/admin/store/promo/toggle']        = 'admin/updatepromotoggle';
$route[$lang.'/admin/store/promo/refresh']       = 'admin/forcepromorefresh';

/*Donate*/
$route[$lang.'/admin/donate'] = 'admin/donate';
$route[$lang.'/admin/donate/create'] = 'admin/createdonateplan';
$route[$lang.'/admin/donate/edit/(:num)'] = 'admin/editdonateplan/$2';
$route[$lang.'/admin/donate/add'] = 'admin/adddonateplan';
$route[$lang.'/admin/donate/update'] = 'admin/updatedonateplan';
$route[$lang.'/admin/donate/delete'] = 'admin/deletedonateplan';

/*Topsites*/
$route[$lang.'/admin/topsites'] = 'admin/managetopsites';
$route[$lang.'/admin/topsites/(:num)'] = 'admin/managetopsites/$2';
$route[$lang.'/admin/topsites/create'] = 'admin/createtopsite';
$route[$lang.'/admin/topsites/edit/(:num)'] = 'admin/edittopsite/$2';
$route[$lang.'/admin/topsites/add'] = 'admin/addtopsite';
$route[$lang.'/admin/topsites/update'] = 'admin/updatetopsite';
$route[$lang.'/admin/topsites/delete'] = 'admin/deletetopsite';

/*Forum*/
$route[$lang.'/admin/forum'] = 'admin/manageforum';
$route[$lang.'/admin/forum/(:num)'] = 'admin/manageforum/$2';
$route[$lang.'/admin/forum/elements'] = 'admin/manageforumelements';
$route[$lang.'/admin/forum/elements/(:num)'] = 'admin/manageforumelements/$2';
$route[$lang.'/admin/forum/create'] = 'admin/createforum';
$route[$lang.'/admin/forum/edit/(:num)'] = 'admin/editforum/$2';
$route[$lang.'/admin/forum/add'] = 'admin/addforum';
$route[$lang.'/admin/forum/update'] = 'admin/updateforum';
$route[$lang.'/admin/forum/delete'] = 'admin/deleteforum';
$route[$lang.'/admin/forum/category/create'] = 'admin/createforumcategory';
$route[$lang.'/admin/forum/category/edit/(:num)'] = 'admin/editforumcategory/$2';
$route[$lang.'/admin/forum/category/add'] = 'admin/addforumcategory';
$route[$lang.'/admin/forum/category/update'] = 'admin/updateforumcategory';
$route[$lang.'/admin/forum/category/delete'] = 'admin/deleteforumcategory';

/*Check*/
$route[$lang.'/admin/checksoap'] = 'admin/checkSoap';

// WMMV Proxy
$route[$lang.'/armory/wmmv/proxy/(.+)'] = 'armory/wmmv/proxy';
