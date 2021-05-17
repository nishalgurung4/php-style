<?php

$finder = PhpCsFixer\Finder::create()
  ->notPath('vendor')
    ->notPath('bootstrap')
    ->notPath('storage')
    ->in(__DIR__)
    ->name('*.php')
    ->notName('*.blade.php');

 
 return Nishal\styles($finder);