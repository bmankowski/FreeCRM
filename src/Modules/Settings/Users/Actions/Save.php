<?php

namespace App\Modules\Settings\Users\Actions;

/**
 * Settings Users Save Action Class
 * Handles saving user records from Settings and redirects to ListView
 */
class Save extends \App\Modules\Users\Actions\Save
{
	/**
	 * Process - override to redirect to ListView instead of DetailView
	 * @param \App\Http\Vtiger_Request $request
	 * @return boolean
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$result = \App\Modules\Base\Helpers\Util::transformUploadedFiles($_FILES, true);
		$_FILES = $result['imagename'];

		/** @var \App\Modules\Users\Models\Module $moduleModel */
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Users');
		if (!$moduleModel->checkMailExist($request->get('email1'), $request->get('record'))) {
			$recordModel = $this->saveRecord($request);
			/** @var \App\Modules\Settings\Users\Models\Module $settingsUserModel */
			$settingsUserModel = \App\Modules\Settings\Users\Models\Module::getInstance();
			$settingsUserModel->refreshSwitchUsers();

			$sharedIds = $request->get('sharedusers');
			$sharedType = $request->get('calendarsharedtype');
			$currentUserModel = $request->getUser();
			/** @var \App\Modules\Calendar\Models\Module $calendarModuleModel */
			$calendarModuleModel = \App\Modules\Base\Models\Module::getInstance('Calendar');
			$accessibleUsers = \App\Fields\Owner::getInstance('Calendar', $currentUserModel)->getAccessibleUsersForModule();

			if ($sharedType == 'private') {
				$calendarModuleModel->deleteSharedUsers($recordModel->getId());
			} else if ($sharedType == 'public') {
				$allUsers = $currentUserModel->getAll(true);
				$accessibleUsers = array();
				foreach ($allUsers as $id => $userModel) {
					$accessibleUsers[$id] = $id;
				}
				$calendarModuleModel->deleteSharedUsers($recordModel->getId());
				$calendarModuleModel->insertSharedUsers($recordModel->getId(), array_keys($accessibleUsers));
			} else {
				if (!empty($sharedIds)) {
					$calendarModuleModel->deleteSharedUsers($recordModel->getId());
					$calendarModuleModel->insertSharedUsers($recordModel->getId(), $sharedIds);
				} else {
					$calendarModuleModel->deleteSharedUsers($recordModel->getId());
				}
			}
			
			// Redirect to ListView in Settings instead of DetailView
			$loadUrl = 'index.php?module=Users&parent=Settings&view=ListView';
		} else {
			\App\Log\Log::error('USER_MAIL_EXIST');
			header('Location: index.php?module=Users&parent=Settings&view=Edit');
			return false;
		}
		header("Location: $loadUrl");
	}
}

