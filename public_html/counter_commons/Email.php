<?php

/*
 * This file is part of Unblock Request System <//urs.sf.net/>.
 *
 * URS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * URS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with URS.  If not, see <//www.gnu.org/licenses/>.
 */

/**
 * @file
 * Email object
 */

/**
 * The email class
 * All the functions in this class assume the object is either explicitly
 * loaded or filled. It is not load-on-demand. There are no accessors.
 */
class Email {
	/**
     * Who to send to
     * @var array
     */
	private $mTo = array();
	
	/**
     * Who the message is from
     * @var array
     */
	private $mFrom = array();
	
	/**
     * Subject
     * @var string
     */
	private $mSubject;
	
	/**
     * Message to send
     * @var string
     */
	private $mMessage;
	
	/**
     * CC field
     * @var array
     */
	private $mCC = array();
	
	/**
     * BCC field
     * @var array
     */
	private $mBCC = array();
	
	/**
     * Reply-to field
     * @var array
     */
	private $mRT = array();
	
	/**
     * Construct function, adds the From: field, subject, and message
     * @param string $fromEmail Email address of sender
     * @param string $fromName Name of sender.
     * @param string $subject Subject of email
     * @param string $message Message to send
     */
	function __construct( $fromEmail, $fromName, $subject, $message ) {
		if( !is_null( $fromName ) ) {
			$this->mFrom[$fromName] = $fromEmail;
		}
		else {
			$this->mFrom[] = $fromEmail;
		}
		$this->mSubject = $subject;
		$this->mMessage = $message;
	}
	
	/**
     * Adds another email to the To: field.
     * @param string $toEmail Email address of recipient
     * @param string $toName Name of recipient. Default null
     */
	public function addTarget( $toEmail, $toName = null ) {
		if( !is_null( $toName ) ) {
			$this->mTo[$toName] = $toEmail;
		}
		else {
			$this->mTo[] = $toEmail;
		}
	}
	
	/**
     * Adds another email to the CC: field.
     * @param string $ccEmail Email address of cc
     * @param string $ccName Name of cc. Default null
     */
	public function addCC( $ccEmail, $ccName = null ) {
		if( !is_null( $ccName ) ) {
			$this->mCC[$ccName] = $ccEmail;
		}
		else {
			$this->mCC[] = $ccEmail;
		}
	}
	
	/**
     * Adds another email to the BCC: field.
     * @param string $bccEmail Email address of bcc
     * @param string $bccName Name of bcc. Default null
     */
	public function addBCC( $bccEmail, $bccName = null ) {
		if( !is_null( $bccName ) ) {
			$this->mBCC[$bccName] = $bccEmail;
		}
		else {
			$this->mBCC[] = $bccEmail;
		}
	}
	
	/**
     * Adds another email to the Reply-to: field.
     * @param string $rtEmail Email address to reply to
     * @param string $rtName Name to reply to. Default null
     */
	public function addReplyTo( $rtEmail, $rtName = null ) {
		if( !is_null( $rtName ) ) {
			$this->mRT[$rtName] = $rtEmail;
		}
		else {
			$this->mRT[] = $rtEmail;
		}
	}
	
	/**
     * Sends the email.
     */
	public function send() {
		$msg = array();
		
		$msg_to = array();
		foreach( $this->mTo as $name => $target ) {
			if( !is_string($name) ) {
				$msg_to[] = $target;
			}
			else {
				$msg_to[] = "$name <$target>";
			}
		}
		$msg['to'] = implode(', ',$msg_to);
		
		$msg_from = array();
		foreach( $this->mFrom as $name => $target ) {
			if( !is_string($name) ) {
				$msg_from[] = $target;
			}
			else {
				$msg_from[] = "$name <$target>";
			}
		}
		$msg['from'] = null;
		if( count( $msg_from ) > 0 ) $msg['from'] = "From: " . implode(', ',$msg_from);
		
		$msg_cc = array();
		foreach( $this->mCC as $name => $target ) {
			if( !is_string($name) ) {
				$msg_cc[] = $target;
			}
			else {
				$msg_cc[] = "$name <$target>";
			}
		}
		$msg['cc'] = null;
		if( count( $msg_cc) > 0  ) $msg['cc'] = "CC: " . implode(', ',$msg_cc);
		
		$msg_bcc = array();
		foreach( $this->mBCC as $name => $target ) {
			if( !is_string($name) ) {
				$msg_bcc[] = $target;
			}
			else {
				$msg_bcc[] = "$name";
			}
		}
		$msg['bcc'] = null;
		if( count( $msg_bcc ) > 0 ) $msg['bcc'] = "BCC: " . implode(', ',$msg_bcc);
		
		$msg_rt = array();
		foreach( $this->mRT as $name => $target ) {
			if( !is_string($name) ) {
				$msg_rt[] = $target;
			}
			else {
				$msg_rt[] = "$name <$target>";
			}
		}
		$msg['rt'] = null;
		if( count( $msg_rt ) > 0 ) $msg['rt'] = "Reply-to: " . implode(', ',$msg_rt);
		
		$msg['subject'] = $this->mSubject;
		$msg['message'] = $this->mMessage;
		$msg['version'] = 'X-Mailer: PHP/' . phpversion();
		
		$msg['headers'] = $msg['from'] . "\r\n";
		if( !is_null( $msg['rt'] ) ) $msg['headers'] .= $msg['rt'] . "\r\n";
		if( !is_null( $msg['cc'] ) ) $msg['headers'] .= $msg['cc'] . "\r\n";
		if( !is_null( $msg['bcc'] ) ) $msg['headers'] .= $msg['bcc'] . "\r\n";
		$msg['headers'] .= $msg['version'];
		
		$result = mail( $msg['to'], $msg['subject'], $msg['message'], $msg['headers'] );
		
		return $result;
	}
}
