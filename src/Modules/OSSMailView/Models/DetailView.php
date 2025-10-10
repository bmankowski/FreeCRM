<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace FreeCRM\Modules\OSSMailView\Models;

class DetailView extends \FreeCRM\Modules\Vtiger\Models\DetailView
{

	public function getDetailViewLinks($linkParams)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$recordModel = $this->getRecord();
		$linkModelList = parent::getDetailViewLinks($linkParams);
		unset($linkModelList['DETAILVIEWBASIC']);

		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission('OSSMail');
		if ($permission && \FreeCRM\AppConfig::main('isActiveSendingMails') && \FreeCRM\Modules\Users\Models\Privileges::isPermitted('OSSMail')) {
			$recordId = $recordModel->getId();
			if ($currentUserModel->get('internal_mailer') == 1) {
				$config = \FreeCRM\Modules\OSSMail\Models\Module::getComposeParameters();
				$url = \FreeCRM\Modules\OSSMail\Models\Module::getComposeUrl();

				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => '',
					'linkhint' => 'LBL_REPLY',
					'linkdata' => ['url' => $url . '&mid=' . $recordId . '&type=reply', 'popup' => $config['popup']],
					'linkimg' => Yeti_Layout::getLayoutFile('src/Modules/OSSMailView/previewReply.png'),
					'linkclass' => 'sendMailBtn'
				];
				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => '',
					'linkhint' => 'LBL_REPLYALLL',
					'linkdata' => ['url' => $url . '&mid=' . $recordId . '&type=replyAll', 'popup' => $config['popup']],
					'linkimg' => Yeti_Layout::getLayoutFile('src/Modules/OSSMailView/previewReplyAll.png'),
					'linkclass' => 'sendMailBtn'
				];
				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => '',
					'linkhint' => 'LBL_FORWARD',
					'linkdata' => ['url' => $url . '&mid=' . $recordId . '&type=forward', 'popup' => $config['popup']],
					'linkicon' => 'glyphicon glyphicon-share-alt',
					'linkclass' => 'sendMailBtn'
				];
			} else {
				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linkhref' => true,
					'linklabel' => '',
					'linkhint' => 'LBL_REPLY',
					'linkurl' => \FreeCRM\Modules\OSSMail\Models\Module::getExternalUrlForWidget($recordModel, 'reply'),
					'linkimg' => Yeti_Layout::getLayoutFile('src/Modules/OSSMailView/previewReply.png'),
					'linkclass' => 'sendMailBtn'
				];
				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linkhref' => true,
					'linklabel' => '',
					'linkhint' => 'LBL_REPLYALLL',
					'linkurl' => \FreeCRM\Modules\OSSMail\Models\Module::getExternalUrlForWidget($recordModel, 'replyAll'),
					'linkimg' => Yeti_Layout::getLayoutFile('src/Modules/OSSMailView/previewReplyAll.png'),
					'linkclass' => 'sendMailBtn'
				];
				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linkhref' => true,
					'linklabel' => '',
					'linkhint' => 'LBL_FORWARD',
					'linkurl' => \FreeCRM\Modules\OSSMail\Models\Module::getExternalUrlForWidget($recordModel, 'forward'),
					'linkicon' => 'glyphicon glyphicon-share-alt',
					'linkclass' => 'sendMailBtn'
				];
			}

			if (\FreeCRM\Modules\Users\Models\Privileges::isPermitted('OSSMailView', 'PrintMail')) {
				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => '',
					'linkhint' => 'LBL_PRINT',
					'linkurl' => 'javascript:OSSMailView_Detail_Js.printMail();',
					'linkicon' => 'glyphicon glyphicon-print'
				];
			}
			foreach ($detailViewLinks as $detailViewLink) {
				$linkModelList['DETAILVIEWBASIC'][] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($detailViewLink);
			}
		}
		$linkModelDetailViewList = $linkModelList['DETAILVIEW'];
		$countOfList = count($linkModelDetailViewList);
		for ($i = 0; $i < $countOfList; $i++) {
			$linkModel = $linkModelDetailViewList[$i];
			if ($linkModel->get('linklabel') == 'LBL_DUPLICATE') {
				unset($linkModelList['DETAILVIEW'][$i]);
				break;
			}
		}
		return $linkModelList;
	}
}
