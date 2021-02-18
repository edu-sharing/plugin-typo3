<?php

$EM_CONF[$_EXTKEY] = array(
  'title' => 'Edusharing',
  'description' => '',
  'category' => 'fe',
  'state' => 'stable',
  'author' => 'hippeli',
  'author_email' => 'hippeli@metaventis.com',
  'version' => '2.0',
  'constraints' => array(
    'depends' => array(
      'typo3' => '9.5.0-9.5.99',
    ),
    'conflicts' => array(),
    'suggests' => array(),
  ),
  'autoload' => array(
    'psr-4' => array(
      'Metaventis\\Edusharing\\' => 'Classes',
    ),
  ),
  'uploadfolder' => false,
  'createDirs' => NULL,
  'clearcacheonload' => false,
  'author_company' => NULL,
);
