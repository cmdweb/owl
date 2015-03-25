<?php
/**
 * Created by PhpStorm.
 * User: gabriel.malaquias
 * Date: 05/01/2015
 * Time: 15:16
 */

namespace Alcatraz\Owl;


class OwlException extends \Exception {
    public function __construct($msg,$code = 0){
        parent::__construct($msg,$code);
    }
} 