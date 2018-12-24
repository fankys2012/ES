<?php 
 return array (
  'live' => 
  array (
    'live_name' => 
    array (
      'patterns' => 
      array (
        0 => '/第(.)+季/',
      ),
      'replace' => 
      array (
        0 => '',
      ),
    ),
  ),
  'vod' => 
  array (
    'vod_alias' => 
    array (
      'patterns' => 
      array (
        0 => '/abb/',
        1 => 'ab',
      ),
      'replace' => 
      array (
        0 => 'a',
        1 => 'a',
      ),
    ),
    'vod_name' => 
    array (
      'patterns' => 
      array (
        0 => '/第(.)+季/',
      ),
      'replace' => 
      array (
        0 => '',
      ),
    ),
  ),
);