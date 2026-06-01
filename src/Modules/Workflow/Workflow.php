<?php

namespace App\Modules\Workflow;

class Workflow
{

	static $SCHEDULED_HOURLY = 1;
	static $SCHEDULED_DAILY = 2;
	static $SCHEDULED_WEEKLY = 3;
	static $SCHEDULED_ON_SPECIFIC_DATE = 4;
	static $SCHEDULED_MONTHLY_BY_DATE = 5;
	static $SCHEDULED_MONTHLY_BY_WEEKDAY = 6;
	static $SCHEDULED_ANNUALLY = 7;

	public function __construct()
	{
		$this->conditionStrategy = new \App\Modules\Workflow\VTJsonCondition();
	}

	public function setup($row)
	{
		$this->id = isset($row['workflow_id']) ? $row['workflow_id'] : '';
		$this->moduleName = isset($row['module_name']) ? $row['module_name'] : '';
		$this->description = isset($row['summary']) ? $row['summary'] : '';
		$this->test = isset($row['test']) ? $row['test'] : '';
		$this->executionCondition = isset($row['execution_condition']) ? $row['execution_condition'] : '';
		$this->schtypeid = isset($row['schtypeid']) ? $row['schtypeid'] : '';
		$this->schtime = isset($row['schtime']) ? $row['schtime'] : '';
		$this->schdayofmonth = isset($row['schdayofmonth']) ? $row['schdayofmonth'] : '';
		$this->schdayofweek = isset($row['schdayofweek']) ? $row['schdayofweek'] : '';
		$this->schannualdates = isset($row['schannualdates']) ? $row['schannualdates'] : '';
		if (isset($row['defaultworkflow'])) {
			$this->defaultworkflow = $row['defaultworkflow'];
		}
		$this->filtersavedinnew = isset($row['filtersavedinnew']) ? $row['filtersavedinnew'] : '';
		$this->nexttrigger_time = isset($row['nexttrigger_time']) ? $row['nexttrigger_time'] : '';
	}

	/**
	 * Evaluate
	 * @param \App\Modules\Base\Models\Record $recordModel
	 * @return boolean
	 */
	public function evaluate($recordModel)
	{
		if ($this->test == "") {
			return true;
		} else {
			$cs = $this->conditionStrategy;
			return $cs->evaluate($this->test, $recordModel);
		}
	}

	public function isCompletedForRecord($recordId)
	{
		$isExistsActivateDonce = (new \App\Db\Query())->from('com_vtiger_workflow_activatedonce')->where(['entity_id' => $recordId, 'workflow_id' => $this->id])->exists();
		$isExistsWorkflowTasks = (new \App\Db\Query())->from('com_vtiger_workflowtasks')
				->innerJoin('com_vtiger_workflowtask_queue', 'com_vtiger_workflowtasks.task_id= com_vtiger_workflowtask_queue.task_id')
				->where(['entity_id' => $recordId, 'workflow_id' => $this->id])->exists();

		if (!$isExistsActivateDonce && !$isExistsWorkflowTasks) { // Workflow not done for specified record
			return false;
		} else {
			return true;
		}
	}

	public function markAsCompletedForRecord($recordId)
	{
		\App\Db\Db::getInstance()->createCommand()
			->insert('com_vtiger_workflow_activatedonce', [
				'entity_id' => $recordId,
				'workflow_id' => $this->id
			])->execute();
	}

