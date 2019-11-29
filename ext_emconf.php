<?php

$EM_CONF[$_EXTKEY] = array(
  'title' => 'Edusharing',
  'description' => '',
  'category' => 'fe',
  'state' => 'stable',
  'author' => 'shippeli',
  'author_email' => '',
  'version' => '0.1',
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
