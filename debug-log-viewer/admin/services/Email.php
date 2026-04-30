<?php

namespace DebugLogViewer\Admin\Services;

use DebugLogViewer\Admin\Controllers\AlertController;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class EmailService {

	public $emails;
	public $subject;
	public $body;
	public $notificationController;

	public function __construct( ?AlertController $notificationController = null ) {
		$this->notificationController = $notificationController ?? new AlertController();
	}

	public function setBody( $body ) {
		$this->body = $body;
	}
	public function getBody() {
		return $this->body;
	}

	public function setEmails( $emails ) {
		$this->emails = $emails;
	}
	public function getEmails() {
		return $this->emails;
	}

	public function setSubject( $subject ) {
		$this->subject = $subject;
	}
	public function getSubject() {
		return $this->subject;
	}

	public function prepare( $template, $params ) {
		$data = file_get_contents(realpath(__DIR__) . '/../templates/email/' . $template);
		$data = str_replace('{{dbg_lv_summary}}', $this->renderTable($params['entries']), $data);
		$data = str_replace('{{dbg_lv_website}}', $params['website'], $data);
		$data = str_replace('{{dbg_lv_generated_date}}', date_i18n('jS \of F Y \a\t h:i:s A'), $data);

		$this->setBody($data);
	}

	private function renderTable( $errors ) {
		$body     = '';
		$td_style_odd  = ' style="border-bottom: 1px solid #e8eef8; color: #4c4c4c; padding: 10px 12px; vertical-align: top;"';
		$td_style_even = ' style="background-color: #f7f9ff; border-bottom: 1px solid #e8eef8; color: #4c4c4c; padding: 10px 12px; vertical-align: top;"';

		$index = 1;
		foreach ($this->notificationController->getLevelsToReport() as $level) {
			foreach ($errors[ $level ] as $hash => $error) {
				$td_style = ($index % 2 === 0) ? $td_style_even : $td_style_odd;
				$row      = '' .
					'<tr>' .
					'    <td ' . $td_style . '>%s</td>' .
					'    <td ' . $td_style . '>%s</td>' .
					'    <td ' . $td_style . '>%s</td>' .
					'    <td ' . $td_style . '>%s</td>' .
					'    <td ' . $td_style . '>%s</td>' .
					'    <td ' . $td_style . '>%d</td>' .
					'</tr>';
				$body  .= sprintf($row, $index, $error['type'], $error['description']['text'], $error['file'], $error['line'], $error['hits']);
				$index += 1;
			}
		}

		return $body;
	}

	public function send() {
		$emails = $this->getEmails();
		if (!empty($emails)) {
			// Send to all emails in bulk
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			$result = wp_mail($emails, $this->getSubject(), $this->getBody(), $headers);

			// Log or handle errors if sending fails
			if (!$result) {
				error_log('Failed to send emails to: ' . implode(', ', $emails));
			}
		}
	}
}