	/**
	 * Perform tasks
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function performTasks($recordModel, ?RelationWorkflowContext $relationContext = null)
	{

		$tm = new VTTaskManager();
		$taskQueue = new VTTaskQueue();
		$tasks = $tm->getTasksForWorkflow($this->id);
		foreach ($tasks as &$task) {
			if ($task->active) {
				if ($relationContext !== null && !RelationWorkflowRunner::isAllowedTaskClass(get_class($task))) {
					continue;
				}
				$trigger = $task->trigger;
				if ($trigger != null) {
					$delay = strtotime($recordModel->get($trigger['field'])) + $trigger['days'] * 86400;
				} else {
					$delay = 0;
				}
				if ($task->executeImmediately == true) {
					$task->doTask($recordModel, $relationContext);
				} else {
					$hasContents = $task->hasContents($recordModel);
					if ($hasContents) {
						$taskQueue->queueTask($task->id, $recordModel->getId(), $delay, $task->getContents($recordModel));
					}
				}
			}
		}
	}

	public function executionConditionAsLabel($label = null)
	{
		if ($label == null) {
			$arr = ['ON_FIRST_SAVE', 'ONCE', 'ON_EVERY_SAVE', 'ON_MODIFY', 'ON_DELETE', 'ON_SCHEDULE', 'MANUAL', 'TRIGGER', 'BLOCK_EDIT', 'ON_RELATED', 'ON_RELATION_MODIFY'];
			return $arr[$this->executionCondition - 1] ?? 'ON_RELATION_MODIFY';
		} else {
			$arr = ['ON_FIRST_SAVE' => 1, 'ONCE' => 2, 'ON_EVERY_SAVE' => 3, 'ON_MODIFY' => 4,
				'ON_DELETE' => 5, 'ON_SCHEDULE' => 6, 'MANUAL' => 7, 'TRIGGER' => 8, 'BLOCK_EDIT' => 9, 'ON_RELATED' => 10, 'ON_RELATION_MODIFY' => 11];
			$this->executionCondition = $arr[$label];
		}
	}

	public function setNextTriggerTime($time)
	{
		if ($time) {
			$db = \App\Database\PearDatabase::getInstance();
			$db->pquery("UPDATE com_vtiger_workflows SET nexttrigger_time=? WHERE workflow_id=?", array($time, $this->id));
			$this->nexttrigger_time = $time;
		}
	}

	public function getNextTriggerTimeValue()
	{
		return $this->nexttrigger_time;
	}

	public function getWFScheduleType()
	{
		return ($this->executionCondition == 6 ? $this->schtypeid : 0);
	}

	public function getWFScheduleTime()
	{
		return $this->schtime;
	}

	public function getWFScheduleDay()
	{
		return $this->schdayofmonth;
	}

	public function getWFScheduleWeek()
	{
		return $this->schdayofweek;
	}

	public function getWFScheduleAnnualDates()
	{
		return $this->schannualdates;
	}

	/**
	 * Function gets the next trigger for the workflows
	 * @global <String> $default_timezone
	 * @return mixed
	 */
	public function getNextTriggerTime()
	{
		$default_timezone = \App\Core\AppConfig::main('default_timezone');
		$admin = \App\Modules\Users\Users::getActiveAdminUser();
		$adminTimeZone = $admin->time_zone;
		@date_default_timezone_set($adminTimeZone);

		$scheduleType = $this->getWFScheduleType();
		$nextTime = null;

		if ($scheduleType == Workflow::$SCHEDULED_HOURLY) {
			$nextTime = date("Y-m-d H:i:s", strtotime("+1 hour"));
		}

		if ($scheduleType == Workflow::$SCHEDULED_DAILY) {
			$nextTime = $this->getNextTriggerTimeForDaily($this->getWFScheduleTime());
		}

		if ($scheduleType == Workflow::$SCHEDULED_WEEKLY) {
			$nextTime = $this->getNextTriggerTimeForWeekly($this->getWFScheduleWeek(), $this->getWFScheduleTime());
		}

		if ($scheduleType == Workflow::$SCHEDULED_ON_SPECIFIC_DATE) {
			$nextTime = date('Y-m-d H:i:s', strtotime('+10 year'));
		}

		if ($scheduleType == Workflow::$SCHEDULED_MONTHLY_BY_DATE) {
			$nextTime = $this->getNextTriggerTimeForMonthlyByDate($this->getWFScheduleDay(), $this->getWFScheduleTime());
		}

		if ($scheduleType == Workflow::$SCHEDULED_MONTHLY_BY_WEEKDAY) {
			$nextTime = $this->getNextTriggerTimeForMonthlyByWeekDay($this->getWFScheduleDay(), $this->getWFScheduleTime());
		}

		if ($scheduleType == Workflow::$SCHEDULED_ANNUALLY) {
			$nextTime = $this->getNextTriggerTimeForAnnualDates($this->getWFScheduleAnnualDates(), $this->getWFScheduleTime());
		}
		@date_default_timezone_set($default_timezone);
		return $nextTime;
	}

