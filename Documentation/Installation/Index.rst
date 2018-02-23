.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


Mailjet Module - Typo v8 and v7
===========================

Mailjet is a powerful all-in-one email service provider used to get maximum 
insight and deliverability results from both  marketing and transactional 
emails. Our analytics tools and intelligent APIs give senders the best 
understanding of how to maximize benefits for each individual contact and 
campaign email after email. 


Prerequisites
-------------
The Mailjet plugin relies on the PHPMailer v5.2.21 for sending emails.

The PHPMailer is installed in the mailjet plugin before installtion!!! His version is 5.2.22!

To install PHPMailer via composer use: NOT SUPPORT YET! 

To update version of PHPMailer manually:
1) Get the PHPMailer from GitHub here:
http://github.com/PHPMailer/PHPMailer/archive/v5.2.22.zip
2) Delete old folder "phpmailer" inside directory mailjet_extension_path/Resources/Private/Libraries and put new archive.
3) Extract the archive and rename the folder "PHPMailer-5.2.22" to "phpmailer".
4) Upload the "phpmailer" folder to your server inside mailjet_extension_path/Resources/Private/Libraries!    
Example : typo3conf/ext/mailjet/Resources/Private/Libraries
5) Check if the 'phpmailer' is properly installed by submitting a test email from a subscription form. You should also check the field of your admin panel for extension - Send emails through Mailjet and
Allow HTML
6) The end


Installation
============

**Installation**

This extension can be installed like any regular TYPO3 extension:

- Use the Extension Manager to download the extension
- If the name of archive extension is different from 'mailjet', you must  rename archive extension. Example: mailjet.zip
- Use composer to require the package ``mailjet``.
- After enabling the Mailjet ext. in the Extension Manager, you need to provede your api key and secret key. When you create a subscription form in
 some page, your admin settings will be updated by Mailjet database (Profile and tracking settings).

