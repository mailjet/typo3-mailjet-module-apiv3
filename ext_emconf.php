<?php

$EM_CONF[$_EXTKEY] = [
  'title' => 'Mailjet',
  'description' => 'Send your emails by your MailJet API.',
  'category' => 'plugin',
  'author' => '',
  'author_email' => 'example@mail.com',
  'state' => 'beta',
  'internal' => '',
  'uploadfolder' => '1',
  'createDirs' => '',
  'clearCacheOnLoad' => 0,
  'version' => '1.0.0',
  'constraints' => [
    'depends' => [
      'typo3' => '7.6.0-8.9.99',
    ],
    'conflicts' => [],
    'suggests' => [
      'typoscript_rendering' => '1.0.5-1.99.999',
    ],
  ],
];