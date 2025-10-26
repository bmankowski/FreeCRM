<?php

namespace App\Modules\Base\Dashboards;

/**
 * Widget to display RSS
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
use App\Http\Vtiger_Request;

class Rss  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request, $widget = NULL)
	{
		require_once 'libraries/RSSFeeds/Feed.php';
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		if ($widget && !$request->has('widgetid')) {
			$widgetId = $widget->get('id');
		} else {
			$widgetId = $request->get('widgetid');
		}
		$widget = \App\Modules\Base\Models\Widget::getInstanceWithWidgetId($widgetId, $currentUser->getId());
		$data = $widget->get('data');
		$data = \App\Json::decode(\App\Utils\ListViewUtils::decodeHtml($data));
		$listSubjects = [];
		foreach ($data['channels'] as $rss) {
			try {
				$rssContent = Feed::loadRss($rss);
			} catch (FeedException $ex) {
				continue;
			}
			if (!empty($rssContent)) {
				foreach ($rssContent->item as $item) {
					$date = new \DateTime($item->pubDate);
					$date = \App\Fields\DateTimeField::convertToUserFormat($date->format('Y-m-d H:i:s'));
					$listSubjects[] = [
						'title' => strlen($item->title) > 40 ? substr($item->title, 0, 40) . '...' : $item->title,
						'link' => $item->link,
						'date' => $date,
						'fullTitle' => $item->title,
						'source' => $rss
					];
				}
			}
		}
		$viewer->assign('LIST_SUCJECTS', $listSubjects);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/RssContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/RssHeader.tpl', $moduleName);
		}
	}
}
