<?php

namespace App\Modules\Calendar;

class Layout
{

	public $view = 'day';
	public $start_time;
	public $end_time;
	public $activities = Array();

	/**
	 * Constructor for Layout class
	 * @param  string   $view - calendarview
	 * @param  string   $time - time string 
	 */
	public function Layout($view, $time)
	{
		$this->view = $view;
		$this->start_time = $time;
		if ($view == 'month')
			$this->end_time = $this->start_time->getMonthendtime();
		if ($view == 'day')
			$this->end_time = $this->start_time->getDayendtime();
		if ($view == 'hour')
			$this->end_time = $this->start_time->getHourendtime();
	}

	/**
	 * Function to get view 
	 * return currentview
	 */
	public function getView()
	{
		return $this->view;
	}
}