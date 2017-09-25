<?php

$EM_CONF[$_EXTKEY] = array (
  'title' => 'edusharing',
  'description' => 'edusharing',
  'category' => 'fe',
  'state' => 'stable',
  'author' => 'shippeli',
  'author_email' => '',
  'version' => '0.1',
  'constraints' => 
  array (
    'depends' => 
    array (
      'typo3' => '8.7.4',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  'autoload' => 
  array (
    'psr-4' => 
    array (
      'metaVentis\\edusharing\\' => 'Classes',
    ),
  ),
  'uploadfolder' => false,
  'createDirs' => NULL,
  'clearcacheonload' => false,
  'author_company' => NULL,
);