	/**
	 * get next trigger time for daily
	 * @param string $schTime
	 * @return time
	 */
	public function getNextTriggerTimeForDaily($scheduledTime)
	{
		$now = strtotime(date("Y-m-d H:i:s"));
		$todayScheduledTime = strtotime(date("Y-m-d H:i:s", strtotime($scheduledTime)));
		if ($now > $todayScheduledTime) {
			$nextTime = date("Y-m-d H:i:s", strtotime('+1 day ' . $scheduledTime));
		} else {
			$nextTime = date("Y-m-d H:i:s", $todayScheduledTime);
		}
		return $nextTime;
	}

	/**
	 * get next trigger Time For weekly
	 * @param mixed $scheduledDaysOfWeek
	 * @param string $scheduledTime
	 * @return <time>
	 */
	public function getNextTriggerTimeForWeekly($scheduledDaysOfWeek, $scheduledTime)
	{
		$weekDays = array('1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday');
		$currentTime = time();
		$currentWeekDay = date('N', $currentTime);
		if ($scheduledDaysOfWeek) {
			$scheduledDaysOfWeek = \App\Utils\Json::decode($scheduledDaysOfWeek);
			if (is_array($scheduledDaysOfWeek)) {
				// algorithm :
				//1. First sort all the weekdays(stored as 0,1,2,3 etc in db) and find the closest weekday which is greater than currentWeekDay
				//2. If found, set the next trigger date to the next weekday value in the same week.
				//3. If not found, set the trigger date to the next first value.
				$nextTriggerWeekDay = null;
				sort($scheduledDaysOfWeek);
				foreach ($scheduledDaysOfWeek as $index => $weekDay) {
					if ($weekDay == $currentWeekDay) { //if today is the weekday selected
						$scheduleWeekDayInTime = strtotime(date('Y-m-d', strtotime($weekDays[$currentWeekDay])) . ' ' . $scheduledTime);
						if ($currentTime < $scheduleWeekDayInTime) { //if the scheduled time is greater than current time, selected today
							$nextTriggerWeekDay = $weekDay;
							break;
						} else {
							//current time greater than scheduled time, get the next weekday
							if (count($scheduledDaysOfWeek) == 1) { //if only one weekday selected, then get next week
								$nextTime = date('Y-m-d', strtotime('next ' . $weekDays[$weekDay])) . ' ' . $scheduledTime;
							} else {
								$nextWeekDay = $scheduledDaysOfWeek[$index + 1]; // its the last day of the week i.e. sunday
								if (empty($nextWeekDay)) {
									$nextWeekDay = $scheduledDaysOfWeek[0];
								}
								$nextTime = date('Y-m-d', strtotime('next ' . $weekDays[$nextWeekDay])) . ' ' . $scheduledTime;
							}
						}
					} else if ($weekDay > $currentWeekDay) {
						$nextTriggerWeekDay = $weekDay;
						break;
					}
				}

				if ($nextTime == null) {
					if (!empty($nextTriggerWeekDay)) {
						$nextTime = date("Y-m-d H:i:s", strtotime($weekDays[$nextTriggerWeekDay] . ' ' . $scheduledTime));
					} else {
						$nextTime = date("Y-m-d H:i:s", strtotime($weekDays[$scheduledDaysOfWeek[0]] . ' ' . $scheduledTime));
					}
				}
			}
		}
		return $nextTime;
	}

