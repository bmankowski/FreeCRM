<?php

namespace FreeCRM\Modules\Announcements\Models;

/**
 * Announcements DetailView Model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class DetailView extends Model
{

	/**
	 * Function to get the detail view related links
	 * @return <array> - list of links parameters
	 */
	public function getDetailViewRelatedLinks()
	{
		$recordModel = $this->getRecord();
		$relatedLinks = parent::getDetailViewRelatedLinks();

		$relatedLinks[] = [
			'linktype' => 'DETAILVIEWTAB',
			'linklabel' => 'LBL_USERS',
			'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showUsers',
			'linkicon' => 'glyphicon-user',
			'linkKey' => 'LBL_USERS',
			'related' => 'Users'
		];
		return $relatedLinks;
	}
}
