<?php
/**
* ownCloud - user notifications
*
* @author Florian Hülsmann
* @copyright 2012 Florian Hülsmann <fh@cbix.de>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

require_once('3rdparty/feedcreator/feedcreator.class.php');
OCP\App::checkAppEnabled('notify');
if($path_info == '/notify_feed/feed.rss') {
	$type = 'RSS2.0';
} else if($path_info == '/notify_feed/feed.atom') {
	$type = 'ATOM1.0';
} else {
	header('HTTP/1.0 404 Not Found');
	exit;
}
if(!isset($_SERVER["PHP_AUTH_USER"]) or !OCP\User::checkPassword($uid = $_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])) {
	header('WWW-Authenticate: Basic realm="ownCloud Login"');
	header('HTTP/1.0 401 Unauthorized');
	exit;
}
$lang = OC_Preferences::getValue($uid, 'core', 'lang', OC_L10N::findLanguage());
$l = OC_L10N::get('notify', $lang);
$notifications = OC_Notify::getNotifications($uid, 50, $lang);
$baseAddress = (isset($_SERVER["HTTPS"]) ? 'https://' : 'http://') . $_SERVER["SERVER_NAME"];
$rssURI = $baseAddress . $baseuri . 'feed.rss';
$atomURI = $baseAddress . $baseuri . 'feed.atom';

$feed = new UniversalFeedCreator();
$feed->title = $l->t('ownCloud notifications');
$feed->description = $l->t('ownCloud notification stream of the user "%s".', array($uid));
$feed->link = $baseAddress . OC::$WEBROOT;
$feed->syndicationURL = $baseAddress . $_SERVER["PHP_SELF"];

$feed->image = new FeedImage();
$feed->image->title = 'ownCloud';
$feed->image->url = $baseAddress . OCP\Util::imagePath('core', 'logo-inverted.png');
$feed->image->link = $feed->link;

foreach($notifications as $notification) {
	$item = new FeedItem();
	$item->title = strip_tags($notification["content"]);
	$item->date = strtotime($notification["moment"]);
	if(preg_match('/^https?:\/\//', $notification["href"])) {
		$item->link = $notification["href"];
	} else if(strpos($notification["href"], "/") === 0) {
		$item->link = $baseAddress . $notification["href"];
	} else {
		$item->link = $feed->link;
	}
	$item->description = $notification["content"];
	$item->author = "ownCloud (" . $notification["appid"] . " app)";
	$feed->addItem($item);
}
$feed->outputFeed($type);