	/**
	 * get next triggertime for monthly
	 * @param string $scheduledDayOfMonth
	 * @param string $scheduledTime
	 * @return <time>
	 */
	public function getNextTriggerTimeForMonthlyByDate($scheduledDayOfMonth, $scheduledTime)
	{
		$currentDayOfMonth = date('j', time());
		if ($scheduledDayOfMonth) {
			$scheduledDaysOfMonth = \App\Utils\Json::decode($scheduledDayOfMonth);
			if (is_array($scheduledDaysOfMonth)) {
				// algorithm :
				//1. First sort all the days in ascending order and find the closest day which is greater than currentDayOfMonth
				//2. If found, set the next trigger date to the found value which is in the same month.
				//3. If not found, set the trigger date to the next month's first selected value.
				$nextTriggerDay = null;
				sort($scheduledDaysOfMonth);
				foreach ($scheduledDaysOfMonth as $day) {
					if ($day == $currentDayOfMonth) {
						$currentTime = time();
						$schTime = strtotime($date = date('Y') . '-' . date('m') . '-' . $day . ' ' . $scheduledTime);
						if ($schTime > $currentTime) {
							$nextTriggerDay = $day;
							break;
						}
					} elseif ($day > $currentDayOfMonth) {
						$nextTriggerDay = $day;
						break;
					}
				}
				if (!empty($nextTriggerDay)) {
					$firstDayofNextMonth = date('Y:m:d H:i:s', strtotime('first day of this month'));
					$nextTime = date('Y:m:d', strtotime($firstDayofNextMonth . ' + ' . ($nextTriggerDay - 1) . ' days'));
					$nextTime = $nextTime . ' ' . $scheduledTime;
				} else {
					$firstDayofNextMonth = date('Y:m:d H:i:s', strtotime('first day of next month'));
					$nextTime = date('Y:m:d', strtotime($firstDayofNextMonth . ' + ' . ($scheduledDaysOfMonth[0] - 1) . ' days'));
					$nextTime = $nextTime . ' ' . $scheduledTime;
				}
			}
		}
		return $nextTime;
	}

	/**
	 * to get next trigger time for weekday of the month
	 * @param string $scheduledWeekDayOfMonth
	 * @param string $scheduledTime
	 * @return <time>
	 */
	public function getNextTriggerTimeForMonthlyByWeekDay($scheduledWeekDayOfMonth, $scheduledTime)
	{
		$currentTime = time();
		$currentDayOfMonth = date('j', $currentTime);
		$scheduledTime = $this->getWFScheduleTime();
		if ($scheduledWeekDayOfMonth == $currentDayOfMonth) {
			$nextTime = date("Y-m-d H:i:s", strtotime('+1 month ' . $scheduledTime));
		} else {
			$monthInFullText = date('F', $currentTime);
			$yearFullNumberic = date('Y', $currentTime);
			if ($scheduledWeekDayOfMonth < $currentDayOfMonth) {
				$nextMonth = date("Y-m-d H:i:s", strtotime('next month'));
				$monthInFullText = date('F', strtotime($nextMonth));
			}
			$nextTime = date("Y-m-d H:i:s", strtotime($scheduledWeekDayOfMonth . ' ' . $monthInFullText . ' ' . $yearFullNumberic . ' ' . $scheduledTime));
		}
		return $nextTime;
	}

	/**
	 * to get next trigger time
	 * @param mixed $annualDates
	 * @param string $scheduledTime
	 * @return <time>
	 */
	public function getNextTriggerTimeForAnnualDates($annualDates, $scheduledTime)
	{
		if ($annualDates) {
			$today = date('Y-m-d');
			$annualDates = \App\Utils\Json::decode($annualDates);
			$nextTriggerDay = null;
			// sort the dates
			sort($annualDates);
			$currentTime = time();
			$currentDayOfMonth = date('Y-m-d', $currentTime);
			foreach ($annualDates as $day) {
				if ($day == $currentDayOfMonth) {
					$schTime = strtotime($day . ' ' . $scheduledTime);
					if ($schTime > $currentTime) {
						$nextTriggerDay = $day;
						break;
					}
				} else if ($day > $today) {
					$nextTriggerDay = $day;
					break;
				}
			}
			if (!empty($nextTriggerDay)) {
				$nextTime = date('Y:m:d H:i:s', strtotime($nextTriggerDay . ' ' . $scheduledTime));
			} else {
				$nextTriggerDay = $annualDates[0];
				$nextTime = date('Y:m:d H:i:s', strtotime($nextTriggerDay . ' ' . $scheduledTime . '+1 year'));
			}
		}
		return $nextTime;
	}
}