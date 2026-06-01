<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\OSSPasswords\Views;

use App\Http\Vtiger_Request;

class Edit extends \App\Modules\Base\Views\Edit
{

	public function getFooterScripts(Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);

		$jsFileNames = [
			'modules.OSSPasswords.resources.gen_pass',
			'libraries.jquery.clipboardjs.clipboard',
			'modules.OSSPasswords.resources.zClipDetailView',
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($jsScriptInstances, $headerScriptInstances);
	}

	public function process(Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$relatedModule = 'OSSPasswords';

		if (file_exists('src/Modules/OSSPasswords/config.ini')) {
			$config = parse_ini_file('src/Modules/OSSPasswords/config.ini');
			$viewer->assign('ENCRYPTED', true);
			$viewer->assign('ENC_KEY', $config['key']);
		} else {
			$viewer->assign('ENCRYPTED', false);
			$viewer->assign('ENC_KEY', '');
		}

		$passwordConfig = (new \App\Db\Query())->from('vtiger_passwords_config')->one();

		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('GENERATEPASS', 'Generate Password');
		$viewer->assign('VALIDATE_STRINGS', \App\Runtime\Vtiger_Language_Handler::translate('Very Weak', $relatedModule) . ',' . \App\Runtime\Vtiger_Language_Handler::translate('Weak', $relatedModule) . ',' . \App\Runtime\Vtiger_Language_Handler::translate('Better', $relatedModule) . ',' .
			\App\Runtime\Vtiger_Language_Handler::translate('Medium', $relatedModule) . ',' . \App\Runtime\Vtiger_Language_Handler::translate('Strong', $relatedModule) . ',' . \App\Runtime\Vtiger_Language_Handler::translate('Very Strong', $relatedModule));
		$viewer->assign('passLengthMin', $passwordConfig['pass_length_min']);
		$viewer->assign('passLengthMax', $passwordConfig['pass_length_max']);
		$viewer->assign('allowChars', $passwordConfig['pass_allow_chars']);

		parent::process($request);
	}
}
