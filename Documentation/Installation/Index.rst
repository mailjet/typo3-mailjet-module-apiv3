.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


Mailjet API Extension for Typo3 v8 and v7
===========================

Mailjet is a powerful all-in-one email service provider used to get maximum 
insight and deliverability results from both marketing and transactional 
emails. Our analytics tools and intelligent APIs give senders the best 
understanding of how to maximize benefits for each individual contact and 
campaign email after email. 


Prerequisites
-------------
The Mailjet plugin relies on the PHPMailer library for sending emails.

The PHPMailer v.5.2.22 is included by default in the Mailjet plugin! Please update it manually after installation to avoid security risks due to obsolete PHPMailer version.

To install PHPMailer via composer use: NOT SUPPORTED YET! 

To update version of PHPMailer manually:
1) Delete the old folder "phpmailer" located inside mailjet_extension_path/Resources/Private/Libraries.
2) Get the PHPMailer from GitHub here: https://github.com/PHPMailer/PHPMailer/releases
3) Extract the archive and rename the folder "PHPMailer-5.2.xx" to "phpmailer".
4) Upload the "phpmailer" folder to your server inside mailjet_extension_path/Resources/Private/Libraries
Example: typo3conf/ext/mailjet/Resources/Private/Libraries
5) Check if the 'phpmailer' is properly installed by sending a test email from the Mailjet admin page. Make sure tou have enabled sending via Mailjet by marking "Send emails through Mailjet" in the Mailjet admin page and optionally "Allow HTML" to send emails in HTML format.


Installation
============

**Installation**

This extension can be installed like any regular TYPO3 extension:

- Use the Extension Manager to download the extension
- If the name of zip file is different from 'mailjet.zip', you must rename it to 'mailjet.zip'
- Use composer to require the package ``mailjet``.
- After enabling the Mailjet extension in the Extension Manager, you need to provide your Mailjet api key and secret key.
