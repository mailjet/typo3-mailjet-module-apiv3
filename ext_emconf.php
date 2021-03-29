<?php

$EM_CONF[$_EXTKEY] = [
  'title' => 'Mailjet Email Marketing',
  'description' => "Use Mailjet's SMTP to send Typo3 transactional emails. Add newsletter subscribers from Typo3 to your Mailjet contact lists.",
  'category' => 'plugin',
  'author' => 'Mailjet',
  'author_email' => 'plugins@mailjet.com',
  'state' => 'stable',
  'internal' => '',
  'uploadfolder' => '1',
  'createDirs' => '',
  'clearCacheOnLoad' => 0,
  'version' => '1.0.5',
  'constraints' => [
    'depends' => [
      'typo3' => '7.6.0-10.9.99',
    ],
    'conflicts' => [],
    'suggests' => [
      'typoscript_rendering' => '1.0.5-1.99.999',
    ],
  ],
];
