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

	/**
	 * Loads a template file and replaces each {{placeholder}} with the
	 * corresponding value from $replacements, then sets the email body.
	 *
	 * @param string               $template     Template filename inside templates/email/.
	 * @param array<string,string> $replacements Map of placeholder names (without braces) to values.
	 */
	public function prepareFromTemplate( string $template, array $replacements ): void {
		$path = realpath(__DIR__) . '/../templates/email/' . $template;
		if (false === $path || !is_readable($path)) {
			throw new \RuntimeException('Email template not found: ' . $template);
		}

		$data = file_get_contents($path);
		if ($data === false) {
			throw new \RuntimeException('Failed to read email template: ' . $template);
		}

		// Always inject the standard placeholders.
		$replacements['dbg_lv_website']        = esc_url( $replacements['dbg_lv_website'] ?? get_site_url() );
		$replacements['dbg_lv_generated_date'] = date_i18n('jS \of F Y \a\t h:i:s A');

		foreach ($replacements as $key => $value) {
			$data = str_replace('{{' . $key . '}}', (string) $value, $data);
		}

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
