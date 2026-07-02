<?php

namespace App\Modules\Base\UiTypes;

/**
 * UIType MultiImage Field Class
 * @package YetiForce.UIType
 * @license licenses/License.html
 * @author Michał Lorencik <m.lorencik@titantech.pl>
 * @author Radosław Skrzypczak <r.skrzypczak@titantech.pl>
 */

/**
 * UIType MultiImage Field Class
 */
class MultiImage extends BaseUiType
{

	/**
	 * If the field is editable by ajax
	 * @return boolean
	 */
	public function isAjaxEditable()
	{
		return false;
	}

	/**
	 * If the field is active in search
	 * @return boolean
	 */
	public function isActiveSearchView()
	{
		return false;
	}

	/**
	 * If the field is sortable in ListView
	 * @return boolean
	 */
	public function isListviewSortable()
	{
		return false;
	}

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/MultiImage.tpl';
	}

	/**
	 * Function to get the Display Value
	 * @param string $value
	 * @param int $recordId
	 * @param \App\Modules\Base\Models\Record $recordInstance
	 * @param bool $noLimit
	 * @return string
	 */
	public function getDisplayValue($value, $recordId = false, $recordInstance = false, $noLimit = false)
	{
		$imageIcons = '<div class="multiImageContenDiv">';
		if ($recordId) {
			if (!\App\Core\AppConfig::performance('ICON_MULTIIMAGE_VIEW')) {
				$images = $this->getMultiImageQuery($value, ['name'], false)->column('name');
				return implode(', ', $images);
			}
			$images = $this->getMultiImageQuery($value, [], $noLimit);
			foreach ($images->all() as $attach) {
				$imageIcons .= '<div class="contentImage" title="' . $attach['name'] . '">'
					. '<button type="button" class="btn btn-sm btn-default imageFullModal hide"><span class="glyphicon glyphicon-fullscreen"></span></button>'
					. '<img src="' . $this->getImageWebUrl($attach['attachmentid'], $recordId) . '" class="multiImageListIcon"></div>';
			}
		}
		$imageIcons .= '</div>';
		return $imageIcons;
	}

	public function getImageWebUrl($value, $recordId): string
	{
		$field = $this->getFieldModel();
		$moduleName = $field->getModuleName();
		return "file.php?module=$moduleName&action=Image&attachment=$value&record=$recordId&field={$field->getId()}";
	}

	/**
	 * Function to get the List Display Value
	 * @param string $value
	 * @param int $record
	 * @param \App\Modules\Base\Models\Record $recordInstance
	 * @param bool $rawText
	 * @return string
	 */
	public function getListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$images = $this->getDisplayValue($value, $record, $recordInstance, true);
		return !\App\Core\AppConfig::performance('ICON_MULTIIMAGE_VIEW') ? \vtlib\Functions:: textLength($images, $this->get('field')->get('maxlengthtext')) : $images;
	}

	/**
	 * Function to get the display value in edit view
	 * @param string $value
	 * @param int $record
	 * @return array
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		return $record ? $this->getMultiImageQuery($value, [], false)->all() : [];
	}

	/**
	 * Get query for attachments
	 * @param string $value
	 * @param array $fields
	 * @param bool $limit
	 * @return mixed
	 */
	public function getMultiImageQuery($value, $fields = [], $limit = true)
	{
		$field = $this->getFieldModel();
		$query = (new \App\Db\Query());
		if ($fields) {
			$query->select($fields);
		}
		$query->from('u_#__attachments')
			->where(['attachmentid' => explode(',', $value)]);
		if ($limit) {
			$query->limit(\App\Core\AppConfig::performance('MAX_MULTIIMAGE_VIEW'));
		}
		return $query;
	}
}
